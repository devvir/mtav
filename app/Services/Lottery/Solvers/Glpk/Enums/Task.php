<?php

namespace App\Services\Lottery\Solvers\Glpk\Enums;

enum Task: string
{
    /**
     * Atomic tasks
     */
    case MIN_SATISFACTION = 'min_satisfaction';
    case ITEM_DISTRIBUTION = 'item_distribution';

    /**
     * Composite tasks
     */
    case GLPK_DISTRIBUTION = 'glpk_distribution';
    case HYBRID_DISTRIBUTION = 'hybrid_distribution';
}
