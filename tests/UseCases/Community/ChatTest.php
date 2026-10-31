<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Event;

/**
 * The chat: anyone with a place in a project talks with anyone else of it, one on one
 * (two people have one conversation) or in groups. Only those in a conversation read and
 * write it: nobody else, the project's admins and superadmins included. What each person
 * has not read yet is counted for them.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto']);
    $this->carla = aMemberOf(aFamily($this->project), ['firstname' => 'Carla']);
});

function startChat($test, $user, array $people, ?string $title = null)
{
    return $test->as($user, $test->project)->post(route('chat.store', $test->project), [
        'people' => array_map(fn ($person) => $person->id, $people),
        'title'  => $title,
    ]);
}

function say($test, $user, Conversation $conversation, string $body = 'Hello!')
{
    return $test->as($user, $test->project)->post(route('chat.send', [$test->project, $conversation]), ['body' => $body]);
}

it('gives two people one conversation, whoever starts it', function () {
    startChat($this, $this->ana, [$this->beto])->assertSessionHasNoErrors();
    $conversation = Conversation::where('is_community', false)->where('is_assistant', false)->sole();

    startChat($this, $this->beto, [$this->ana])->assertRedirect(route('chat.show', [$this->project, $conversation]));

    expect(Conversation::where('is_community', false)->where('is_assistant', false)->count())->toBe(1)
        ->and($conversation->is_group)->toBeFalse()
        ->and($conversation->participants()->pluck('users.id')->sort()->values()->all())->toBe(collect([$this->ana->id, $this->beto->id])->sort()->values()->all());
});

it('starts a group, with its name, for several people', function () {
    startChat($this, $this->ana, [$this->beto, $this->carla], 'Building committee')->assertSessionHasNoErrors();

    expect(Conversation::where('is_community', false)->where('is_assistant', false)->sole())->is_group->toBeTrue()->title->toBe('Building committee')
        ->and(Conversation::where('is_community', false)->where('is_assistant', false)->sole()->participants()->count())->toBe(3);
});

it('lets only those in a conversation read and write it, the admins neither', function () {
    startChat($this, $this->ana, [$this->beto]);
    $conversation = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    say($this, $this->ana, $conversation, 'Our secret')->assertSessionHasNoErrors();

    $this->as($this->beto, $this->project)->get(route('chat.show', [$this->project, $conversation]))->assertOk()
        ->assertInertia(fn ($page) => $page->where('messages.data.0.body', 'Our secret'));

    foreach ([$this->carla, $this->admin, aSuperadmin()] as $outsider) {
        expect($this->as($outsider, $this->project)->get(route('chat.show', [$this->project, $conversation])))->toBeDenied();
        expect(say($this, $outsider, $conversation, 'Let me in'))->toBeDenied();
    }

    expect(Message::count())->toBe(1);
});

it('never starts a conversation with someone outside the project', function () {
    $stranger = aMemberOf(aFamily(aProject()));

    startChat($this, $this->ana, [$stranger])->assertSessionHasErrors('people');

    expect(Conversation::where('is_community', false)->where('is_assistant', false)->count())->toBe(0);
});

it('keeps a project\'s conversations at its own address', function () {
    startChat($this, $this->ana, [$this->beto]);
    $conversation = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    $other = aProject();
    $this->ana->memberships()->create(['project_id' => $other->id, 'family_id' => aFamily($other)->id]);

    $this->as($this->ana->fresh(), $other)->get(route('chat.show', [$other, $conversation]))->assertNotFound();
});

it('counts the conversations with something new for each person, until they read it', function () {
    startChat($this, $this->ana, [$this->beto]);
    $conversation = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    say($this, $this->ana, $conversation);

    $unread = fn ($user) => $this->as($user, $this->project)->get(route('projects.show', $this->project))->inertiaProps('project.unread_messages');

    expect($unread($this->beto))->toBe(1)
        // What one wrote is read
        ->and($unread($this->ana))->toBe(0);

    $this->as($this->beto, $this->project)->get(route('chat.show', [$this->project, $conversation]));

    expect($unread($this->beto))->toBe(0);
});

it('tells everyone in the conversation of a message, on their own channels only', function () {
    Event::fake([MessageSent::class]);
    startChat($this, $this->ana, [$this->beto]);
    say($this, $this->ana, Conversation::where('is_community', false)->where('is_assistant', false)->sole());

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
        $channels = collect($event->broadcastOn())->map->name->sort()->values()->all();

        return $channels === collect(["private-users.{$this->ana->id}", "private-users.{$this->beto->id}"])->sort()->values()->all();
    });
});

it('lets those in a group rename it, add people, and leave it; one on one stays two', function () {
    startChat($this, $this->ana, [$this->beto, $this->carla], 'Committee');
    $group = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    $dora = aMemberOf(aFamily($this->project), ['firstname' => 'Dora']);

    $this->as($this->beto, $this->project)->put(route('chat.update', [$this->project, $group]), ['title' => 'Works committee', 'people' => [$dora->id]])->assertSessionHasNoErrors();
    expect($group->fresh()->title)->toBe('Works committee')
        ->and($group->participants()->count())->toBe(4);

    $this->as($this->carla, $this->project)->delete(route('chat.leave', [$this->project, $group]))->assertRedirect(route('chat.index', $this->project));
    expect($group->includes($this->carla))->toBeFalse();

    startChat($this, $this->ana, [$this->beto]);
    $pair = Conversation::where('is_group', false)->where('is_assistant', false)->sole();
    expect($this->as($this->ana, $this->project)->put(route('chat.update', [$this->project, $pair]), ['people' => [$dora->id]]))->toBeDenied()
        ->and($this->delete(route('chat.leave', [$this->project, $pair])))->toBeDenied();
});

it('lets whoever wrote a message take it back, and nobody else', function () {
    startChat($this, $this->ana, [$this->beto]);
    $conversation = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    say($this, $this->ana, $conversation, 'First');
    say($this, $this->ana, $conversation, 'Oops');
    $oops = Message::where('body', 'Oops')->sole();

    expect($this->as($this->beto, $this->project)->delete(route('chat.messages.destroy', [$this->project, $oops])))->toBeDenied();

    $this->as($this->ana, $this->project)->delete(route('chat.messages.destroy', [$this->project, $oops]))->assertRedirect();

    expect(Message::pluck('body')->all())->toBe(['First'])
        // The conversation ends on the one before
        ->and($conversation->fresh()->lastMessage->body)->toBe('First');
});

it('opens a conversation with its latest messages, earlier ones on asking', function () {
    startChat($this, $this->ana, [$this->beto]);
    $conversation = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    foreach (range(1, 60) as $n) {
        say($this, $this->ana, $conversation, "Message {$n}");
    }

    $first = $this->as($this->beto, $this->project)->get(route('chat.show', [$this->project, $conversation]))->inertiaProps('messages');
    expect($first['earlier'])->toBeTrue()
        ->and(collect($first['data'])->pluck('body')->first())->toBe('Message 11')
        ->and(collect($first['data'])->pluck('body')->last())->toBe('Message 60');

    $all = $this->get(route('chat.show', [$this->project, $conversation, 'count' => 100]))->inertiaProps('messages');
    expect($all['earlier'])->toBeFalse()->and($all['data'])->toHaveCount(60);
});

describe('The Community group', function () {
    $community = fn ($project) => Conversation::where('project_id', $project->id)->where('is_community', true)->sole();

    it('comes with every project, and everyone is in it as they join', function () use ($community) {
        $project = aProject();
        $member = aMemberOf(aFamily($project));
        $admin = anAdminOf($project);

        expect($community($project)->participants()->pluck('users.id')->all())->toContain($member->id, $admin->id);
    });

    it('loses whoever leaves the project', function () use ($community) {
        $project = aProject();
        $member = aMemberOf(aFamily($project));

        $member->memberships()->where('project_id', $project->id)->first()->delete();

        expect($community($project)->participants()->whereKey($member->id)->exists())->toBeFalse();
    });

    it('is left by nobody and renamed by nobody', function () use ($community) {
        $project = aProject();
        $member = aMemberOf(aFamily($project));
        $group = $community($project);

        expect($member->can('leaveGroup', $group))->toBeFalse()
            ->and($member->can('manageGroup', $group))->toBeFalse()
            ->and($member->can('writeChat', $group))->toBeTrue();
    });

    it('reads as Community, each in their language', function () use ($community) {
        $project = aProject();
        $member = aMemberOf(aFamily($project), ['locale' => 'es_UY']);

        $listed = $this->as($member, $project)->get(route('chat.index'))->inertiaProps('conversations');

        expect(collect($listed)->firstWhere('is_community', true)['title'])->toBe('Comunidad');
    });

    it('stays off people\'s devices', function () use ($community) {
        $project = aProject();
        $author = aMemberOf(aFamily($project));
        $other = aMemberOf(aFamily($project));
        $other->pushSubscriptions()->create(['endpoint' => 'https://push.example.com/1', 'public_key' => 'k', 'auth_token' => 'a']);
        $sender = Mockery::mock(App\Services\Push\PushSender::class);
        $sender->shouldNotReceive('send');
        app()->instance(App\Services\Push\PushSender::class, $sender);

        $message = app(App\Services\ChatService::class)->send($community($project), $author, 'Hello everyone');
        app(App\Services\Push\PushService::class)->deliverMessage($message);
    });
});

describe('The window', function () {
    beforeEach(function () {
        $this->windowed = aProject();
        $this->one = aMemberOf(aFamily($this->windowed));
        $this->two = aMemberOf(aFamily($this->windowed));
        $this->pair = app(App\Services\ChatService::class)->start($this->windowed, $this->one, [$this->two->id]);
    });

    it('gets the list, and a conversation it opens, marking it read', function () {
        app(App\Services\ChatService::class)->send($this->pair, $this->one, 'Hola');

        $list = $this->as($this->two, $this->windowed)->getJson(route('chat.panel'))->assertOk();
        expect($list->json('conversation'))->toBeNull()
            ->and(collect($list->json('conversations'))->firstWhere('id', $this->pair->id)['unread'])->toBeTrue();

        $opened = $this->as($this->two, $this->windowed)->getJson(route('chat.panel', ['conversation' => $this->pair->id]))->assertOk();
        expect($opened->json('messages.data.0.body'))->toBe('Hola');

        $after = $this->as($this->two, $this->windowed)->getJson(route('chat.panel'));
        expect(collect($after->json('conversations'))->firstWhere('id', $this->pair->id)['unread'])->toBeFalse();
    });

    it('opens nobody else\'s conversation, nor one of another project', function () {
        $third = aMemberOf(aFamily($this->windowed));
        $this->as($third, $this->windowed)->getJson(route('chat.panel', ['conversation' => $this->pair->id]))->assertForbidden();

        $other = aProject();
        $outsider = aMemberOf(aFamily($other));
        $this->as($outsider, $other)->getJson(route('chat.panel', ['conversation' => $this->pair->id]))->assertNotFound();
    });

    it('writes and starts in JSON, the page staying as it is', function () {
        $this->as($this->one, $this->windowed)->postJson(route('chat.send', $this->pair), ['body' => 'Hola'])->assertOk();
        $started = $this->as($this->one, $this->windowed)->postJson(route('chat.store'), ['people' => [$this->two->id]])->assertOk();

        expect($started->json('conversation'))->toBe($this->pair->id);
    });

    it('says what is wrong with what it sends', function () {
        $this->as($this->one, $this->windowed)->postJson(route('chat.send', $this->pair), ['body' => ''])->assertUnprocessable();
    });

    it('is closed to those outside the project', function () {
        $this->as(aUser())->getJson(route('chat.panel', $this->windowed))->assertNotFound();
    });

    it('is gone with the chat off', function () {
        $this->windowed->update(['disabled_features' => ['chat']]);

        $this->as($this->one, $this->windowed)->getJson(route('chat.panel'))->assertNotFound();
    });
});

describe('Groups', function () {
    beforeEach(function () {
        $this->crowded = aProject();
        $this->one = aMemberOf(aFamily($this->crowded));
        $this->two = aMemberOf(aFamily($this->crowded));
        $this->three = aMemberOf(aFamily($this->crowded));
        $this->service = app(App\Services\ChatService::class);
        config(['limits.chat_groups_per_person' => 2]);
    });

    it('are not started past the limit: they say to leave one', function () {
        foreach (['A', 'B'] as $name) {
            $this->service->start($this->crowded, $this->one, [$this->two->id, $this->three->id], $name);
        }

        $this->as($this->one, $this->crowded)->postJson(route('chat.store'), ['people' => [$this->two->id, $this->three->id], 'title' => 'C'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.people.0', __('validation.chat_group_limit', ['max' => 2]));
    });

    it('take nobody already in as many as there can be', function () {
        $four = aMemberOf(aFamily($this->crowded));
        foreach (['A', 'B'] as $name) {
            $this->service->start($this->crowded, $this->two, [$this->three->id, $four->id], $name);
        }
        $mine = $this->service->start($this->crowded, $this->one, [aMemberOf(aFamily($this->crowded))->id, aMemberOf(aFamily($this->crowded))->id], 'Mine');

        $this->as($this->one, $this->crowded)->putJson(route('chat.update', $mine), ['title' => 'Mine', 'people' => [$this->two->id]])
            ->assertUnprocessable();
        expect($mine->includes($this->two))->toBeFalse();
    });

    it('leave Community out of the count, and one-on-one chats too', function () {
        $this->service->start($this->crowded, $this->one, [$this->two->id]);
        $this->service->start($this->crowded, $this->one, [$this->three->id]);
        foreach (['A', 'B'] as $name) {
            $this->service->start($this->crowded, $this->one, [$this->two->id, $this->three->id], $name);
        }

        expect(Conversation::where('project_id', $this->crowded->id)->where('title', 'B')->exists())->toBeTrue();
    });

    it('stay with one person left, who may bring others in, and go with the last', function () {
        $group = $this->service->start($this->crowded, $this->one, [$this->two->id, $this->three->id], 'Few');

        $this->service->leave($group, $this->one);
        $this->service->leave($group, $this->two);

        expect(Conversation::find($group->id))->not->toBeNull()
            ->and($this->three->can('manageGroup', $group->fresh()))->toBeTrue();

        $this->service->leave($group->fresh(), $this->three);

        expect(Conversation::find($group->id))->toBeNull();
    });
});

describe('Pins', function () {
    beforeEach(function () {
        $this->pinning = aProject();
        $this->one = aMemberOf(aFamily($this->pinning));
        $this->two = aMemberOf(aFamily($this->pinning));
        $this->pair = app(App\Services\ChatService::class)->start($this->pinning, $this->one, [$this->two->id]);
    });

    $listed = fn ($test, $user) => collect($test->as($user, $test->pinning)->getJson(route('chat.panel'))->json('conversations'));

    it('are each person\'s own', function () use ($listed) {
        $this->as($this->one, $this->pinning)->putJson(route('chat.pin', $this->pair), ['pinned' => true])->assertOk();

        expect($listed($this, $this->one)->firstWhere('id', $this->pair->id)['pinned'])->toBeTrue()
            ->and($listed($this, $this->two)->firstWhere('id', $this->pair->id)['pinned'])->toBeFalse();

        $this->as($this->one, $this->pinning)->putJson(route('chat.pin', $this->pair), ['pinned' => false])->assertOk();
        expect($listed($this, $this->one)->firstWhere('id', $this->pair->id)['pinned'])->toBeFalse();
    });

    it('start on Community, and it can be unpinned', function () use ($listed) {
        $community = $listed($this, $this->one)->firstWhere('is_community', true);
        expect($community['pinned'])->toBeTrue();

        $this->as($this->one, $this->pinning)->putJson(route('chat.pin', $community['id']), ['pinned' => false])->assertOk();
        expect($listed($this, $this->one)->firstWhere('is_community', true)['pinned'])->toBeFalse();
    });

    it('are only for conversations one is in', function () {
        $this->as(aMemberOf(aFamily($this->pinning)), $this->pinning)->putJson(route('chat.pin', $this->pair), ['pinned' => true])->assertForbidden();
    });
});

it('names a conversation with many people without loading them all', function () {
    $project = aProject();
    $family = aFamily($project);
    $me = aMemberOf($family);
    foreach (range(1, 10) as $n) {
        aMemberOf($family);
    }

    $listed = $this->as($me, $project)->getJson(route('chat.panel'))->assertOk()->json('conversations');

    expect(collect($listed)->firstWhere('is_community', true)['faces'])->toHaveCount(3);
});
