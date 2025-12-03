<?php

namespace App\Events\Lottery;

use App\Services\Lottery\DataObjects\LotteryManifest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Event dispatched when lottery execution begins.
 *
 * This event marks the clean boundary between high-level validation
 * (ExecutionService) and low-level execution (LotteryOrchestrator).
 */
class LotteryExecutionTriggered
{
    use Dispatchable;

    public function __construct(
        public LotteryManifest $manifest,
    ) {
        Log::info('Lottery execution triggered', ['manifest' => $manifest]);
    }
}
