<?php

namespace App\Events\Lottery;

use App\Services\Lottery\Contracts\ExecutorInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Enums\ExecutionType;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base event for lottery executions.
 */
abstract class LotteryExecuted
{
    use Dispatchable;

    public readonly int $project_id;
    public readonly int $lottery_id;
    public readonly string $executor;

    public function __construct(
        public readonly string $uuid,
        LotteryManifest $manifest,
        ExecutorInterface $executor,
        public readonly ExecutionResult $report,
    ) {
        $this->project_id = $manifest->projectId;
        $this->lottery_id = $manifest->lotteryId;
        $this->executor = get_class($executor);
    }

    /**
     * Get the execution type for this event.
     */
    abstract public function executionType(): ExecutionType;
}
