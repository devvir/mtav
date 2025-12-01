<?php

namespace App\Services\Lottery\Exceptions;

/**
 * Exception thrown when lottery has insufficient families (<2) to execute.
 */
class InsufficientFamiliesException extends LotteryExecutionException
{
    public function __construct(int $familyCount)
    {
        parent::__construct(
            "Cannot execute lottery: found {$familyCount} families, but at least 2 are required."
        );
    }

    public function getUserMessage(): string
    {
        return __('lottery.insufficient_families');
    }
}
