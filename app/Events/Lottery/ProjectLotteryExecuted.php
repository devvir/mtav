<?php

namespace App\Events\Lottery;

use App\Services\Lottery\Enums\ExecutionType;

/**
 * Event dispatched after complete project-wide lottery execution.
 */
class ProjectLotteryExecuted extends LotteryExecuted
{
    public function executionType(): ExecutionType
    {
        return ExecutionType::PROJECT;
    }
}
