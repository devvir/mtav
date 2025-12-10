<?php

namespace App\Services\Lottery\Exceptions;

/**
 * Exception thrown when lottery execution requires explicit admin confirmation.
 *
 * Signals that the solver has detected a condition requiring deviation from the
 * standard beahvior or algorithm e.g., falling back to greedy when exact solution
 * doesn't exist or is too hard too find).
 *
 * @param string $option   Which option requires confirmation, e.g. 'fallback-to-greedy'.
 * @param string $message  User-facing message to describe the option needing confirmation
 */
class LotteryRequiresConfirmationException extends LotteryExecutionException
{
    /**
     * @param string $option The option key that requires confirmation (e.g., 'fallback-to-greedy')
     * @param string $message User-facing message explaining what needs confirmation
     */
    public function __construct(
        public readonly string $option,
        string $message = ''
    ) {
        parent::__construct($message);
    }

    /**
     * Get the option that requires confirmation.
     */
    public function getOption(): string
    {
        return $this->option;
    }

    /**
     * Get user-facing error message for display.
     */
    public function getUserMessage(): string
    {
        return $this->message ?: parent::getUserMessage();
    }
}
