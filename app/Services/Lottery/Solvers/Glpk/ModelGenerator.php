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

# At most one item per participant, at most one participant per item, and as many pairs as the
# smaller side allows: whichever side has more, some of it is left over
s.t. atMostOneItemPerParticipant{p in P}:
    sum{i in I} x[p,i] <= 1;

s.t. atMostOneParticipantPerItem{i in I}:
    sum{p in P} x[p,i] <= 1;

s.t. everyPairPossible:
    sum{p in P, i in I} x[p,i] = min(card(P), card(I));

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

# At most one item per participant, at most one participant per item, and as many pairs as the
# smaller side allows: whichever side has more, some of it is left over
s.t. atMostOneItemPerParticipant{p in P}:
    sum{i in I} x[p,i] <= 1;

s.t. atMostOneParticipantPerItem{i in I}:
    sum{p in P} x[p,i] <= 1;

s.t. everyPairPossible:
    sum{p in P, i in I} x[p,i] = min(card(P), card(I));

end;
GMPL;
    }

}
