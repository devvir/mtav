<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something changed in an open lottery: pages open on it ask again. Carries nothing, since its
 * channel is as public as its link.
 */
class OpenLotteryChanged implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(public string $slug)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel("open-lottery.{$this->slug}");
    }

    /** Apart from what takes long: it reaches people at once */
    public function broadcastQueue(): string
    {
        return 'realtime';
    }

    public function broadcastAs(): string
    {
        return 'changed';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
