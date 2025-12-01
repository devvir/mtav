<?php

namespace App\Services\Lottery\Contracts;

use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Strategy interface for lottery execution algorithms.
 *
 * Implementations handle the actual assignment logic using various approaches:
 * - RandomExecutor: Random assignment for development/testing
 * - TestExecutor: Predictable assignments for unit tests
 * - <ApiService>Executor: Calls external optimization service
 *
 * Executors process ONE group at a time (one LotterySpec), usually linked to
 * one UnitType (may be mixed for distributing remnant from previous phases).
 */
interface ExecutorInterface
{
    /**
     * Execute lottery for a single group of families and units (e.g. by unit type).
     */
    public function execute(LotterySpec $spec): ExecutionResult;
}
