<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\GroupLotteryExecuted;
use App\Events\Lottery\ProjectLotteryExecuted;
use App\Services\Lottery\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Generate Lottery audits for both group and project wide Lottery executions.
 */
class LotteryExecutedListener implements ShouldQueue
{
    public function handle(GroupLotteryExecuted|ProjectLotteryExecuted $event): void
    {
        app(AuditService::class)->audit(
            type: $event->executionType(),
            execution_uuid: $event->uuid,
            project_id: $event->project_id,
            lottery_id: $event->lottery_id,
            result: $event->report
        );
    }
}
