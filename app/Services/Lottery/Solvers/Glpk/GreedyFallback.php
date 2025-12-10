<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use Illuminate\Support\Facades\Log;

/**
 * Greedy fallback algorithm for degenerate lottery cases.
 *
 * This is NOT a public solver; it's an internal fallback used by GlpkSolver
 * when degeneracy is detected. It uses a fair greedy algorithm with randomized
 * family ordering to provide a good (but not optimal) approximation very quickly.
 *
 * Algorithm:
 * 1. Randomize family processing order
 * 2. For each family, assign the highest-ranked available unit
 * 3. Mark unit as assigned
 * 4. Repeat until all families assigned or no units remain
 *
 * Fairness:
 * - Each iteration, the next family gets the highest-ranked available unit
 * - Randomization prevents earlier/later families getting systematic advantage
 * - Max-min fairness is approximated (all families tend to get similarly-ranked units)
 *
 * Performance: O(n log n) - extremely fast, suitable for any problem size
 *
 * Guarantees:
 * - Every family gets a unit (if available)
 * - Every family gets their highest-ranked available unit at assignment time
 * - Fair distribution despite randomization
 */
class GreedyFallback implements SolverInterface
{
    /**
     * Execute greedy lottery assignment.
     *
     * @param LotteryManifest $_ Full lottery manifest (unused for greedy)
     * @param LotterySpec $spec Families and units to assign
     * @return ExecutionResult Picks and orphans
     */
    public function execute(LotteryManifest $_, LotterySpec $spec): ExecutionResult
    {
        Log::info('GreedyFallback@execute:started', [
            'family_count' => $spec->familyCount(),
            'unit_count'   => $spec->unitCount(),
        ]);

        $picks = $this->assignFamilies($spec);

        $orphans = $this->calculateOrphans($spec, $picks);

        Log::info('GreedyFallback@execute:completed', compact('picks', 'orphans'));

        return new ExecutionResult($picks, $orphans);
    }

    /**
     * Assign units to families using fair greedy algorithm.
     *
     * @param LotterySpec $spec Lottery specification
     * @return array<int, int> Family ID => Unit ID assignments
     */
    private function assignFamilies(LotterySpec $spec): array
    {
        $availableUnits = array_flip($spec->units); // unit_id => true for O(1) lookup
        $picks = [];

        // Randomize family processing order for fairness
        $familyIds = array_keys($spec->families);
        shuffle($familyIds);

        foreach ($familyIds as $familyId) {
            $preferences = $spec->families[$familyId];

            // Find first available unit in this family's preferences
            foreach ($preferences as $unitId) {
                if (isset($availableUnits[$unitId])) {
                    $picks[$familyId] = $unitId;
                    unset($availableUnits[$unitId]);
                    break;
                }
            }
        }

        return $picks;
    }

    /**
     * Calculate orphaned families and units.
     *
     * @param LotterySpec $spec Original specification
     * @param array<int, int> $picks Family assignments
     * @return array<string, array<int>> Orphaned families and units
     */
    private function calculateOrphans(LotterySpec $spec, array $picks): array
    {
        // Families without assignments (should not happen if units >= families)
        $assignedFamilies = array_keys($picks);
        $orphanFamilies = array_diff(array_keys($spec->families), $assignedFamilies);

        // Units not assigned (should not happen if families >= units)
        $assignedUnits = array_values($picks);
        $orphanUnits = array_diff($spec->units, $assignedUnits);

        return [
            'families' => array_values($orphanFamilies),
            'units'    => array_values($orphanUnits),
        ];
    }
}
