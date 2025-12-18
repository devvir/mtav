<?php

namespace App\Services\Lottery\Solvers\Glpk\Exceptions;

use App\Services\Lottery\Exceptions\LotteryExecutionException;

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
