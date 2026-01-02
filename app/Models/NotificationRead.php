<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class NotificationRead extends Pivot
{
    protected $table = 'notification_read';

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Notification that was read.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * User who read the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark multiple notifications as read by a user (batch operation).
     */
    public static function markManyAsReadBy(Collection $notifications, User $user): void
    {
        $records = $notifications->map(fn (Notification $notification) => [
            'notification_id' => $notification->id,
            'user_id'         => $user->id,
            'read_at'         => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ])->all();

        static::insertOrIgnore($records);
    }
}
