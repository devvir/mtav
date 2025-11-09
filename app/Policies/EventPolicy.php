<?php

namespace App\Policies;

use App\Enums\EventType;
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
        return $event->is_published || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin() && $event->type !== EventType::LOTTERY;
    }

    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
