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
        $participantCount = count($spec->participants);
        $itemCount = count($spec->items);

        return match (true) {
            $participantCount === $itemCount => $this->executeBalanced($manifest, $spec),
            $participantCount < $itemCount   => $this->executeMoreItems($manifest, $spec),
            $participantCount > $itemCount   => $this->executeMoreParticipants($manifest, $spec),
        };
    }

    /**
     * Execute balanced scenario (equal participants and items).
     */
    protected function executeBalanced(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $picks = $this->glpk->distributeItems($manifest, $spec);

        return new ExecutionResult($picks, [
            'participants' => [],
            'items'        => [],
        ]);
    }

    /**
     * Execute scenario with more items than participants.
     *
     *  1. Prune worst items until we're left with as many items as participants
     *  2. Distribute the best items as in the originally balanced scenario
     */
    protected function executeMoreItems(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $balancedSpec = $this->balancer->pruneWorstItems($manifest, $spec);
        $picks = $this->glpk->distributeItems($manifest, $balancedSpec);

        $orphans = array_values(array_diff($spec->items, $balancedSpec->items));

        return new ExecutionResult($picks, [
            'participants' => [],
            'items'        => $orphans,
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
