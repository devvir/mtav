<?php

namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Exceptions\GlpkException;
use App\Services\Lottery\Glpk\Glpk;
use Illuminate\Support\Facades\Log;

/**
 * Solver that uses local GLPK (GNU Linear Programming Kit) for optimal lottery assignments.
 *
 * Implements max-min fairness optimization in two phases:
 * - Phase 1: Find minimum satisfaction level S (maximize worst-case satisfaction)
 * - Phase 2: Maximize overall satisfaction given constraint that no family gets worse than S
 */
class GlpkSolver implements SolverInterface
{
    public function __construct(protected Glpk $glpk)
    {
        // ...
    }

    /**
     * Execute lottery using GLPK optimization.
     *
     * @throws GlpkException if GLPK execution fails
     */
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        Log::info('GlpkSolver@execute', compact('spec'));

        $familyCount = count($spec->families);
        $unitCount = count($spec->units);

        return match (true) {
            $familyCount === $unitCount => $this->executeBalanced($spec),
            $familyCount < $unitCount   => $this->executeMoreUnits($spec),
            $familyCount > $unitCount   => $this->executeMoreFamilies($spec),
        };
    }

    /**
     * Execute balanced scenario (equal families and units).
     */
    protected function executeBalanced(LotterySpec $spec): ExecutionResult
    {
        Log::info('GlpkSolver@executeBalanced:started');

        $picks = $this->glpk->distributeUnits($spec);

        Log::info('GlpkSolver@executeBalanced:completed', compact('picks'));

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
    protected function executeMoreUnits(LotterySpec $spec): ExecutionResult
    {
        Log::info('GlpkSolver@executeMoreUnits:started');

        $filteredSpec = new LotterySpec(
            $spec->families,
            $this->preferredUnits($spec)
        );

        $picks = $this->glpk->distributeUnits($filteredSpec);
        $orphans = array_values(array_diff($spec->units, $filteredSpec->units));

        Log::info('GlpkSolver@executeMoreUnits:completed', compact('filteredSpec', 'picks', 'orphans'));

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
    protected function executeMoreFamilies(LotterySpec $spec): ExecutionResult
    {
        Log::info('GlpkSolver@executeMoreFamilies:started');

        $paddedSpec = $this->padWithMockUnits($spec);
        $paddedPicks = $this->glpk->distributeUnits($paddedSpec);

        foreach ($paddedPicks as $familyId => $unitId) {
            $this->isMockUnit($unitId)
                ? ($orphans[] = $familyId)          /** Matched with mock Units */
                : ($picks[$familyId] = $unitId);    /** Matched with real Units */
        }

        Log::info('GlpkSolver@executeMoreFamilies:completed', compact('paddedPicks', 'paddedSpec', 'picks', 'orphans'));

        return new ExecutionResult($picks, [
            'families' => $orphans,
            'units'    => [],
        ]);
    }

    /**
     * Check if a unit ID represents a mock unit (id = MOCK_*).
     */
    protected function isMockUnit(string|int $unitId): bool
    {
        return is_string($unitId) && str_starts_with($unitId, 'MOCK_');
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers for executeMoreUnits (more Units than Families)
    |--------------------------------------------------------------------------
    */

    /**
     * Strategy: two-step process to maintain max-min fairness:
     *  1. Heuristic pruning: Find minimal preference depth where enough units exist
     *  2.a. If exact match (#picked-units = #families), we're done
     *  2.b. Otherwise, use GLPK to prune worst units among the remaining candidates
     */
    protected function preferredUnits(LotterySpec $spec): array
    {
        // Step 1: Heuristic pruning - find minimal set of candidate units
        $preferredUnits = $this->findMinimalPreferenceSet($spec);

        // Step 2: If there are still more units than families, use GLPK to prune further
        if (count($preferredUnits) > count($spec->families)) {
            $candidateSpec = new LotterySpec($spec->families, $preferredUnits);
            $worstRemainingUnits = $this->identifyWorstUnitsViaGLPK($candidateSpec);

            $preferredUnits = array_diff($preferredUnits, $worstRemainingUnits);
        }

        return $preferredUnits;
    }

    /**
     * Find minimal set of units by iteratively expanding preference depth.
     *
     * Algorithm: Start with top-1 choices, collect unique units. If fewer than M families,
     * expand to top-2, then top-3, etc. Stop when we have at least M unique units.
     *
     * Result: Returns Px units where M ≤ Px ≤ 2M-1
     * - Best case: Px = M (exact match, very common in practice)
     * - Typical case: Px = M to M+5 (some preference overlap)
     * - Worst case: Px approaches 2M-1 (maximum preference divergence)
     *
     * @return array<int> Array of unit IDs
     */
    protected function findMinimalPreferenceSet(LotterySpec $spec): array
    {
        $familyCount = count($spec->families);
        $depth = 0;
        $uniqueUnits = [];

        do {
            $depth++;
            $uniqueUnits = [];

            // Collect first $depth preferences from each family
            foreach ($spec->families as $preferences) {
                for ($i = 0; $i < $depth && $i < count($preferences); $i++) {
                    $unitId = $preferences[$i];
                    $uniqueUnits[$unitId] = true;
                }
            }

        } while (count($uniqueUnits) < $familyCount && $depth < 100);

        $result = array_keys($uniqueUnits);
        sort($result);

        return $result;
    }

    /**
     * Use GLPK to identify worst units among candidates.
     *
     * This runs a GLPK optimization that selects exactly M units from candidates
     * while minimizing worst-case satisfaction. Units not selected are the worst.
     *
     * @return array<int> Array of unit IDs to discard
     */
    protected function identifyWorstUnitsViaGLPK(LotterySpec $spec): array
    {
        return $this->glpk->identifyWorstUnits($spec);
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers for executeMoreFamilies (more Families than Units)
    |--------------------------------------------------------------------------
    */

    /**
     * MoreFamilies scenario: Add fake units to balance families and units count.
     *
     * Each family will rank these fake units as 999 (worst possible).
     * This lets GLPK decide which families should lose fairly.
     */
    protected function padWithMockUnits(LotterySpec $spec): LotterySpec
    {
        $mockUnitsNeeded = count($spec->families) - count($spec->units);

        for ($i = 1; $i <= $mockUnitsNeeded; $i++) {
            $mockUnits[] = 'MOCK_' . $i;
        }

        $paddedUnits = array_merge($spec->units, $mockUnits);

        $paddedFamilies = $this->addMockUnitsToPreferences($spec->families, $mockUnits);

        return new LotterySpec($paddedFamilies, $paddedUnits);
    }

    /**
     * Add mock units to every family's preferences (will be given rank 999, i.e. very bad).
     *
     * @param array<int, array<int, int>> $families Original family preferences
     * @param array<int> $mockUnits Array of mock unit IDs
     * @return array<int, array<int, int>> Updated preferences
     */
    protected function addMockUnitsToPreferences(array $families, array $mockUnits): array
    {
        foreach ($families as $familyId => $preferences) {
            $paddedFamilies[$familyId] = $preferences;

            foreach ($mockUnits as $mockUnitId) {
                $paddedFamilies[$familyId][] = $mockUnitId;
            }
        }

        return $paddedFamilies ?? [];
    }
}
