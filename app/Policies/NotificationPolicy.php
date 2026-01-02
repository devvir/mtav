<?php

namespace App\Policies;

use App\Enums\NotificationTarget;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the notification.
     */
    public function view(User $user, Notification $notification): bool
    {
        return match($notification->target) {
            /** Private User channel: only available to the receipient */
            NotificationTarget::PRIVATE => $notification->target_id === $user->id,

            /** Project channel: only if User has access to Project (handled by Project scope) */
            NotificationTarget::PROJECT => Project::whereId($notification->target_id)->exists(),

            /** Global Notifications: only for Admins who manage multiple projects */
            default => $user->asAdmin()?->projects()->count() > 1,
        };
    }

    /**
     * Notifications cannot be created manually.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Notifications cannot be updated.
     */
    public function update(User $user, Notification $notification): bool
    {
        return false;
    }

    /**
     * Notifications cannot be deleted manually.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return false;
    }

    /**
     * Notifications cannot be restored.
     */
    public function restore(User $user, Notification $notification): bool
    {
        return false;
    }
}
