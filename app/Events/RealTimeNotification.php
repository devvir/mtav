<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base abstract class for real-time notification events.
 *
 * All events meant to be presented as user notifications must extend this class
 * and implement the message() method to provide user-facing text.
 */
abstract class RealTimeNotification implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Get the user-facing notification message.
     * This will be shown in the notifications UI.
     */
    abstract public function message(): string;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    abstract public function broadcastOn(): array;

    /**
     * The event's broadcast name.
     */
    abstract public function broadcastAs(): string;

    /**
     * Get the data to broadcast.
     */
    abstract public function broadcastWith(): array;
}
