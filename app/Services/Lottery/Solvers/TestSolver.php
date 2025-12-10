<?php

namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Test solver for predictable lottery assignments.
 *
 * Sorts families and units, then pairs them, ignoring preferences.
 * Useful for deterministic automated testing.
 */
class TestSolver implements SolverInterface
{
    /**
     * Execute test lottery assignment.
     */
    public function execute(LotteryManifest $_, LotterySpec $spec): ExecutionResult
    {
        $families = array_keys($spec->families);
        $units = $spec->units;

        sort($families);
        sort($units);

        $matched = min(count($families), count($units));

        return new ExecutionResult(
            picks: array_combine(
                array_slice($families, 0, $matched),
                array_slice($units, 0, $matched)
            ),
            orphans: [
                'families' => array_slice($families, $matched),
                'units'    => array_slice($units, $matched),
            ],
        );
    }
}
