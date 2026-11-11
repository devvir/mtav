<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A message edited or deleted, to everyone in its conversation: pages showing it ask again.
 * Only where it was travels, never what it said (or says now).
 */
class MessageChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param list<int> $participants */
    public function __construct(public int $conversationId, public ?int $by, public array $participants, public bool $removed)
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
        return 'message-changed';
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'by' => $this->by, 'removed' => $this->removed];
    }
}
