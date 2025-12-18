<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Glpk;

/**
 * Balances lottery specifications to handle unequal family/unit counts.
 *
 * Handles two scenarios:
 * - More units than families: Prunes worst units to achieve balance
 * - More families than units: Adds mock units to achieve balance
 */
class SpecBalancer
{
    public function __construct(
        protected Glpk $glpk,
    ) {
        // ...
    }

    /**
     * Remove worst units to balance spec (more units than families).
     *
     * Strategy: two-step process to maintain max-min fairness:
     *  1. Heuristic pruning: Find minimal preference depth where enough units exist
     *  2.a. If exact match (#picked-units = #families), we're done
     *  2.b. Otherwise, use GLPK to prune worst units among the remaining candidates
     *
     * @return LotterySpec Balanced spec with same number of units as families
     */
    public function pruneWorstUnits(LotteryManifest $manifest, LotterySpec $spec): LotterySpec
    {
        // Step 1: Heuristic pruning - find minimal set of candidate units
        $preferredUnits = $this->collectTopPreferredUnits($spec);

        // Step 2: If there are still more units than families, use GLPK to prune further
        if (count($preferredUnits) > count($spec->families)) {
            $candidateSpec = new LotterySpec($spec->families, $preferredUnits);
            $worstRemainingUnits = $this->glpk->identifyWorstUnits($manifest, $candidateSpec);

            $preferredUnits = array_diff($preferredUnits, $worstRemainingUnits);
        }

        return new LotterySpec($spec->families, $preferredUnits);
    }

    /**
     * Add mock units to balance spec (more families than units).
     *
     * Each family will rank these fake units as 999 (worst possible).
     * This lets GLPK decide which families should lose fairly.
     *
     * @return LotterySpec Balanced spec with same number of units as families
     */
    public function addMockUnits(LotterySpec $spec): LotterySpec
    {
        $mockUnitsNeeded = count($spec->families) - count($spec->units);

        $mockUnits = [];
        for ($i = 1; $i <= $mockUnitsNeeded; $i++) {
            $mockUnits[] = 'MOCK_' . $i;
        }

        $paddedUnits = array_merge($spec->units, $mockUnits);
        $paddedFamilies = $this->appendMockUnitsToPreferences($spec->families, $mockUnits);

        return new LotterySpec($paddedFamilies, $paddedUnits);
    }

    /**
     * Check if a unit ID represents a mock unit (id = MOCK_*).
     */
    public function isMockUnit(string|int $unitId): bool
    {
        return is_string($unitId) && str_starts_with($unitId, 'MOCK_');
    }

    /**
     * Collect units that appear in families' top preferences.
     *
     * Algorithm: Start with top-1 choices, collect unique units. If fewer than M families,
     * expand to top-2, then top-3, etc. Stop when we have at least M unique units.
     *
     * Result: Returns Px units where M ≤ Px ≤ 2M-1
     *
     * @return array<int> Array of unit IDs
     */
    protected function collectTopPreferredUnits(LotterySpec $spec): array
    {
        $unitsCount = count($spec->units);
        $familyCount = count($spec->families);

        $depth = 0;
        $uniqueUnits = [];

        while (count($uniqueUnits) < $familyCount && $depth < $unitsCount) {
            // Collect preferences of level $depth from each family
            foreach ($spec->families as $preferences) {
                $uniqueUnits[$preferences[$depth]] = true;
            }

            $depth++;
        }

        $result = array_keys($uniqueUnits);
        sort($result);

        return $result;
    }

    /**
     * Add mock units to every family's preferences (will be given rank 999, i.e. very bad).
     *
     * @param array<int, array<int, int>> $families Original family preferences
     * @param array<string> $mockUnits Array of mock unit IDs
     * @return array<int, array<int, int|string>> Updated preferences with mock units appended
     */
    protected function appendMockUnitsToPreferences(array $families, array $mockUnits): array
    {
        $paddedFamilies = [];

        foreach ($families as $familyId => $preferences) {
            $paddedFamilies[$familyId] = $preferences;

            foreach ($mockUnits as $mockUnitId) {
                $paddedFamilies[$familyId][] = $mockUnitId;
            }
        }

        return $paddedFamilies;
    }
}
