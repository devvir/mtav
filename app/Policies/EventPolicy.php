<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return $event->isPublished() || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() && ($event->isPublished() || ! $event->isLottery());
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin() && ! $event->isLottery();
    }

    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
