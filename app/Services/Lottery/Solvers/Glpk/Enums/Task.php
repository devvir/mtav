<?php

namespace App\Services\Lottery\Solvers\Glpk\Enums;

enum Task: string
{
    /**
     * Atomic tasks
     */
    case MIN_SATISFACTION = 'min_satisfaction';
    case UNIT_DISTRIBUTION = 'unit_distribution';
    case WORST_UNITS_PRUNING = 'worst_units_pruning';

    /**
     * Composite tasks
     */
    case GLPK_DISTRIBUTION = 'glpk_distribution';
    case HYBRID_DISTRIBUTION = 'hybrid_distribution';
}
