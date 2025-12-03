<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\ProjectLotteryExecuted;
use App\Services\Lottery\ExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Handle the event that signals the completion of the Project' lottery execution.
 */
class ApplyLotteryResultsListener implements ShouldQueue
{
    public function handle(ProjectLotteryExecuted $event): void
    {
        $service = resolve(ExecutionService::class);

        $service->applyResults($event->lottery_id, $event->report->picks);
    }
}
