<?php

namespace App\Events\Lottery;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base event for lottery executions.
 */
abstract class LotteryExecuted
{
    use Dispatchable;

    public readonly string $uuid;
    public readonly int $project_id;
    public readonly int $lottery_id;
    public readonly string $solver;

    public function __construct(
        LotteryManifest $manifest,
        SolverInterface $solver,
        public readonly ExecutionResult $report,
    ) {
        $this->uuid = $manifest->uuid;
        $this->project_id = $manifest->projectId;
        $this->lottery_id = $manifest->lotteryId;
        $this->solver = get_class($solver);
    }

    /**
     * Get the execution type for this event.
     */
    abstract public function executionType(): LotteryAuditType;
}
