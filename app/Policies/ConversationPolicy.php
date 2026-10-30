<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;

/**
 * A project's chat is its people's: anyone with a place in the project (in a family, an
 * admin or a guest) talks with any other, one on one or in groups. A conversation is read and
 * written only by those in it: nobody else, the project's admins and superadmins
 * included (its abilities are personal: superadmins do not pass them by default).
 */
class ConversationPolicy
{
    public function startChat(User $user, Project $project): bool
    {
        return (bool) $user->membershipIn($project);
    }

    public function readChat(User $user, Conversation $conversation): bool
    {
        return $user->membershipIn($conversation->project_id) && $conversation->includes($user);
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

    /** A message is taken back by whoever wrote it */
    public function unsendMessage(User $user, Message $message): bool
    {
        return $message->user_id === $user->id && $this->readChat($user, $message->conversation);
    }
}
