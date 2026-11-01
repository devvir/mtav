<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A message taken back, to everyone in its conversation: pages showing it ask again. It is
 * gone by now, so only where it was travels.
 */
class MessageRemoved implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param list<int> $participants */
    public function __construct(public int $conversationId, public ?int $by, public array $participants)
    {
    }

    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel("users.{$id}"), $this->participants);
    }

    /** Apart from what takes long: it reaches people at once */
    public function broadcastQueue(): string
    {
        return 'realtime';
    }

    public function broadcastAs(): string
    {
        return 'message-removed';
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'by' => $this->by];
    }
}
