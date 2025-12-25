<?php

namespace App\Services\Lottery\Solvers\Glpk\Exceptions;

use App\Services\Lottery\Exceptions\LotteryExecutionException;

/**
 * Exception thrown when GLPK execution fails.
 */
class GlpkException extends LotteryExecutionException
{
    protected array $debugData = [];

    /**
     * Attach debug data to the exception.
     */
    public function with(array $debugData): static
    {
        $this->debugData = $debugData;

        return $this;
    }

    /**
     * Get debug data attached to the exception.
     */
    public function debug(): array
    {
        return $this->debugData;
    }

    /**
     * User-facing localized error message.
     */
    public function getUserMessage(): string
    {
        return __('lottery.glpk_execution_failed');
    }
}
