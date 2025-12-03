<?php

namespace App\Services\Lottery\Exceptions;

/**
 * Exception thrown when GLPK execution fails.
 */
class GlpkException extends LotteryExecutionException
{
    public function getUserMessage(): string
    {
        return __('lottery.glpk_execution_failed');
    }
}
