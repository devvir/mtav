<?php

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * The GLPK data (.dat) of each task, in GMPL: the participants, the items, and the rank each
 * participant gives each item.
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
        return $this->withParam($this->generateData($spec), 'S', $minSatisfaction);
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
        return $this->withParam($this->generateData($spec), 'M', $itemCount);
    }

    /** The data with one more parameter, before its closing "end;" */
    protected function withParam(string $data, string $name, int $value): string
    {
        $data = preg_replace('/end;\s*$/', '', rtrim($data));

        return $data . "\nparam {$name} := {$value};\n\nend;\n";
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
     * The rank each participant gives each item (param r), 1 for the most wanted; an item a
     * participant did not rank counts as 999.
     *
     * Each participant's ranks all get one same random amount (at most 0.0001), meant as a
     * tie-breaker; ranks are written with three decimals.
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

            // One amount for all of this participant's ranks: 1 becomes 1.00004751, 2 becomes 2.00004751…
            $tieBreaker = mt_rand(0, 10000) / 100000000.0;
            $preferenceMap = [];
            foreach ($preferences as $rank => $itemId) {
                $preferenceMap[$itemId] = ($rank + 1) + $tieBreaker;
            }

            foreach ($items as $itemId) {
                $rank = $preferenceMap[$itemId] ?? 999;
                $matrix .= ' ' . str_pad(sprintf('%.3f', $rank), 7);
            }

            $matrix .= "\n";
        }

        $matrix .= ';';

        return $matrix;
    }
}
