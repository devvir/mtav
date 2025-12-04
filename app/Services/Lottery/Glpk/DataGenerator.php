<?php

// Copilot - Pending review

namespace App\Services\Lottery\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Generates GLPK data files (.dat) from lottery specifications.
 */
class DataGenerator
{
    /**
     * Generate Phase 1 data file from lottery specification.
     *
     * @param  LotterySpec  $spec  Contains families (with preferences) and units
     * @return string GMPL data section
     */
    public function generateData(LotterySpec $spec): string
    {
        $families = $spec->families;
        $units = $spec->units;

        // Build sets
        $familyIds = array_keys($families);
        $familySet = $this->formatSet($familyIds, 'c');
        $unitSet = $this->formatSet($units, 'v');

        // Build preference matrix
        $preferenceMatrix = $this->buildPreferenceMatrix($families, $units);

        return <<<GMPL
data;

set C := {$familySet};
set V := {$unitSet};

{$preferenceMatrix}

end;
GMPL;
    }

    /**
     * Generate Phase 2 data file with minimum satisfaction parameter.
     *
     * @param  LotterySpec  $spec  Contains families (with preferences) and units
     * @param  int  $minSatisfaction  The S value from Phase 1
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
     * Generate data file for unit selection model with M parameter.
     *
     * @param  LotterySpec  $spec  Contains families (with preferences) and units
     * @param  int  $unitCount  The M value (number of units to select)
     * @return string GMPL data section
     */
    public function generateDataWithUnitCount(LotterySpec $spec, int $unitCount): string
    {
        $baseData = $this->generateData($spec);

        // Remove the trailing "end;" and add M parameter
        $baseData = rtrim($baseData);
        $baseData = preg_replace('/end;\s*$/', '', $baseData);

        return $baseData . "\nparam M := {$unitCount};\n\nend;\n";
    }

    /**
     * Format array of IDs as GMPL set.
     *
     * @param  array  $ids  Array of numeric IDs
     * @param  string  $prefix  Prefix for IDs (e.g., 'c' for families, 'v' for units)
     * @return string Space-separated prefixed IDs
     */
    protected function formatSet(array $ids, string $prefix): string
    {
        return implode(' ', array_map(fn ($id) => "{$prefix}{$id}", $ids));
    }

    /**
     * Build preference matrix in GMPL format.
     *
     * @param  array  $families  Family ID => preferences array
     * @param  array  $units  Array of unit IDs
     * @return string GMPL param p matrix
     */
    protected function buildPreferenceMatrix(array $families, array $units): string
    {
        $unitHeaders = implode(' ', array_map(fn ($id) => "v{$id}", $units));
        $matrix = "param p : {$unitHeaders} :=\n";

        foreach ($families as $familyId => $preferences) {
            $matrix .= "c{$familyId}";

            // Create map of unit_id => preference_rank
            $preferenceMap = [];
            foreach ($preferences as $rank => $unitId) {
                $preferenceMap[$unitId] = $rank + 1; // 1-indexed ranks
            }

            // Output preference for each unit in order
            foreach ($units as $unitId) {
                $rank = $preferenceMap[$unitId] ?? 999; // Default to very low preference if missing
                $matrix .= ' ' . str_pad((string)$rank, 7);
            }

            $matrix .= "\n";
        }

        $matrix .= ';';

        return $matrix;
    }
}
