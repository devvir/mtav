<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\ProjectLotteryExecuted;
use App\Services\Lottery\ExecutionService;

/**
 * Handle the event that signals the completion of the Project' lottery execution.
 */
class ApplyLotteryResultsListener
{
    public function handle(ProjectLotteryExecuted $event, ExecutionService $service): void
    {
        $service->applyResults($event->lottery_id, $event->report->picks);
    }
}
