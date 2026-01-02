<?php

namespace App\Models\Concerns;

use App\Enums\NotificationTarget;
use App\Models\Notification;
use App\Services\Notifications\NotificationCollection;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \App\Models\User
 */
trait HasNotifications
{
    public const RECENT_NOTIFICATIONS_COUNT = 4;

    /**
     * Query builder for all notifications visible to this user (private, project, and global).
     */
    public function notifications(): Builder
    {
        return Notification::forUser($this);
    }

    /**
     * Fetch recent notifications for this user.
     */
    public function recentNotifications(int $limit = self::RECENT_NOTIFICATIONS_COUNT): NotificationCollection
    {
        return $this->notifications()->limit($limit)->get();
    }

    /**
     * Private-channel notifications for this user.
     */
    public function privateNotifications(): Builder
    {
        return Notification::forUser($this)->whereTarget(NotificationTarget::PRIVATE);
    }

    /**
     * Query builder for all notifications visible to this user (private, project, and global).
     */
    public function projectNotifications(): Builder
    {
        return Notification::forUser($this)->whereTarget(NotificationTarget::PROJECT);
    }

    /**
     * Private-channel notifications for this user.
     */
    public function globalNotifications(): Builder
    {
        return Notification::forUser($this)->whereTarget(NotificationTarget::GLOBAL);
    }

    /**
     * Query builder for notifications read by this user.
     */
    public function readNotifications(): Builder
    {
        return $this->notifications()->readBy($this);
    }

    /**
     * Query builder for notifications NOT read by this user.
     */
    public function unreadNotifications(): Builder
    {
        return $this->notifications()->unreadBy($this);
    }

    /**
     * Pseudo-attributees to make the Builder functions act more like Relations.
     */

    public function getNotificationsAttribute(): NotificationCollection
    {
        return $this->notifications()->get();
    }

    public function getPrivateNotificationsAttribute(): NotificationCollection
    {
        return $this->privateNotifications()->get();
    }

    public function getProjectNotificationsAttribute(): NotificationCollection
    {
        return $this->projectNotifications()->get();
    }

    public function getGlobalNotificationsAttribute(): NotificationCollection
    {
        return $this->globalNotifications()->get();
    }

    public function getReadNotificationsAttribute(): NotificationCollection
    {
        return $this->readNotifications()->get();
    }

    public function getUnreadNotificationsAttribute(): NotificationCollection
    {
        return $this->unreadNotifications()->get();
    }
}
