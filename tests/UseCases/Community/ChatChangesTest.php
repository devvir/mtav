<?php

use App\Events\MessageChanged;
use App\Jobs\AnswerInChat;
use App\Jobs\PushMessage;
use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Report;
use App\Services\Assistant\Assistant;
use App\Services\Assistant\Brain;
use App\Services\ChatService;
use App\Services\Push\PushSender;
use App\Services\Push\PushService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * Editing and deleting one's own messages: only one's own, only for an hour after sending,
 * the same for everyone at once, and nothing kept of what they said (no earlier version, no
 * "edited" mark, nothing in the activity log, nobody notified). With the Assistant a message
 * is deleted, never edited (its answer would stay as it was), and its answer goes with it.
 */

beforeEach(function () {
    // To the second: an hour after sending is exactly an hour
    $this->freezeSecond();
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto']);
    $this->chat = app(ChatService::class);
    $this->pair = $this->chat->start($this->project, $this->ana, [$this->beto->id]);
});

function editMessage($test, $user, Message $message, string $body)
{
    return $test->as($user, $test->project)->putJson(route('chat.messages.update', $message), ['body' => $body]);
}

function deleteMessage($test, $user, Message $message)
{
    return $test->as($user, $test->project)->deleteJson(route('chat.messages.destroy', $message));
}

function unreadFor($test, $user): int
{
    return $test->as($user, $test->project)->get(route('projects.show', $test->project))->inertiaProps('project.unread_messages');
}

/** Everything that could keep a record of a message, as it stands */
function recordsNow(): array
{
    return ['activity' => Activity::count(), 'notifications' => Notification::count()];
}

/** A brain that answers as told, running something while it thinks */
function thinkingBrain(string $answer, ?Closure $meanwhile = null): object
{
    $brain = new class ($answer, $meanwhile) implements Brain {
        public int $asked = 0;

        public function __construct(private string $answer, private ?Closure $meanwhile)
        {
        }

        public function ask(string $instructions, string $context, array $history): ?array
        {
            $this->asked++;
            $this->meanwhile && ($this->meanwhile)();

            return ['text' => $this->answer, 'actions' => []];
        }
    };
    app()->instance(Brain::class, $brain);
    config(['assistant.driver' => 'api']);

    return $brain;
}

function assistantChatOf($user, $project): Conversation
{
    return Conversation::where('project_id', $project->id)->where('pair', "assistant:{$user->id}")->sole();
}

describe('Editing', function () {
    it('rewords one\'s message for everyone, at once, on their own channels only', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'See you at 5');
        Event::fake([MessageChanged::class]);

        editMessage($this, $this->ana, $message, '  See you at 6  ')->assertOk();

        $seen = $this->as($this->beto, $this->project)->getJson(route('chat.panel', ['conversation' => $this->pair->id]));
        expect($message->fresh()->body)->toBe('See you at 6')
            ->and($seen->json('messages.data.0.body'))->toBe('See you at 6')
            ->and(collect($seen->json('conversations'))->firstWhere('id', $this->pair->id)['last']['body'])->toBe('See you at 6');
        Event::assertDispatched(MessageChanged::class, function (MessageChanged $event) {
            expect(collect($event->broadcastOn())->map->name->sort()->values()->all())
                ->toBe(collect(["private-users.{$this->ana->id}", "private-users.{$this->beto->id}"])->sort()->values()->all())
                ->and($event->broadcastWith())->toBe(['conversation_id' => $this->pair->id, 'by' => $this->ana->id, 'removed' => false])
                ->and($event->broadcastQueue())->toBe('realtime');

            return true;
        });
    });

    it('keeps nothing of what it said, no mark that it changed, and tells nobody', function () {
        config(['services.push.public_key' => 'public', 'services.push.private_key' => 'private']);
        $message = $this->chat->send($this->pair, $this->ana, 'My number is 099 123 456');
        $before = recordsNow();
        $times = [$message->created_at->toJSON(), $message->updated_at->toJSON()];
        Queue::fake();
        $this->travel(5)->minutes();

        editMessage($this, $this->ana, $message, 'Call me')->assertOk();

        $message->refresh();
        expect(Message::where('body', 'like', '%099%')->exists())->toBeFalse()
            ->and([$message->created_at->toJSON(), $message->updated_at->toJSON()])->toBe($times)
            ->and($this->as($this->beto, $this->project)->getJson(route('chat.panel', ['conversation' => $this->pair->id]))->json('messages.data.0'))
            ->not->toHaveKeys(['edited', 'edited_at', 'updated_at'])
            ->and(recordsNow())->toBe($before);
        Queue::assertNotPushed(PushMessage::class);
    });

    it('changes nothing and tells nobody for the same words', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'Same');
        Event::fake([MessageChanged::class]);

        editMessage($this, $this->ana, $message, 'Same')->assertOk();

        Event::assertNotDispatched(MessageChanged::class);
    });

    it('takes what a new message takes: some words, no longer than one', function (string $body) {
        $message = $this->chat->send($this->pair, $this->ana, 'Hello');

        editMessage($this, $this->ana, $message, $body)->assertUnprocessable()->assertJsonValidationErrors('body');

        expect($message->fresh()->body)->toBe('Hello');
    })->with([
        'empty'        => '',
        'only spaces'  => "   \n  ",
        'one too many' => str_repeat('a', 5001),
    ]);

    it('takes the longest message there can be', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'Hello');

        editMessage($this, $this->ana, $message, str_repeat('a', 5000))->assertOk();
    });

    it('is nobody else\'s to do: not the other person\'s, nor an admin\'s or a superadmin\'s', function () {
        $group = $this->chat->start($this->project, $this->ana, [$this->beto->id, $this->admin->id]);
        $message = $this->chat->send($group, $this->ana, 'Mine');
        $superadmin = aSuperadmin();
        $group->participants()->attach($superadmin->id);

        foreach ([$this->beto, $this->admin, $superadmin] as $someone) {
            expect(editMessage($this, $someone, $message, 'Theirs'))->toBeDenied();
        }

        expect($message->fresh()->body)->toBe('Mine');
    });

    it('stops being possible after an hour, saying why', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'Hello');

        $this->travel(60)->minutes();
        editMessage($this, $this->ana, $message, 'Right at the hour')->assertOk();

        $this->travel(1)->seconds();
        editMessage($this, $this->ana, $message, 'Too late')->assertStatus(409)
            ->assertJson(['message' => __('chat.change_too_late', ['minutes' => 60])]);

        expect($message->fresh()->body)->toBe('Right at the hour');
    });

    it('is not for whoever left the group, or the project', function () {
        $carla = aMemberOf(aFamily($this->project));
        $group = $this->chat->start($this->project, $this->ana, [$this->beto->id, $carla->id]);
        $inGroup = $this->chat->send($group, $carla, 'Bye soon');
        $inPair = $this->chat->send($this->chat->start($this->project, $carla, [$this->beto->id]), $carla, 'Hi Beto');

        $this->chat->leave($group, $carla);
        expect(editMessage($this, $carla, $inGroup, 'Changed'))->toBeDenied();

        $carla->memberships()->where('project_id', $this->project->id)->first()->delete();
        expect(editMessage($this, $carla->fresh(), $inPair, 'Changed'))->toBeDenied()
            ->and($inGroup->fresh()->body)->toBe('Bye soon')
            ->and($inPair->fresh()->body)->toBe('Hi Beto');
    });

    it('leaves read what the others read', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'Hello');
        $this->as($this->beto, $this->project)->get(route('chat.show', [$this->project, $this->pair]));
        expect(unreadFor($this, $this->beto))->toBe(0);

        editMessage($this, $this->ana, $message, 'Hello, Beto');

        expect(unreadFor($this, $this->beto))->toBe(0)
            ->and($this->pair->fresh()->last_message_id)->toBe($message->id);
    });

    it('is never for the Assistant\'s conversation, nor for what the Assistant said', function () {
        thinkingBrain('Sure');
        $conversation = assistantChatOf($this->ana, $this->project);
        $question = $this->chat->send($conversation, $this->ana, 'Where is the plan?');
        $answer = $conversation->messages()->where('from_assistant', true)->sole();

        expect(editMessage($this, $this->ana, $question, 'Where are the units?'))->toBeDenied()
            ->and(editMessage($this, $this->ana, $answer, 'Anything'))->toBeDenied()
            ->and($question->fresh()->body)->toBe('Where is the plan?');
    });

    it('tells the page until when each of one\'s own messages can change, and no other', function () {
        $mine = $this->chat->send($this->pair, $this->ana, 'Mine');
        $this->travel(10)->minutes();
        $theirs = $this->chat->send($this->pair, $this->beto, 'Theirs');
        $shown = fn () => collect($this->as($this->ana, $this->project)
            ->getJson(route('chat.panel', ['conversation' => $this->pair->id]))->json('messages.data'))->keyBy('id');

        expect($shown()[$mine->id]['changeable_until'])->toBe($mine->created_at->copy()->addHour()->toJSON())
            ->and($shown()[$theirs->id]['changeable_until'])->toBeNull();

        $this->travel(51)->minutes();
        expect($shown()[$mine->id]['changeable_until'])->toBeNull();
    });
});

describe('Deleting', function () {
    it('leaves no trace: gone for everyone, nothing recorded, nobody notified', function () {
        config(['services.push.public_key' => 'public', 'services.push.private_key' => 'private']);
        $this->chat->send($this->pair, $this->ana, 'First');
        $oops = $this->chat->send($this->pair, $this->ana, 'Oops, wrong chat');
        $before = recordsNow();
        Queue::fake();
        Event::fake([MessageChanged::class]);

        deleteMessage($this, $this->ana, $oops)->assertOk();

        expect(Message::where('body', 'like', '%wrong chat%')->exists())->toBeFalse()
            ->and(collect($this->as($this->beto, $this->project)->getJson(route('chat.panel', ['conversation' => $this->pair->id]))->json('messages.data'))->pluck('body')->all())
            ->toBe(['First'])
            ->and($this->pair->fresh()->lastMessage->body)->toBe('First')
            ->and(recordsNow())->toBe($before);
        Queue::assertNotPushed(PushMessage::class);
        Event::assertDispatched(MessageChanged::class, fn (MessageChanged $event) => $event->removed);
    });

    it('stops being possible after an hour, saying why', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'Hello');

        $this->travel(3601)->seconds();
        deleteMessage($this, $this->ana, $message)->assertStatus(409)
            ->assertJson(['message' => __('chat.change_too_late', ['minutes' => 60])]);
        expect($message->fresh())->not->toBeNull();
    });

    it('is still possible right at the hour', function () {
        $message = $this->chat->send($this->pair, $this->ana, 'Hello');

        $this->travel(60)->minutes();
        deleteMessage($this, $this->ana, $message)->assertOk();

        expect($message->fresh())->toBeNull();
    });

    it('is nobody else\'s to do: not the other person\'s, nor an admin\'s or a superadmin\'s', function () {
        $group = $this->chat->start($this->project, $this->ana, [$this->beto->id, $this->admin->id]);
        $message = $this->chat->send($group, $this->ana, 'Mine');
        $superadmin = aSuperadmin();
        $group->participants()->attach($superadmin->id);

        foreach ([$this->beto, $this->admin, $superadmin] as $someone) {
            expect(deleteMessage($this, $someone, $message))->toBeDenied();
        }

        expect($message->fresh())->not->toBeNull();
    });

    it('is not for whoever left the group, or the project', function () {
        $carla = aMemberOf(aFamily($this->project));
        $group = $this->chat->start($this->project, $this->ana, [$this->beto->id, $carla->id]);
        $inGroup = $this->chat->send($group, $carla, 'Bye soon');
        $inPair = $this->chat->send($this->chat->start($this->project, $carla, [$this->beto->id]), $carla, 'Hi Beto');

        $this->chat->leave($group, $carla);
        expect(deleteMessage($this, $carla, $inGroup))->toBeDenied();

        $carla->memberships()->where('project_id', $this->project->id)->first()->delete();
        expect(deleteMessage($this, $carla->fresh(), $inPair))->toBeDenied()
            ->and($inGroup->fresh())->not->toBeNull()
            ->and($inPair->fresh())->not->toBeNull();
    });

    it('takes back what it made unread, and nothing more', function () {
        $first = $this->chat->send($this->pair, $this->ana, 'First');
        $second = $this->chat->send($this->pair, $this->ana, 'Second');
        expect(unreadFor($this, $this->beto))->toBe(1);

        // The one before is still new to Beto
        deleteMessage($this, $this->ana, $second);
        expect(unreadFor($this, $this->beto))->toBe(1);

        // Nothing new is left
        deleteMessage($this, $this->ana, $first);
        expect(unreadFor($this, $this->beto))->toBe(0)
            ->and(collect($this->as($this->beto, $this->project)->getJson(route('chat.panel'))->json('conversations'))->firstWhere('id', $this->pair->id)['unread'])->toBeFalse();
    });

    it('makes nothing unread for whoever had read up to it', function () {
        $this->chat->send($this->pair, $this->beto, 'From Beto');
        $mine = $this->chat->send($this->pair, $this->ana, 'From Ana');
        $this->as($this->beto, $this->project)->get(route('chat.show', [$this->project, $this->pair]));

        deleteMessage($this, $this->ana, $mine);

        // The conversation ends on Beto's own message: nothing new for either
        expect(unreadFor($this, $this->beto))->toBe(0)
            ->and(unreadFor($this, $this->ana))->toBe(0);
    });

    it('sends nothing to devices for a message deleted before it was delivered', function () {
        config(['services.push.public_key' => 'public', 'services.push.private_key' => 'private']);
        $this->beto->pushSubscriptions()->create(['endpoint' => 'https://push.example.com/1', 'public_key' => 'k', 'auth_token' => 'a']);
        $sender = Mockery::mock(PushSender::class);
        $sender->shouldNotReceive('send');
        app()->instance(PushSender::class, $sender);
        Queue::fake([PushMessage::class]);
        $message = $this->chat->send($this->pair, $this->ana, 'Sent by mistake');

        deleteMessage($this, $this->ana, $message)->assertOk();
        Queue::assertPushed(PushMessage::class, function (PushMessage $job) {
            $job->handle(app(PushService::class));

            return true;
        });
    });

    it('reaches everyone of a large Community group at once, on their own channels', function () {
        $everyone = collect(range(1, 40))->map(fn () => aMemberOf(aFamily($this->project)));
        $community = Conversation::where('project_id', $this->project->id)->where('is_community', true)->sole();
        $message = $this->chat->send($community, $this->ana, 'Hello everyone');
        Event::fake([MessageChanged::class]);

        deleteMessage($this, $this->ana, $message)->assertOk();

        Event::assertDispatched(MessageChanged::class, fn (MessageChanged $event) => count($event->broadcastOn()) === $community->participants()->count()
            && count($event->broadcastOn()) >= $everyone->count());
        expect($community->messages()->count())->toBe(0);
    });
});

describe('With the Assistant', function () {
    beforeEach(function () {
        $this->conversation = assistantChatOf($this->ana, $this->project);
        $this->project->forceFill(['approved_at' => now()])->save();
    });

    it('deletes a question with its answer, and leaves the rest of the conversation', function () {
        thinkingBrain('Sure');
        $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');
        $second = $this->chat->send($this->conversation, $this->ana, 'And the units?');

        deleteMessage($this, $this->ana, $second)->assertOk();

        expect($this->conversation->messages()->orderBy('id')->pluck('body')->all())->toBe(['Where is the plan?', 'Sure'])
            ->and($this->conversation->fresh()->lastMessage->body)->toBe('Sure');
    });

    it('deletes the one answer to messages written in a row with any of them, keeping the others', function () {
        Queue::fake([AnswerInChat::class]);
        thinkingBrain('Both answered');
        $first = $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');
        $second = $this->chat->send($this->conversation, $this->ana, 'And the units?');
        app(Assistant::class)->answer($first);
        app(Assistant::class)->answer($second);
        expect($this->conversation->messages()->count())->toBe(3);

        deleteMessage($this, $this->ana, $first)->assertOk();

        expect($this->conversation->messages()->pluck('body')->all())->toBe(['And the units?']);
    });

    it('lets no answer come to a question deleted before it was answered', function () {
        Queue::fake([AnswerInChat::class]);
        $brain = thinkingBrain('Too late');
        $question = $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');

        deleteMessage($this, $this->ana, $question)->assertOk();
        Queue::assertPushed(AnswerInChat::class, function (AnswerInChat $job) {
            $job->handle(app(Assistant::class));

            return true;
        });

        expect($this->conversation->messages()->count())->toBe(0)
            ->and($brain->asked)->toBe(0);
    });

    it('says nothing when the question goes while it thinks, and still counts it', function () {
        config(['assistant.per_person_per_day' => 1]);
        Queue::fake([AnswerInChat::class]);
        $question = $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');
        thinkingBrain('Too late', fn () => app(ChatService::class)->remove($question));

        app(Assistant::class)->answer($question);

        expect($this->conversation->messages()->count())->toBe(0);
        thinkingBrain('Sure');
        $again = $this->chat->send($this->conversation, $this->ana, 'Where are the units?');
        app(Assistant::class)->answer($again);
        expect($this->conversation->messages()->where('from_assistant', true)->sole()->body)->toBe(__('chat.assistant_limit'));
    });

    it('never gives back a message of the day for one deleted', function () {
        config(['assistant.per_person_per_day' => 1]);
        $brain = thinkingBrain('Sure');
        $question = $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');

        deleteMessage($this, $this->ana, $question)->assertOk();
        $this->chat->send($this->conversation, $this->ana, 'Where are the units?');

        expect($this->conversation->messages()->where('from_assistant', true)->sole()->body)->toBe(__('chat.assistant_limit'))
            ->and($brain->asked)->toBe(1);

        // A new day, new messages
        $this->travel(1)->days();
        $this->chat->send($this->conversation, $this->ana, 'Where are the units?');
        expect($brain->asked)->toBe(2);
    });

    it('never gives back the deployment\'s messages of the day either', function () {
        config(['assistant.per_day' => 1]);
        $brain = thinkingBrain('Sure');
        deleteMessage($this, $this->ana, $this->chat->send($this->conversation, $this->ana, 'Where is the plan?'))->assertOk();

        $this->chat->send(assistantChatOf($this->beto, $this->project), $this->beto, 'Where are the units?');

        expect(assistantChatOf($this->beto, $this->project)->messages()->where('from_assistant', true)->sole()->body)->toBe(__('chat.assistant_busy'))
            ->and($brain->asked)->toBe(1);
    });

    it('takes nothing from the day for deleting what needed no model', function () {
        config(['assistant.per_person_per_day' => 1]);
        $brain = thinkingBrain('Sure');
        deleteMessage($this, $this->ana, $this->chat->send($this->conversation, $this->ana, 'Thanks'))->assertOk();

        $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');

        expect($brain->asked)->toBe(1)
            ->and($this->conversation->messages()->where('from_assistant', true)->sole()->body)->toBe('Sure');
    });

    it('keeps a report already sent when its question goes, and nothing of the conversation', function () {
        Queue::fake([AnswerInChat::class]);
        $question = $this->chat->send($this->conversation, $this->ana, 'The plan does not open on my phone');
        $answer = app(Assistant::class)->say($this->conversation, 'I can tell the team', [
            ['type' => 'report', 'label' => 'Send', 'message' => 'The plan does not open'],
        ]);
        $this->as($this->ana, $this->project)->postJson(route('chat.report', $answer), ['action' => 0])->assertOk();

        deleteMessage($this, $this->ana, $question)->assertOk();

        expect($this->conversation->messages()->count())->toBe(0)
            ->and(Report::sole()->body)->toBe('The plan does not open');
    });

    it('stops being possible after an hour, for the question and its answer', function () {
        thinkingBrain('Sure');
        $question = $this->chat->send($this->conversation, $this->ana, 'Where is the plan?');

        $this->travel(61)->minutes();
        deleteMessage($this, $this->ana, $question)->assertStatus(409);

        expect($this->conversation->messages()->count())->toBe(2);
    });

    it('works the same outside any project: deleted, never edited', function () {
        thinkingBrain('Sure');
        $luz = aUser(['firstname' => 'Luz']);
        $own = $this->chat->assistantFor($luz);
        $question = $this->chat->send($own, $luz, 'How do I join?');

        $this->as($luz)->putJson(route('account.chat.messages.update', $question), ['body' => 'Changed'])->assertForbidden();
        $this->as($luz)->deleteJson(route('account.chat.messages.destroy', $question))->assertOk();

        expect($own->messages()->count())->toBe(0);
    });
});
