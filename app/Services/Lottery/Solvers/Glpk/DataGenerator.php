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
     * participant did not rank counts as 999. Ties are not broken here: the orchestrator draws
     * the order participants and items come in (`LotteryOrchestrator::drawn`).
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

            $preferenceMap = [];
            foreach ($preferences as $rank => $itemId) {
                $preferenceMap[$itemId] = $rank + 1;
            }

            foreach ($items as $itemId) {
                $rank = $preferenceMap[$itemId] ?? 999;
                $matrix .= ' ' . str_pad((string) $rank, 3);
            }

            $matrix .= "\n";
        }

        $matrix .= ';';

        return $matrix;
    }
}
