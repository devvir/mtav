<?php

namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\Glpk\Glpk;

/**
 * Solver that uses local GLPK (GNU Linear Programming Kit) for optimal lottery assignments.
 *
 * Implements max-min fairness optimization in two phases:
 * - Phase 1: Find minimum satisfaction level S (maximize worst-case satisfaction)
 * - Phase 2: Maximize overall satisfaction given constraint that no participant gets worse than S
 *
 * The models pair at most one item with each participant and at most one participant with each
 * item, as many pairs as the smaller side allows: with items to spare, the ones nobody receives
 * stay free; with participants to spare, which ones go without is decided by the same two
 * criteria. Whoever and whatever is left over goes on as leftovers.
 */
class GlpkSolver implements SolverInterface
{
    public function __construct(protected Glpk $glpk)
    {
    }

    /**
     * Execute lottery using GLPK optimization.
     *
     * @throws GlpkException if GLPK execution fails
     */
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $picks = $this->glpk->distributeItems($manifest, $spec);

        return new ExecutionResult($picks, [
            'participants' => array_values(array_diff(array_keys($spec->participants), array_keys($picks))),
            'items'        => array_values(array_diff($spec->items, $picks)),
        ]);
    }
}
