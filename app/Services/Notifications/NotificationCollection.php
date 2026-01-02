<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;

class NotificationCollection extends Collection
{
    /**
     * Create a new collection.
     *
     * @param  Arrayable<TKey, TValue>|iterable<TKey, TValue>|null  $items
     */
    public function __construct($items = [])
    {
        parent::__construct($items);

        Auth::guest() || $this->withReadState(Auth::user());
    }

    /**
     * Efficiently load the readBy relation for a specific user across all notifications in the collection.
     * Uses a single query to check which notifications were read by the given user.
     *
     * After calling this method, each notification's readBy relation will contain either:
     * - A collection with the user (if read by them)
     * - An empty collection (if unread by them)
     */
    public function withReadState(User $user): self
    {
        /**
         * Abort if empty or called with arrays instead of Notification models (e.g. by pagination).
         */
        if ($this->isEmpty() || is_array(current($this->items))) {
            return $this;
        }

        $readIds = $this->notificationsReadByUser($user);

        return $this->each(
            fn (Notification $n) => $n->setAttribute('read', $readIds->contains($n->id))
        );
    }

    protected function notificationsReadByUser(User $user): BaseCollection
    {
        return NotificationRead::query()
            ->whereUserId($user->id)
            ->whereIn('notification_id', $this->pluck('id'))
            ->pluck('notification_id');
    }
}
