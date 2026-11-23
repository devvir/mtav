<?php

namespace App\Services\Lottery\Solvers\Glpk;

/**
 * The GLPK models (.mod) of each task, in GMPL. Ranks are 1 for the most wanted: "satisfaction"
 * is better the lower the rank.
 */
class ModelGenerator
{
    /**
     * Generate Phase 1 model: Maximize minimum satisfaction (max-min fairness).
     *
     * It minimizes z, the worst rank any participant receives: its optimum is S.
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

# Every one on the smaller side is paired, exactly once; on the larger side, at most once, so
# whichever side has more, some of it is left over. Written as equalities where they hold,
# the problem keeps the assignment structure GLPK solves fast.
s.t. everyParticipantServed{p in P: card(P) <= card(I)}:
    sum{i in I} x[p,i] = 1;

s.t. atMostOneItemPerParticipant{p in P: card(P) > card(I)}:
    sum{i in I} x[p,i] <= 1;

s.t. everyItemTaken{i in I: card(I) <= card(P)}:
    sum{p in P} x[p,i] = 1;

s.t. atMostOneParticipantPerItem{i in I: card(I) > card(P)}:
    sum{p in P} x[p,i] <= 1;

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

# Every one on the smaller side is paired, exactly once; on the larger side, at most once, so
# whichever side has more, some of it is left over. Written as equalities where they hold,
# the problem keeps the assignment structure GLPK solves fast.
s.t. everyParticipantServed{p in P: card(P) <= card(I)}:
    sum{i in I} x[p,i] = 1;

s.t. atMostOneItemPerParticipant{p in P: card(P) > card(I)}:
    sum{i in I} x[p,i] <= 1;

s.t. everyItemTaken{i in I: card(I) <= card(P)}:
    sum{p in P} x[p,i] = 1;

s.t. atMostOneParticipantPerItem{i in I: card(I) > card(P)}:
    sum{p in P} x[p,i] <= 1;

end;
GMPL;
    }

}
