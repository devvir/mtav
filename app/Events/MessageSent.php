<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A message, to everyone in its conversation (their own channels: nobody else hears it).
 * Only where it is: each page fetches what it shows.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param list<int> $participants */
    public function __construct(public Message $message, public array $participants)
    {
    }

    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel("users.{$id}"), $this->participants);
    }

    public function broadcastAs(): string
    {
        return 'message';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'message_id'      => $this->message->id,
            'by'              => $this->message->user_id,
            // Only those in the conversation hear it: who wrote, for the toast
            'author' => $this->message->author?->firstname,
        ];
    }
}
