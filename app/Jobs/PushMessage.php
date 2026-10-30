<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Push\PushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** A chat message, to the devices of the others in its conversation, in the background */
class PushMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $messageId)
    {
    }

    public function handle(PushService $push): void
    {
        ($message = Message::find($this->messageId)) && $push->deliverMessage($message);
    }
}
