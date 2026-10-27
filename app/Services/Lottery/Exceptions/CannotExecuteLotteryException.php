<?php

namespace App\Services\Lottery\Exceptions;

/**
 * The lottery cannot run now: not scheduled yet, not due, already run, too few
 * families, or too large for an unapproved project.
 */
class CannotExecuteLotteryException extends LotteryExecutionException
{
    public static function because(string $reason, array $params = []): static
    {
        return static::withUserMessage("lottery.cannot_run_{$reason}", $params, "Cannot run the lottery: {$reason}");
    }
}
