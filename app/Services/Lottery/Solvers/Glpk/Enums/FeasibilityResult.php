<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk\Enums;

/**
 * Feedback from Phase 2 feasibility check to guide MinSatisfaction's binary search.
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

    /**
     * Phase 2 timed out - cannot determine feasibility.
     */
    case TIMEOUT = 'timeout';
}
