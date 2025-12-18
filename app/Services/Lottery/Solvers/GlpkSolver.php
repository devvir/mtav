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
 * - Phase 2: Maximize overall satisfaction given constraint that no family gets worse than S
 */
class GlpkSolver implements SolverInterface
{
    public function __construct(
        protected Glpk $glpk,
        protected SpecBalancer $balancer,
    ) {
        // ...
    }

    /**
     * Execute lottery using GLPK optimization.
     *
     * @throws GlpkException if GLPK execution fails
     */
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $familyCount = count($spec->families);
        $unitCount = count($spec->units);

        return match (true) {
            $familyCount === $unitCount => $this->executeBalanced($manifest, $spec),
            $familyCount < $unitCount   => $this->executeMoreUnits($manifest, $spec),
            $familyCount > $unitCount   => $this->executeMoreFamilies($manifest, $spec),
        };
    }

    /**
     * Execute balanced scenario (equal families and units).
     */
    protected function executeBalanced(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $picks = $this->glpk->distributeUnits($manifest, $spec);

        return new ExecutionResult($picks, [
            'families' => [],
            'units'    => [],
        ]);
    }

    /**
     * Execute scenario with more units than families.
     *
     *  1. Prune worst units until we're left with as many units as families
     *  2. Distribute the best units as in the originally balanced scenario
     */
    protected function executeMoreUnits(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $balancedSpec = $this->balancer->pruneWorstUnits($manifest, $spec);
        $picks = $this->glpk->distributeUnits($manifest, $balancedSpec);

        $orphans = array_values(array_diff($spec->units, $balancedSpec->units));

        return new ExecutionResult($picks, [
            'families' => [],
            'units'    => $orphans,
        ]);
    }

    /**
     * Execute scenario with more families than units.
     *
     * Strategy: Add fake units to balance the problem, then filter them out.
     */
    protected function executeMoreFamilies(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        $balancedSpec = $this->balancer->addMockUnits($spec);
        $picks = $this->glpk->distributeUnits($manifest, $balancedSpec);

        foreach ($picks as $familyId => $unitId) {
            if ($this->balancer->isMockUnit($unitId)) {
                $orphans[] = $familyId;
            } else {
                $realPicks[$familyId] = $unitId;
            }
        }

        return new ExecutionResult($realPicks ?? [], [
            'families' => $orphans ?? [],
            'units'    => [],
        ]);
    }
}
