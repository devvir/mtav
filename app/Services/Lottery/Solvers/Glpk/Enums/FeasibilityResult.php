<?php

namespace App\Services\Lottery\Solvers\Glpk\Enums;

/**
 * What phase 2 made of a candidate S, to guide HybridDistribution's binary search over it.
 */
enum FeasibilityResult: string
{
    /**
     * Phase 2 succeeded with the given S - this S value works.
     */
    case FEASIBLE = 'feasible';

    /**
     * Phase 2 failed due to infeasibility - need a higher S value.
     */
    case INFEASIBLE = 'infeasible';
}
