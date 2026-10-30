<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Jobs\PushMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use App\Services\Push\PushService;
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

        return DB::transaction(function () use ($project, $by, $others, $title) {
            $group = count($others) > 1;
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

        MessageSent::dispatch($message, $conversation->participants()->pluck('users.id')->all());
        if (PushService::enabled()) {
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
     * A group's name, and more people in it
     *
     * @param list<int> $people
     */
    public function update(Conversation $conversation, ?string $title, array $people = []): void
    {
        $conversation->update(['title' => $title ?: null]);
        if ($people) {
            $conversation->participants()->syncWithoutDetaching($this->ofProject($conversation->project, $people));
        }
    }

    /** Leaving a group; the last to leave takes it with them */
    public function leave(Conversation $conversation, User $user): void
    {
        $conversation->participants()->detach($user->id);
        if (! $conversation->participants()->exists()) {
            $conversation->delete();
        }
    }

    /** A message taken back: the conversation then ends on the one before */
    public function remove(Message $message): void
    {
        $conversation = $message->conversation;
        $message->delete();
        $last = $conversation->messages()->latest('id')->first();
        $conversation->forceFill(['last_message_id' => $last?->id, 'last_message_at' => $last?->created_at])->save();
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
