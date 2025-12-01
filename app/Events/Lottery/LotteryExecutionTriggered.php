<?php

namespace App\Events\Lottery;

use App\Services\Lottery\DataObjects\LotteryManifest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when lottery execution begins.
 *
 * This event marks the clean boundary between high-level validation
 * (ExecutionService) and low-level execution (LotteryOrchestrator).
 */
class LotteryExecutionTriggered implements ShouldQueue
{
    use Dispatchable;

    public function __construct(
        public LotteryManifest $manifest,
    ) {
        // ...
    }
}
