<?php

namespace App\Services\Lottery\Exceptions;

use Exception;

/**
 * Base exception for lottery execution errors.
 *
 * Parent class for all lottery-related exceptions that should be shown
 * to users via flash messages. The technical message is logged for admins
 * while users see a translated user-friendly message.
 */
class LotteryExecutionException extends Exception
{
    /**
     * Get user-facing error message for display.
     */
    public function getUserMessage(): string
    {
        return __('lottery.execution_failed');
    }
}
