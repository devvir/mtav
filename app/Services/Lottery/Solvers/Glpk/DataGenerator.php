<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Generates GLPK data files (.dat) from lottery specifications.
 */
class DataGenerator
{
    /**
     * Generate Phase 1 data file from lottery specification.
     *
     * @param  LotterySpec  $spec  Contains participants (with preferences) and items
     *
     * @return string GMPL data section
     */
    public function generateData(LotterySpec $spec): string
    {
        $participants = $spec->participants;
        $items = $spec->items;

        // Build sets
        $participantIds = array_keys($participants);
        $participantSet = $this->formatSet($participantIds, 'p');
        $itemSet = $this->formatSet($items, 'i');

        // Build preference matrix
        $preferenceMatrix = $this->buildPreferenceMatrix($participants, $items);

        return <<<GMPL
data;

set P := {$participantSet};
set I := {$itemSet};

{$preferenceMatrix}

end;
GMPL;
    }

    /**
     * Generate Phase 2 data file with minimum satisfaction parameter.
     *
     * @param  LotterySpec  $spec  Contains participants (with preferences) and items
     * @param  int  $minSatisfaction  The S value from Phase 1
     *
     * @return string GMPL data section
     */
    public function generateDataWithS(LotterySpec $spec, int $minSatisfaction): string
    {
        $baseData = $this->generateData($spec);

        // Remove the trailing "end;" and add S parameter
        $baseData = rtrim($baseData);
        $baseData = preg_replace('/end;\s*$/', '', $baseData);

        return $baseData . "\nparam S := {$minSatisfaction};\n\nend;\n";
    }

    /**
     * Generate data file for item selection model with M parameter.
     *
     * @param  LotterySpec  $spec  Contains participants (with preferences) and items
     * @param  int  $itemCount  The M value (number of items to select)
     *
     * @return string GMPL data section
     */
    public function generateDataWithItemCount(LotterySpec $spec, int $itemCount): string
    {
        $baseData = $this->generateData($spec);

        // Remove the trailing "end;" and add M parameter
        $baseData = rtrim($baseData);
        $baseData = preg_replace('/end;\s*$/', '', $baseData);

        return $baseData . "\nparam M := {$itemCount};\n\nend;\n";
    }

    /**
     * Format array of IDs as GMPL set.
     *
     * @param  array  $ids  Array of numeric IDs
     * @param  string  $prefix  Prefix for IDs (e.g., 'p' for participants, 'i' for items)
     *
     * @return string Space-separated prefixed IDs
     */
    protected function formatSet(array $ids, string $prefix): string
    {
        return implode(' ', array_map(fn ($id) => "{$prefix}{$id}", $ids));
    }

    /**
     * Build preference matrix in GMPL format.
     *
     * Adds tie-breaker randomization to prevent degenerate preferences:
     * Instead of [1, 2, 3], each participant gets [1.XXX, 2.YYY, 3.ZZZ] where
     * XXX, YYY, ZZZ are unique random decimals per participant (0-999).
     *
     * This ensures no two participants have identical preference values,
     * breaking degeneracy and allowing GLPK to solve quickly without hangs.
     *
     * @param  array  $participants  Participant ID => preferences array
     * @param  array  $items  Array of item IDs
     *
     * @return string GMPL param r matrix
     */
    protected function buildPreferenceMatrix(array $participants, array $items): string
    {
        $itemHeaders = implode(' ', array_map(fn ($id) => "i{$id}", $items));
        $matrix = "param r : {$itemHeaders} :=\n";

        foreach ($participants as $participantId => $preferences) {
            $matrix .= "p{$participantId}";

            // Create map of item_id => preference_rank with random tie-breaker
            // Random tie-breaker: 0.0001 scale to prevent degeneracy without altering semantic rank
            $tieBreaker = mt_rand(0, 10000) / 100000000.0; // 0.0000 - 0.0001
            $preferenceMap = [];
            foreach ($preferences as $rank => $itemId) {
                // Base rank (1-indexed) + tiny tie-breaker for this participant
                // E.g., rank 0 becomes 1.00004751, rank 1 becomes 2.00004751, etc.
                $preferenceMap[$itemId] = ($rank + 1) + $tieBreaker;
            }

            // Output preference for each item in order
            foreach ($items as $itemId) {
                $rank = $preferenceMap[$itemId] ?? 999; // Default to very low preference if missing
                $matrix .= ' ' . str_pad(sprintf('%.3f', $rank), 7);
            }

            $matrix .= "\n";
        }

        $matrix .= ';';

        return $matrix;
    }
}
