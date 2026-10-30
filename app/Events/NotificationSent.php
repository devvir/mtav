<?php

namespace App\Events;

use App\Enums\NotificationTarget;
use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pushes a new notification to the people it is addressed to, while they are online.
 *
 * @see routes/channels.php
 */
class NotificationSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public Notification $notification)
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [match ($this->notification->target) {
            NotificationTarget::PRIVATE => new PrivateChannel("users.{$this->notification->target_id}"),
            NotificationTarget::PROJECT => new PrivateChannel("projects.{$this->notification->target_id}"),
            NotificationTarget::GLOBAL  => new PrivateChannel('superadmins'),
        }];
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        // Only which one: each reader fetches it in their own language
        return ['id' => $this->notification->id];
    }
}
