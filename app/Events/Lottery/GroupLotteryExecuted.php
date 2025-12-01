<?php

namespace App\Events\Lottery;

use App\Services\Lottery\Enums\ExecutionType;

/**
 * Event dispatched after a single lottery group execution.
 */
class GroupLotteryExecuted extends LotteryExecuted
{
    public function executionType(): ExecutionType
    {
        return ExecutionType::GROUP;
    }
}
