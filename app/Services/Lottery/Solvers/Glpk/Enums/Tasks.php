<?php

namespace App\Services\Lottery\Solvers\Glpk\Enums;

enum Tasks: string
{
    case MIN_SATISFACTION = 'min_satisfaction';
    case UNIT_DISTRIBUTION = 'unit_distribution';
    case WORST_UNITS_PRUNING = 'worst_units_pruning';
}
