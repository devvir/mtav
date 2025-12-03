<?php

namespace App\Events\Lottery;

use App\Services\Lottery\Enums\LotteryAuditType;

/**
 * Event dispatched after a single lottery group execution.
 */
class GroupLotteryExecuted extends LotteryExecuted
{
    public function executionType(): LotteryAuditType
    {
        return LotteryAuditType::GROUP_EXECUTION;
    }
}
