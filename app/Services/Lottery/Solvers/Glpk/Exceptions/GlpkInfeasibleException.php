<?php

namespace App\Services\Lottery\Solvers\Glpk\Exceptions;

/**
 * Exception thrown when GLPK determines the problem is infeasible (no solution exists).
 */
class GlpkInfeasibleException extends GlpkException
{
    public function getUserMessage(): string
    {
        return __('lottery.glpk_infeasible');
    }
}
