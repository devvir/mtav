<?php

// Copilot - Pending review

namespace App\Services\Lottery\Glpk;

/**
 * Generates GLPK model files (.mod) for lottery optimization.
 */
class ModelGenerator
{
    /**
     * Generate Phase 1 model: Maximize minimum satisfaction (max-min fairness).
     *
     * Objective: Find the optimal worst-case satisfaction level.
     * The model minimizes z, which represents the worst satisfaction any family receives.
     */
    public function generatePhase1Model(): string
    {
        return <<<'GMPL'
# MTAV Lottery - Phase 1: Maximize Minimum Satisfaction
# Objective: Max-min fairness (minimize worst-case dissatisfaction)

set C;              # Cooperativistas (families)
set V;              # Viviendas (units)

param p{c in C, v in V};  # Prioridad (lower = better: 1 = first choice)

var x{c in C, v in V}, binary;  # Assignment decision: 1 if family c gets unit v
var z, integer;                  # Worst satisfaction level (to minimize)

minimize resultado: z;

# z must be at least as large as each family's satisfaction
s.t. z_menorIgual{c in C}:
    z >= sum{v in V} p[c,v] * x[c,v];

# Each family gets exactly one unit
s.t. unicaAsignacionCoperativista_mayorIgual{c in C}:
    sum{v in V} x[c,v] >= 1;
s.t. unicaAsignacionCoperativista_menorIgual{c in C}:
    sum{v in V} x[c,v] <= 1;

# Each unit assigned to exactly one family
s.t. unicaAsignacionCasa_mayorIgual{v in V}:
    sum{c in C} x[c,v] >= 1;
s.t. unicaAsignacionCasa_menorIgual{v in V}:
    sum{c in C} x[c,v] <= 1;

end;
GMPL;
    }

    /**
     * Generate Phase 2 model: Maximize overall satisfaction given min satisfaction constraint.
     *
     * Objective: Among all solutions that achieve the same worst-case satisfaction S,
     * find the one with the best overall satisfaction (most 1st choices, then 2nd, etc.).
     *
     * Note: We minimize the sum of preference ranks (where lower = better),
     * which effectively maximizes satisfaction.
     */
    public function generatePhase2Model(): string
    {
        return <<<'GMPL'
# MTAV Lottery - Phase 2: Maximize Overall Satisfaction
# Objective: Break ties by maximizing total satisfaction

set C;              # Cooperativistas (families)
set V;              # Viviendas (units)

param p{c in C, v in V};  # Prioridad (lower = better: 1 = first choice)
param S;                  # Minimum satisfaction from Phase 1 (worst-case rank)

var x{c in C, v in V}, binary;  # Assignment decision

# Minimize sum of ranks = Maximize satisfaction
minimize resultado: sum{c in C, v in V} p[c,v] * x[c,v];

# No family gets worse than S
s.t. satisfaccionMinima{c in C}:
    sum{v in V} p[c,v] * x[c,v] <= S;

# Each family gets exactly one unit
s.t. unicaAsignacionCoperativista_mayorIgual{c in C}:
    sum{v in V} x[c,v] >= 1;
s.t. unicaAsignacionCoperativista_menorIgual{c in C}:
    sum{v in V} x[c,v] <= 1;

# Each unit assigned to exactly one family
s.t. unicaAsignacionCasa_mayorIgual{v in V}:
    sum{c in C} x[c,v] >= 1;
s.t. unicaAsignacionCasa_menorIgual{v in V}:
    sum{c in C} x[c,v] <= 1;

end;
GMPL;
    }

    /**
     * Generate unit selection model for surplus units scenario.
     *
     * This model selects exactly M units from a larger set while minimizing worst-case
     * satisfaction. Used as pre-phase when heuristic pruning leaves more units than needed.
     *
     * Variables:
     * - x[c,v]: Assignment of family c to unit v (binary)
     * - u[v]: Whether unit v is used (binary)
     * - z: Worst-case satisfaction level (integer)
     *
     * The model finds which M units to keep such that the assignment has the best
     * worst-case outcome (max-min fairness).
     */
    public function generateUnitSelectionModel(): string
    {
        return <<<'GMPL'
# MTAV Lottery - Unit Selection: Identify Worst Units
# Objective: Select M units that minimize worst-case satisfaction

set C;              # Cooperativistas (families)
set V;              # Viviendas (candidate units)

param p{c in C, v in V};  # Prioridad (lower = better: 1 = first choice)
param M;                  # Number of units to select (= number of families)

var x{c in C, v in V}, binary;  # Assignment decision: 1 if family c gets unit v
var u{v in V}, binary;          # Unit selection: 1 if unit v is used
var z, integer;                  # Worst satisfaction level (to minimize)

minimize resultado: z;

# z must be at least as large as each family's satisfaction
s.t. z_menorIgual{c in C}:
    z >= sum{v in V} p[c,v] * x[c,v];

# Each family gets exactly one unit
s.t. familyGetsOne{c in C}:
    sum{v in V} x[c,v] = 1;

# Each unit assigned to at most one family
s.t. unitGetsAtMostOne{v in V}:
    sum{c in C} x[c,v] <= 1;

# Link assignment to usage: if family gets unit v, then u[v] = 1
s.t. unitUsageLink{v in V}:
    sum{c in C} x[c,v] <= u[v];

# Select exactly M units
s.t. exactlyMUnitsUsed:
    sum{v in V} u[v] = M;

end;
GMPL;
    }
}
