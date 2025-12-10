<?php

namespace App\Services\Lottery\Contracts;

use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Strategy interface for lottery solver algorithms.
 *
 * Implementations solve the mathematical assignment problem using various approaches:
 * - RandomSolver: Random assignment for development/testing
 * - TestSolver: Predictable assignments for unit tests
 * - GlpkSolver: Optimal assignments using GLPK mathematical optimization
 * - <ApiService>Solver: Calls external optimization service
 *
 * Solvers process ONE group at a time (one LotterySpec), usually linked to
 * one UnitType (may be mixed for distributing remnants from previous phases).
 */
interface SolverInterface
{
    /**
     * Solve the lottery assignment problem for a single group of families and units.
     *
     * @param LotteryManifest $manifest Full lottery manifest with project-wide configuration
     * @param LotterySpec $spec Group-specific families and units to solve
     */
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult;
}
