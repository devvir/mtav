<?php

use App\Events\MessageChanged;
use App\Events\MessageSent;
use App\Services\AccountService;
use App\Services\ChatService;
use App\Services\Push\PushSender;
use Illuminate\Support\Facades\Event;

/**
 * Who hears of a conversation's messages: only those in it who may still read it. Someone who
 * left the project, or whose account was blocked, stays in its conversations (should they come
 * back, theirs are there), but nothing of them reaches their pages or devices meanwhile.
 */

beforeEach(function () {
    config(['services.push.public_key' => 'public', 'services.push.private_key' => 'private']);
    $this->project = aProject();
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto']);
    $this->carla = aMemberOf(aFamily($this->project), ['firstname' => 'Carla']);
    $this->chat = app(ChatService::class);
    $this->group = $this->chat->start($this->project, $this->ana, [$this->beto->id, $this->carla->id], 'Roof');

    $this->pushed = new class () implements PushSender {
        public array $to = [];

        public function queue(App\Models\PushSubscription $to, array $payload): void
        {
            $this->to[] = $to->user_id;
        }

        public function flush(): array
        {
            return [];
        }
    };
    app()->instance(PushSender::class, $this->pushed);
    foreach ([$this->beto, $this->carla] as $person) {
        $person->pushSubscriptions()->create(['endpoint' => "https://push.example.com/{$person->id}", 'public_key' => 'k', 'auth_token' => 'a']);
    }
});

/** Who a message reached, on their pages and on their devices */
function reachedBy($test, string $body): array
{
    Event::fake([MessageSent::class]);
    // Its devices too: the queue delivers at once here
    $test->chat->post($test->group, $test->ana, $body);

    $channels = [];
    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use (&$channels) {
        $channels = collect($event->broadcastOn())->map->name->sort()->values()->all();

        return true;
    });

    return [$channels, $test->pushed->to];
}

it('reaches everyone in the conversation who is still in the project', function () {
    [$channels, $devices] = reachedBy($this, 'Hello');

    expect($channels)->toBe(collect([$this->ana, $this->beto, $this->carla])->map(fn ($person) => "private-users.{$person->id}")->sort()->values()->all())
        ->and($devices)->toEqualCanonicalizing([$this->beto->id, $this->carla->id]);
});

it('reaches nobody who left the project, though they stay in it', function () {
    $this->carla->memberships()->where('project_id', $this->project->id)->first()->delete();

    [$channels, $devices] = reachedBy($this, 'After Carla left');

    expect($channels)->not->toContain("private-users.{$this->carla->id}")
        ->and($devices)->toBe([$this->beto->id])
        ->and($this->group->includes($this->carla))->toBeTrue();
});

it('reaches no blocked account, whose devices are forgotten', function () {
    app(AccountService::class)->block($this->carla);

    [$channels, $devices] = reachedBy($this, 'After Carla was blocked');

    expect($channels)->not->toContain("private-users.{$this->carla->id}")
        ->and($devices)->toBe([$this->beto->id])
        ->and($this->carla->pushSubscriptions()->count())->toBe(0);
});

it('tells nobody who left of a message edited or deleted', function () {
    $message = $this->chat->post($this->group, $this->ana, 'Hello');
    $this->carla->memberships()->where('project_id', $this->project->id)->first()->delete();
    Event::fake([MessageChanged::class]);

    $this->chat->edit($message, 'Hello, all');
    $this->chat->remove($message);

    Event::assertDispatchedTimes(MessageChanged::class, 2);
    Event::assertDispatched(MessageChanged::class, fn (MessageChanged $event) => ! in_array("private-users.{$this->carla->id}", collect($event->broadcastOn())->map->name->all(), true));
});

it('lets whoever left read nothing new', function () {
    $this->carla->memberships()->where('project_id', $this->project->id)->first()->delete();
    $this->chat->post($this->group, $this->ana, 'After Carla left');

    expect($this->as($this->carla->fresh(), $this->project)->getJson(route('chat.panel', ['conversation' => $this->group->id])))->toBeDenied();
});
