<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;

/**
 * A project's chat is its people's: anyone with a place in the project (in a family, or
 * an admin) talks with any other, one on one or in groups. A conversation is read and
 * written only by those in it: nobody else, the project's admins and superadmins
 * included.
 */
class ConversationPolicy
{
    public function create(User $user, Project $project): bool
    {
        return (bool) $user->membershipIn($project);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->membershipIn($conversation->project_id) && $conversation->includes($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }

    /** A group is renamed, and grows, by anyone in it; one on one stays two */
    public function update(User $user, Conversation $conversation): bool
    {
        return $conversation->is_group && $this->view($user, $conversation);
    }

    public function leave(User $user, Conversation $conversation): bool
    {
        return $conversation->is_group && $conversation->includes($user);
    }

    /** A message is taken back by whoever wrote it */
    public function deleteMessage(User $user, Message $message): bool
    {
        return $message->user_id === $user->id && $this->view($user, $message->conversation);
    }
}
