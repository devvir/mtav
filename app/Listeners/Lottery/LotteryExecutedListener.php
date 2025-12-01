<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\LotteryExecuted;
use App\Services\Lottery\LotteryAuditService;

/**
 * Listener for lottery execution events.
 * Handles both group and project executions for audit trail.
 */
class LotteryExecutedListener
{
    public function handle(LotteryExecuted $event): void
    {
        app(LotteryAuditService::class)->audit(
            type: $event->executionType(),
            execution_uuid: $event->uuid,
            project_id: $event->project_id,
            lottery_id: $event->lottery_id,
            result: $event->report
        );
    }
}
