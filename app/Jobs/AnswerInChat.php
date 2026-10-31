<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Assistant\Assistant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The Assistant answers apart from the request: a moment's work, and the page is not kept waiting.
 */
class AnswerInChat implements ShouldQueue
{
    use Queueable;

    /** A second try would answer twice */
    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(public int $messageId)
    {
    }

    public function handle(Assistant $assistant): void
    {
        $message = Message::find($this->messageId);

        $message && $assistant->answer($message);
    }
}
