<?php

namespace App\Events\Lottery;

use App\Services\Lottery\Enums\LotteryAuditType;

/**
 * Event dispatched after complete project-wide lottery execution.
 */
class ProjectLotteryExecuted extends LotteryExecuted
{
    public function executionType(): LotteryAuditType
    {
        return LotteryAuditType::PROJECT_EXECUTION;
    }
}
