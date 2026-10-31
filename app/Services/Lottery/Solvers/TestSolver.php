<?php

namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Test solver for predictable lottery assignments.
 *
 * Sorts participants and items, then pairs them, ignoring preferences.
 * Useful for deterministic automated testing.
 */
class TestSolver implements SolverInterface
{
    /**
     * Execute test lottery assignment.
     */
    public function execute(LotteryManifest $_, LotterySpec $spec): ExecutionResult
    {
        $participants = array_keys($spec->participants);
        $items = $spec->items;

        sort($participants);
        sort($items);

        $matched = min(count($participants), count($items));

        return new ExecutionResult(
            picks: array_combine(
                array_slice($participants, 0, $matched),
                array_slice($items, 0, $matched)
            ),
            orphans: [
                'participants' => array_slice($participants, $matched),
                'items'        => array_slice($items, $matched),
            ],
        );
    }
}
