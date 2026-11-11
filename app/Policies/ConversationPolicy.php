<?php

namespace App\Policies;

use App\Enums\ProjectFeature;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A project's chat is its people's: anyone with a place in the project (in a family, an
 * admin or a guest) talks with any other, one on one or in groups. A conversation is read and
 * written only by those in it: nobody else, the project's admins and superadmins
 * included (its abilities are personal: superadmins do not pass them by default).
 */
class ConversationPolicy
{
    /**
     * Its people talk; a support account too, to try it out (it is nobody's place in it).
     * Outside any project, anyone has the Assistant.
     */
    public function startChat(User $user, ?Project $project = null): bool
    {
        return ! $project || (bool) $user->membershipIn($project) || $user->isSuperadmin();
    }

    public function readChat(User $user, Conversation $conversation): bool
    {
        // Of no project: someone's own with the Assistant
        if (! $conversation->project_id) {
            return $conversation->is_assistant && $conversation->includes($user);
        }

        // With the Assistant off, its conversations are not there
        if ($conversation->is_assistant && ! $conversation->project->hasFeature(ProjectFeature::ASSISTANT)) {
            return false;
        }

        return ($user->membershipIn($conversation->project_id) || $user->isSuperadmin()) && $conversation->includes($user);
    }

    public function writeChat(User $user, Conversation $conversation): bool
    {
        return $this->readChat($user, $conversation);
    }

    /** A group is renamed, and grows, by anyone in it; one on one stays two, Community all */
    public function manageGroup(User $user, Conversation $conversation): bool
    {
        return $conversation->is_group && ! $conversation->is_community && $this->readChat($user, $conversation);
    }

    public function leaveGroup(User $user, Conversation $conversation): bool
    {
        return $conversation->is_group && ! $conversation->is_community && $conversation->includes($user);
    }

    /**
     * A message is deleted by whoever wrote it, while they are still in its conversation, and only
     * for a while after sending it: past that, it stays as it is, said so (409)
     */
    public function unsendMessage(User $user, Message $message): Response|bool
    {
        if ($message->from_assistant || $message->user_id !== $user->id || ! $this->readChat($user, $message->conversation)) {
            return false;
        }

        return $message->stillChangeable() ? true : Response::denyWithStatus(409, __('chat.change_too_late', ['minutes' => config('limits.chat_changes_minutes')]));
    }

    /**
     * Edited on the same terms; never with the Assistant, whose answer would stay as it was
     */
    public function editMessage(User $user, Message $message): Response|bool
    {
        return $message->conversation->is_assistant ? false : $this->unsendMessage($user, $message);
    }
}
