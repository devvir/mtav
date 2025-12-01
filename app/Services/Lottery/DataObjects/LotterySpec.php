<?php

namespace App\Services\Lottery\DataObjects;

/**
 * Lottery specification for executing a single lottery.
 *
 * Represents the data needed to execute one lottery: families, units,
 * and their preferences. This is the atomic unit of lottery execution -
 * executors process one LotterySpec at a time.
 */
class LotterySpec
{
    public function __construct(
        public readonly array $families,
        public readonly array $units,
    ) {
        // ...
    }

    /**
     * Get the number of families in this lottery.
     */
    public function familyCount(): int
    {
        return count($this->families);
    }

    /**
     * Get the number of units in this lottery.
     */
    public function unitCount(): int
    {
        return count($this->units);
    }

    /**
     * Check if this is a balanced lottery (same number of families and units).
     */
    public function isBalanced(): bool
    {
        return $this->familyCount() === $this->unitCount();
    }
}
