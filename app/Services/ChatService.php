<?php

namespace App\Services;

use App\Events\MessageChanged;
use App\Events\MessageSent;
use App\Jobs\AnswerInChat;
use App\Jobs\PushMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use App\Services\Assistant\Assistant;
use App\Services\Push\PushService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A project's chat: starting conversations (two people have one; a group, as many as its
 * people want), writing in them, and what each person has read. A message reaches the
 * others at once (their pages, and their devices where they allowed it).
 */
class ChatService
{
    /**
     * With one other person, their conversation (started, or the one they already have);
     * with more, a new group
     *
     * @param list<int> $people  the others, all of the project
     *
     * @throws ValidationException when someone is not of the project
     */
    public function start(Project $project, User $by, array $people, ?string $title = null): Conversation
    {
        $others = $this->ofProject($project, array_diff($people, [$by->id]));

        if (count($others) === 1) {
            $pair = Conversation::pairOf($by->id, $others[0]);
            $existing = Conversation::where('project_id', $project->id)->where('pair', $pair)->first();
            if ($existing) {
                return $existing;
            }
        }

        $group = count($others) > 1;
        if ($group) {
            $this->ensureRoomForAGroup($project, [$by->id, ...$others], $by);
        }

        try {
            return DB::transaction(function () use ($project, $by, $others, $title, $group) {
                $conversation = new Conversation(['title' => $group ? ($title ?: null) : null]);
                $conversation->forceFill([
                    'project_id' => $project->id,
                    'created_by' => $by->id,
                    'is_group'   => $group,
                    'pair'       => $group ? null : Conversation::pairOf($by->id, $others[0]),
                ])->save();
                $conversation->participants()->attach([$by->id, ...$others]);

                return $conversation;
            });
        } catch (UniqueConstraintViolationException $taken) {
            // Both of them just started it: theirs is the one that got there first
            return Conversation::where('project_id', $project->id)->where('pair', Conversation::pairOf($by->id, $others[0]))->first() ?? throw $taken;
        }
    }

    /**
     * Their conversation with the Assistant where joining gave them none: outside any project,
     * or in one a support account looks after. One, started when first wanted.
     */
    public function assistantFor(User $user, ?Project $project = null): Conversation
    {
        $mine = fn () => Conversation::where('project_id', $project?->id)->where('pair', "assistant:{$user->id}")->first();

        // A null project repeats under the unique key: two first visits at once take turns
        return $mine() ?? DB::transaction(function () use ($user, $project, $mine) {
            User::whereKey($user->id)->lockForUpdate()->first();
            if ($found = $mine()) {
                return $found;
            }

            $conversation = (new Conversation())->forceFill(['project_id' => $project?->id, 'is_assistant' => true, 'pair' => "assistant:{$user->id}"]);
            $conversation->save();
            $conversation->participants()->attach($user->id, ['pinned_at' => now()]);

            return $conversation;
        });
    }

    public function send(Conversation $conversation, User $author, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $author, $body) {
            $message = new Message(['body' => $body]);
            $message->forceFill(['conversation_id' => $conversation->id, 'user_id' => $author->id])->save();
            $conversation->forceFill(['last_message_id' => $message->id, 'last_message_at' => $message->created_at])->save();
            // What one writes, one has read
            $conversation->participants()->updateExistingPivot($author->id, ['read_up_to' => $message->id]);

            return $message;
        });

        MessageSent::dispatch($message, $conversation->audience()->pluck('users.id')->all());
        if ($conversation->is_assistant) {
            AnswerInChat::dispatch($message->id);
        } elseif (PushService::enabled()) {
            PushMessage::dispatch($message->id);
        }

        return $message;
    }

    /** Everything in it, read by this person up to now */
    public function read(Conversation $conversation, User $user): void
    {
        if ($conversation->last_message_id) {
            $conversation->participants()->updateExistingPivot($user->id, ['read_up_to' => $conversation->last_message_id]);
        }
    }

    /**
     * A group's name (with none given, it stays as it is), and more people in it. All of it
     * or, someone being refused, none.
     *
     * @param array{title?: ?string, people?: list<int>} $changes
     */
    public function update(Conversation $conversation, array $changes): void
    {
        $added = [];
        if ($changes['people'] ?? []) {
            $added = array_values(array_diff(
                $this->ofProject($conversation->project, $changes['people']),
                $conversation->participants()->pluck('users.id')->all(),
            ));
            $this->ensureRoomForAGroup($conversation->project, $added);
        }

        DB::transaction(function () use ($conversation, $changes, $added) {
            if (array_key_exists('title', $changes)) {
                $conversation->update(['title' => $changes['title'] ?: null]);
            }
            $conversation->participants()->syncWithoutDetaching($added);
        });
    }

    /** Pinned, it stays at the top of that person's list */
    public function pin(Conversation $conversation, User $user, bool $pinned): void
    {
        $conversation->participants()->updateExistingPivot($user->id, ['pinned_at' => $pinned ? now() : null]);
    }

    /** Leaving a group; whoever remains may bring others in, the last to leave takes it along */
    public function leave(Conversation $conversation, User $user): void
    {
        $conversation->participants()->detach($user->id);
        if (! $conversation->participants()->exists()) {
            $conversation->delete();
        }
    }

    /**
     * A message reworded by whoever wrote it: the same message for everyone, nothing kept of
     * what it said before, no mark that it changed (its time stays), nobody notified. What
     * each person read stays read.
     */
    public function edit(Message $message, string $body): void
    {
        $edited = DB::transaction(function () use ($message, $body) {
            // Held, as a delete holds it: one that went meanwhile is not written again
            $current = Message::whereKey($message->id)->lockForUpdate()->first();
            if (! $current || $current->body === $body) {
                return false;
            }

            $current->timestamps = false;
            $current->forceFill(['body' => $body])->save();

            return true;
        });
        if (! $edited) {
            return;
        }

        MessageChanged::dispatch($message->conversation_id, $message->user_id, $message->conversation->audience()->pluck('users.id')->all(), false);
    }

    /**
     * A message deleted, leaving no trace: gone for everyone, the conversation ending on the one
     * before. In the Assistant's conversation its answer goes with it, and what it took from the
     * day's messages stays taken.
     */
    public function remove(Message $message): void
    {
        $removed = DB::transaction(function () use ($message) {
            // Held: an answer being written to it waits, and then finds it gone
            $message = Message::whereKey($message->id)->lockForUpdate()->first();
            if (! $message) {
                return null;
            }
            $assistant = $message->conversation->is_assistant;
            $gone = [$message->id, ...($assistant ? $this->answerTo($message) : [])];
            // Its last message as it is now: someone writing meanwhile is not undone
            $conversation = Conversation::whereKey($message->conversation_id)->lockForUpdate()->first();

            if ($assistant) {
                app(Assistant::class)->keepCounted($message);
            }
            Message::whereKey($gone)->delete();

            if (in_array($conversation->last_message_id, $gone, true)) {
                $last = $conversation->messages()->latest('id')->first();
                $conversation->forceFill(['last_message_id' => $last?->id, 'last_message_at' => $last?->created_at])->save();
            }

            return $conversation;
        });

        $removed && MessageChanged::dispatch($removed->id, $message->user_id, $removed->audience()->pluck('users.id')->all(), true);
    }

    /**
     * The Assistant's answer to someone's message: what it said next. Messages written in a row
     * are answered once, so the answer is to each of them: it could repeat any of them.
     *
     * @return list<int>
     */
    private function answerTo(Message $question): array
    {
        if ($question->from_assistant) {
            return [];
        }

        $after = Message::where('conversation_id', $question->conversation_id)->where('id', '>', $question->id)->orderBy('id');
        $first = (clone $after)->where('from_assistant', true)->lockForUpdate()->value('id');
        if (! $first) {
            return [];
        }
        $next = (clone $after)->where('id', '>', $first)->where('from_assistant', false)->value('id');

        return (clone $after)->where('from_assistant', true)->where('id', '>=', $first)
            ->when($next, fn ($answer) => $answer->where('id', '<', $next))
            ->lockForUpdate()->pluck('id')->all();
    }

    /**
     * Nobody ends up in more groups than there can be: someone starting one is told to leave
     * another, and someone at the limit cannot be brought in
     *
     * @param list<int> $people
     *
     * @throws ValidationException
     */
    private function ensureRoomForAGroup(Project $project, array $people, ?User $starting = null): void
    {
        $max = config('limits.chat_groups_per_person');
        $full = User::whereIn('id', $people)
            ->whereHas('conversations', fn ($q) => $q->where('project_id', $project->id)->where('is_group', true)->where('is_community', false), '>=', $max)
            ->get();

        if ($starting && $full->contains($starting)) {
            throw ValidationException::withMessages(['people' => __('validation.chat_group_limit', ['max' => $max])]);
        }
        if ($full->isNotEmpty()) {
            throw ValidationException::withMessages(['people' => __('validation.chat_group_limit_other', ['name' => $full->first()->name, 'max' => $max])]);
        }
    }

    /**
     * @param array<int> $people
     *
     * @return list<int>
     */
    private function ofProject(Project $project, array $people): array
    {
        $people = array_values(array_unique(array_map('intval', $people)));
        $found = $project->members()->whereIn('users.id', $people)->pluck('users.id')->all();

        if (! $people || count($found) !== count($people)) {
            throw ValidationException::withMessages(['people' => __('validation.chat_people')]);
        }

        return $found;
    }
}
