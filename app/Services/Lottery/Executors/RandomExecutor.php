<?php

namespace App\Services\Lottery\Executors;

use App\Services\Lottery\Contracts\ExecutorInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Random executor for lottery assignments.
 *
 * Shuffles families and units, then pairs them, ignoring preferences.
 * Useful for development and manual testing.
 */
class RandomExecutor implements ExecutorInterface
{
    /**
     * Execute random lottery assignment.
     */
    public function execute(LotterySpec $spec): ExecutionResult
    {
        $families = array_keys($spec->families);
        $units = $spec->units;

        shuffle($families);
        shuffle($units);

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
