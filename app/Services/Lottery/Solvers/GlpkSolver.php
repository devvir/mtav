<?php

namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\Glpk\Glpk;
use App\Services\Lottery\Solvers\Glpk\SpecBalancer;

/**
 * Solver that uses local GLPK (GNU Linear Programming Kit) for optimal lottery assignments.
 *
 * Implements max-min fairness optimization in two phases:
 * - Phase 1: Find minimum satisfaction level S (maximize worst-case satisfaction)
 * - Phase 2: Maximize overall satisfaction given constraint that no participant gets worse than S
 */
class GlpkSolver implements SolverInterface
{
    public function __construct(
        protected Glpk $glpk,
        protected SpecBalancer $balancer,
    ) {
    }

    /**
     * Execute lottery using GLPK optimization.
     *
     * @throws GlpkException if GLPK execution fails
     */
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        return $spec->participantCount() > $spec->itemCount()
            ? $this->executeMoreParticipants($manifest, $spec)
            : $this->executeAssignment($manifest, $spec);
    }

    /**
     * As many items as participants, or more: the models give every participant one item and
     * each item to at most one participant, so the items nobody receives stay free, as leftovers.
     */
    protected function executeAssignment(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $picks = $this->glpk->distributeItems($manifest, $spec);

        return new ExecutionResult($picks, [
            'participants' => [],
            'items'        => array_values(array_diff($spec->items, $picks)),
        ]);
    }

    /**
     * Execute scenario with more participants than items.
     *
     * Strategy: Add fake items to balance the problem, then filter them out.
     */
    protected function executeMoreParticipants(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $balancedSpec = $this->balancer->addMockItems($spec);

        $picks = $this->glpk->distributeItems($manifest, $balancedSpec);

        foreach ($picks as $participantId => $itemId) {
            if ($this->balancer->isMockItem($itemId)) {
                $orphans[] = $participantId;
            } else {
                $realPicks[$participantId] = $itemId;
            }
        }

        return new ExecutionResult($realPicks ?? [], [
            'participants' => $orphans ?? [],
            'items'        => [],
        ]);
    }
}
