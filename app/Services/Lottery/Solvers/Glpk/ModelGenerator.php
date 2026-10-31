<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk;

/**
 * Generates GLPK model files (.mod) for lottery optimization.
 */
class ModelGenerator
{
    /**
     * Generate Phase 1 model: Maximize minimum satisfaction (max-min fairness).
     *
     * Objective: Find the optimal worst-case satisfaction level.
     * The model minimizes z, which represents the worst satisfaction any participant receives.
     */
    public function generatePhase1Model(): string
    {
        return <<<'GMPL'
# MTAV Lottery - Phase 1: Maximize Minimum Satisfaction
# Objective: Max-min fairness (minimize worst-case dissatisfaction)

set P;              # Participants
set I;              # Items

param r{p in P, i in I};  # Rank (lower = better: 1 = first choice)

var x{p in P, i in I}, binary;  # Assignment decision: 1 if participant p gets item i
var z, integer;                  # Worst satisfaction level (to minimize)

minimize resultado: z;

# z must be at least as large as each participant's satisfaction
s.t. z_menorIgual{p in P}:
    z >= sum{i in I} r[p,i] * x[p,i];

# Each participant gets exactly one item
s.t. oneItemPerParticipant{p in P}:
    sum{i in I} x[p,i] = 1;

# Each item assigned to exactly one participant
s.t. oneParticipantPerItem{i in I}:
    sum{p in P} x[p,i] = 1;

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

set P;              # Participants
set I;              # Items

param r{p in P, i in I};  # Rank (lower = better: 1 = first choice)
param S;                  # Minimum satisfaction from Phase 1 (worst-case rank)

var x{p in P, i in I}, binary;  # Assignment decision

# Minimize sum of ranks = Maximize satisfaction
minimize resultado: sum{p in P, i in I} r[p,i] * x[p,i];

# No participant gets worse than S
s.t. satisfaccionMinima{p in P}:
    sum{i in I} r[p,i] * x[p,i] <= S;

# Each participant gets exactly one item
s.t. oneItemPerParticipant{p in P}:
    sum{i in I} x[p,i] = 1;

# Each item assigned to exactly one participant
s.t. oneParticipantPerItem{i in I}:
    sum{p in P} x[p,i] = 1;

end;
GMPL;
    }

    /**
     * Generate item selection model for surplus items scenario.
     *
     * This model selects exactly M items from a larger set while minimizing worst-case
     * satisfaction. Used as pre-phase when heuristic pruning leaves more items than needed.
     *
     * Variables:
     * - x[p,i]: Assignment of participant p to item i (binary)
     * - u[i]: Whether item i is used (binary)
     * - z: Worst-case satisfaction level (integer)
     *
     * The model finds which M items to keep such that the assignment has the best
     * worst-case outcome (max-min fairness).
     */
    public function generateItemSelectionModel(): string
    {
        return <<<'GMPL'
# MTAV Lottery - Item Selection: Identify Worst Items
# Objective: Select M items that minimize worst-case satisfaction

set P;              # Participants
set I;              # Items (candidates)

param r{p in P, i in I};  # Rank (lower = better: 1 = first choice)
param M;                  # Number of items to select (= number of participants)

var x{p in P, i in I}, binary;  # Assignment decision: 1 if participant p gets item i
var u{i in I}, binary;          # Item selection: 1 if item i is used
var z, integer;                 # Worst satisfaction level (to minimize)

minimize resultado: z;

# z must be at least as large as each participant's satisfaction
s.t. z_menorIgual{p in P}:
    z >= sum{i in I} r[p,i] * x[p,i];

# Each participant gets exactly one item
s.t. participantGetsOne{p in P}:
    sum{i in I} x[p,i] = 1;

# Each item assigned to at most one participant
s.t. itemGetsAtMostOne{i in I}:
    sum{p in P} x[p,i] <= 1;

# Link assignment to usage: if participant gets item i, then u[i] = 1
s.t. itemUsageLink{i in I}:
    sum{p in P} x[p,i] <= u[i];

# Select exactly M items
s.t. exactlyMItemsUsed:
    sum{i in I} u[i] = M;

end;
GMPL;
    }
}
