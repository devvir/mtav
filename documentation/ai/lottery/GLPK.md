<!-- Copilot - Pending review -->

# GLPK Solver: Comprehensive Technical Reference

**Last Updated**: December 25, 2025
**Status**: Production-ready, fully tested
**Maintainer**: MTAV Development Team

## Table of Contents

1. [Overview](#overview)
2. [Mathematical Foundation](#mathematical-foundation)
3. [Architecture](#architecture)
4. [Execution Flow](#execution-flow)
5. [TaskRunner System](#taskrunner-system)
6. [Model Generation](#model-generation)
7. [Binary Search Fallback](#binary-search-fallback)
8. [Error Handling & Recovery](#error-handling--recovery)
9. [Auditing](#auditing)
10. [Performance & Benchmarks](#performance--benchmarks)
11. [Configuration](#configuration)
12. [Testing Strategy](#testing-strategy)
13. [Known Edge Cases](#known-edge-cases)
14. [Future Improvements](#future-improvements)

---

## Overview

### Purpose

The GLPK (GNU Linear Programming Kit) solver provides mathematically optimal housing unit assignments for MTAV cooperative projects. It implements a two-phase optimization strategy that ensures **max-min fairness** while **maximizing overall satisfaction**.

### Key Properties

- **Deterministic**: Same input always produces same output (no randomness)
- **Optimal**: Guarantees mathematically optimal solution when one exists
- **Fair**: Minimizes worst-case dissatisfaction (max-min fairness principle)
- **Efficient**: Typical problems (20-100 entities) solve in 2-5 seconds
- **Robust**: Binary search fallback for degenerate cases

### Why GLPK?

**Compared to Random Assignment**:
- Provably optimal fairness (not luck-based)
- Respects all preferences simultaneously
- Mathematically defendable results

**Compared to FCFS/Sequential**:
- Global optimization (sees all preferences)
- No timing bias
- Better overall satisfaction

**Compared to Other Solvers**:
- Open source (no licensing costs)
- Battle-tested (25+ years development)
- Excellent documentation
- Active community support

---

## Mathematical Foundation

### Problem Formulation

The lottery is modeled as a **Mixed Integer Programming (MIP)** problem:

**Sets**:
- `C`: Cooperativistas (families)
- `V`: Viviendas (housing units)

**Parameters**:
- `p[c,v]`: Preference rank (1 = first choice, 2 = second choice, etc.)

**Decision Variables**:
- `x[c,v]`: Binary variable (1 if family c assigned to unit v, 0 otherwise)

**Constraints**:
- Each family gets exactly one unit: `sum{v in V} x[c,v] = 1 for all c in C`
- Each unit assigned to exactly one family: `sum{c in C} x[c,v] = 1 for all v in V`

### Two-Phase Optimization

#### Phase 1: Minimize Worst-Case Rank (Max-Min Fairness)

**Objective**: Find the minimum satisfaction level S such that all families can be assigned.

```
minimize: z
subject to:
    z >= sum{v in V} p[c,v] * x[c,v]  for all c in C
    (assignment constraints as above)
```

**Interpretation**:
- `z` represents the worst rank any family receives
- Minimizing `z` ensures fairness (nobody gets an unreasonably bad outcome)
- Solution provides minimum satisfaction level S*

**Example**:
```
Family A prefers: [1, 2, 3] (unit IDs)
Family B prefers: [1, 3, 2]
Family C prefers: [2, 1, 3]

Phase 1 finds: S* = 2 (worst family gets 2nd choice)
Possible assignment: A→1 (rank 1), B→3 (rank 2), C→2 (rank 1)
```

#### Phase 2: Maximize Overall Satisfaction

**Objective**: Among all assignments where no family gets worse than S*, find the one with best total satisfaction.

```
minimize: sum{c in C, v in V} p[c,v] * x[c,v]
subject to:
    sum{v in V} p[c,v] * x[c,v] <= S  for all c in C
    (assignment constraints as above)
```

**Interpretation**:
- Minimizing sum of ranks = maximizing satisfaction
- S constraint from Phase 1 ensures fairness preserved
- Breaks ties optimally (more 1st choices, then 2nd choices, etc.)

**Example** (continuing from above):
```
All solutions with S=2:
- Option 1: A→1 (1), B→3 (2), C→2 (1) → total = 4
- Option 2: A→3 (3), B→1 (1), C→2 (1) → total = 5 (violates S≤2)
- Option 3: A→1 (1), B→2 (3), C→3 (3) → total = 7 (violates S≤2)

Phase 2 selects Option 1 (total = 4 is minimum)
```

### Why Two Phases?

**Mathematical Reason**: Single-objective optimization cannot simultaneously optimize for fairness AND satisfaction. Multi-objective problems require lexicographic ordering:
1. First priority: fairness (minimize worst-case)
2. Second priority: efficiency (maximize total satisfaction)

**Practical Reason**: Phase 1 ensures nobody feels cheated, Phase 2 makes the solution as good as possible overall.

---

## Architecture

### Directory Structure

```
app/Services/Lottery/Solvers/Glpk/
├── Glpk.php                        # Strategy orchestrator
├── ModelGenerator.php              # Generates .mod files (GMPL models)
├── DataGenerator.php               # Generates .dat files (problem data)
├── SolutionParser.php              # Parses .sol files (GLPK output)
├── Logger.php                      # Failure logging with artifacts
├── lib/
│   ├── Files.php                   # Temp file management
│   └── Process.php                 # glpsol subprocess execution
├── Exceptions/
│   ├── GlpkException.php           # Base exception
│   ├── GlpkTimeoutException.php    # Timeout (60s+)
│   └── GlpkInfeasibleException.php # No solution exists
└── TaskRunners/
    ├── TaskRunner.php              # Abstract base with GLPK execution
    ├── TaskRunnerFactory.php       # DI factory
    ├── Task.php                    # Task enum
    ├── TaskResult.php              # Result DTO with artifacts
    ├── MinSatisfaction.php         # Phase 1 (single task)
    ├── UnitDistribution.php        # Phase 2 (single task)
    ├── WorstUnitsPruning.php       # Unit selection (single task)
    ├── GlpkDistribution.php        # Composite: Phase1 + Phase2
    └── HybridDistribution.php      # Composite: BinarySearch + Phase2
```

### Class Responsibilities

**`Glpk`** (Strategy Orchestrator):
- Entry point from `GlpkSolver`
- Selects execution strategy (GLPK vs Hybrid) based on spec size
- Delegates to composite TaskRunners (GlpkDistribution or HybridDistribution)
- Manages audit trail via `auditTask()`
- Handles timeout fallback (GLPK → Hybrid on timeout)
- **Does NOT**: Execute GLPK directly or orchestrate phases manually

**`ModelGenerator`**:
- Generates GMPL (GNU MathProg Language) models
- Three models: Phase 1, Phase 2, Unit Selection
- Pure string generation (no I/O)

**`DataGenerator`**:
- Converts `LotterySpec` to GLPK data format
- Handles preference matrix encoding
- Supports mock units (string IDs)

**`SolutionParser`**:
- Parses `.sol` output files
- Extracts objective values (Phase 1 S value)
- Extracts assignments (family→unit mapping)
- Detects infeasibility

**`TaskRunner`** (Abstract):
- Shared infrastructure for GLPK execution
- Subprocess management via `Process`
- File I/O via `Files`
- Config reading internalized

**TaskRunner Implementations**:

*Atomic Tasks*:
- `MinSatisfaction`: Phase 1 only (find min satisfaction S)
- `UnitDistribution`: Phase 2 only (distribute with S constraint)
- `WorstUnitsPruning`: Unit selection for surplus units

*Composite Tasks* (orchestrate multiple atomic tasks):
- `GlpkDistribution`: Phase1 (direct GLPK) + Phase2
- `HybridDistribution`: Phase1 (binary search) + Phase2

---

## Execution Flow

### High-Level Flow

```
LotteryOrchestrator
    ↓
GlpkSolver::execute(manifest, spec)
    ↓
Glpk::distributeUnits(manifest, spec)
    ↓
  Strategy Selection
  (based on familyCount)
    ↓
┌────────────────────────────────┐
│ familyCount < 25?              │
│   → GlpkDistribution           │
│      (Phase1 direct + Phase2)  │
│                                │
│ familyCount ≥ 25?              │
│   → HybridDistribution         │
│      (BinarySearch + Phase2)   │
└────────┬───────────────────────┘
         │
    Timeout in Phase1?
         │
         ↓
┌────────────────────────────────┐
│ Fallback to HybridDistribution │
│    (automatic retry)           │
└────────┬───────────────────────┘
         │
         ↓
    Distribution + Audit
```

### Detailed Sequence

1. **`Glpk::distributeUnits()`** receives `LotteryManifest` and `LotterySpec`

2. **Strategy Selection**:
   ```php
   if (familyCount >= glpk_phase1_max_size) {
       return hybridDistribution();  // Skip to binary search
   } else {
       try {
           return glpkDistribution();  // Try direct GLPK
       } catch (GlpkTimeoutException) {
           return hybridDistribution();  // Timeout fallback
       }
   }
   ```

3. **GLPK Strategy** (`glpkDistribution()`):
   ```php
   a. Create GlpkDistribution composite runner
   b. Execute with phase1_timeout context (0.5s default)
   c. Runner internally:
      - Executes MinSatisfaction (direct GLPK Phase1)
      - Extracts S from result
      - Executes UnitDistribution (Phase2) with S
      - Returns combined TaskResult with both phases' metadata
   d. Audit single composite result
   e. Extract distribution from result
   f. Return distribution
   ```

4. **Hybrid Strategy** (`hybridDistribution()`):
   ```php
   a. Create HybridDistribution composite runner
   b. Execute (no special context needed)
   c. Runner internally:
      - Executes MinSatisfaction with binary search
      - Extracts S from result
      - Executes UnitDistribution (Phase2) with S
      - Returns combined TaskResult with iterations/feasible_steps
   d. Audit single composite result
   e. Extract distribution from result
   f. Return distribution
   ```

5. **Error Scenarios**:
   - **Small spec timeout**: Automatic fallback to Hybrid (transparent)
   - **Large spec**: Uses Hybrid from start (no timeout risk)
   - **Phase 2 timeout**: Propagates exception (computational failure)
   - **Infeasible**: Throw `GlpkInfeasibleException`
   - **Other errors**: Throw `GlpkException`

### File Lifecycle

For each GLPK execution:

1. **Generation Phase**:
   ```
   Files::write('phase1_', '.mod', <model>)  → /tmp/phase1_abc123.mod
   Files::write('data_', '.dat', <data>)     → /tmp/data_def456.dat
   ```

2. **Execution Phase**:
   ```
   Process::execute("glpsol --model phase1_abc123.mod --data data_def456.dat --output solution.sol")
   ```

3. **Parsing Phase**:
   ```
   SolutionParser::extractObjective('solution.sol')     → S = 3
   SolutionParser::extractAssignments('solution.sol')  → [1 => 10, 2 => 20, ...]
   ```

4. **Cleanup**:
   ```
   Files are NOT deleted (useful for debugging)
   Temp directory: /tmp/ (system cleanup handles it)
   ```

---

## TaskRunner System

### Design Pattern

The TaskRunner architecture follows the **Template Method** pattern:

- **Abstract base** (`TaskRunner`): Defines common infrastructure
- **Concrete implementations**: Override `execute()` with specific logic
- **Factory**: Creates correct TaskRunner based on `Tasks` enum

### TaskRunner Base Class

**Location**: `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunner.php`

**Responsibilities**:
- Read GLPK config (glpsol path, timeout, temp dir)
- Create `Files` instance for temp file management
- Provide shared `runGlpk()` method for subprocess execution
- Define abstract `execute()` signature

**Key Method: `runGlpk()`**:
```php
protected function runGlpk(
    int $timeout,
    string $modFile,
    string $datFile,
    Closure $parser
): mixed
```

**Parameters**:
- `$timeout`: Max execution time in seconds
- `$modFile`: Path to .mod file (model)
- `$datFile`: Path to .dat file (data)
- `$parser`: Closure that parses .sol file (e.g., `extractAssignments(...)`)

**Returns**: Whatever `$parser` returns (objective value, assignments, etc.)

**Throws**:
- `GlpkTimeoutException`: Execution exceeded timeout
- `GlpkInfeasibleException`: GLPK proved no solution exists
- `GlpkException`: Other GLPK errors

### TaskResult DTO

**Location**: `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskResult.php`

**Structure**:
```php
class TaskResult {
    public readonly Tasks $task;           // Which task executed
    public readonly array $data;           // Task-specific results
    public readonly array $metadata;       // Execution metadata

    public function get(string $key): mixed;  // Helper to access data
}
```

**Example**:
```php
new TaskResult(
    task: Tasks::MIN_SATISFACTION,
    data: ['S' => 3],
    metadata: [
        'strategy' => 'binary_search',
        'iterations' => 7,
        'time_ms' => 1234.56
    ]
)
```

### TaskRunnerFactory

**Location**: `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunnerFactory.php`

**Purpose**: Type-safe TaskRunner instantiation via dependency injection

**Usage**:
```php
$factory = app(TaskRunnerFactory::class);
$runner = $factory->make(Tasks::MIN_SATISFACTION);
$result = $runner->execute($spec, 300);
```

**Implementation**: Uses `match()` expression for exhaustive enum handling

---

## Model Generation

### Constraint Evolution: Split vs Equality

**Original Models** (from tutor):
```gmpl
s.t. unicaAsignacionCoperativista_mayorIgual{c in C}:
    sum{v in V} x[c,v] >= 1;
s.t. unicaAsignacionCoperativista_menorIgual{c in C}:
    sum{v in V} x[c,v] <= 1;
```

**Current Models** (post-2025-12-18):
```gmpl
s.t. unicaAsignacionCoperativista{c in C}:
    sum{v in V} x[c,v] = 1;
```

**Why Changed?**

1. **Split Constraint Bug**: When GLPK's branch-and-bound encounters conflicts, it can relax `>= 1` while honoring `<= 1`, producing "OPTIMAL" status with all variables set to 0 (no assignments).

2. **Equality Constraint**: Forces GLPK to return proper `INFEASIBLE` status when no solution exists.

3. **Mathematical Equivalence**: `x >= 1 AND x <= 1` is identical to `x = 1` for integer variables.

4. **OR Best Practice**: While split constraints can improve numerical stability in some LP solvers, equality constraints are clearer and avoid the edge case.

**Tutor Consultation**: This change should be discussed with thesis tutor as it modifies the original models, though it preserves mathematical equivalence.

### Phase 1 Model

**File**: `ModelGenerator::generatePhase1Model()`

**Objective**: Minimize worst-case satisfaction level

```gmpl
minimize resultado: z;
```

**Key Constraints**:
- `z >= sum{v in V} p[c,v] * x[c,v]` for all families (z captures worst rank)
- Family assignment: `sum{v in V} x[c,v] = 1` for all c
- Unit assignment: `sum{c in C} x[c,v] = 1` for all v

**Variables**:
- `x[c,v]`: Binary (assignment decision)
- `z`: Integer (worst satisfaction level)

### Phase 2 Model

**File**: `ModelGenerator::generatePhase2Model()`

**Objective**: Minimize sum of ranks (= maximize satisfaction)

```gmpl
minimize resultado: sum{c in C, v in V} p[c,v] * x[c,v];
```

**Key Constraints**:
- `sum{v in V} p[c,v] * x[c,v] <= S` for all families (fairness constraint from Phase 1)
- Family assignment: `sum{v in V} x[c,v] = 1` for all c
- Unit assignment: `sum{c in C} x[c,v] = 1` for all v

**Variables**:
- `x[c,v]`: Binary (assignment decision)

**Parameter**:
- `S`: Minimum satisfaction from Phase 1

### Unit Selection Model

**File**: `ModelGenerator::generateUnitSelectionModel()`

**Purpose**: When surplus units exist (more units than families), select which M units to keep for optimal fairness.

**Use Case**: Orphan redistribution in `LotteryOrchestrator`

**Objective**: Minimize worst-case satisfaction for selected units

```gmpl
minimize resultado: z;
```

**Key Constraints**:
- `z >= sum{v in V} p[c,v] * x[c,v]` (worst satisfaction)
- `sum{v in V} x[c,v] = 1` (each family gets one)
- `sum{c in C} x[c,v] <= 1` (each unit gets at most one)
- `sum{c in C} x[c,v] <= u[v]` (assignment requires unit selection)
- `sum{v in V} u[v] = M` (select exactly M units)

**Variables**:
- `x[c,v]`: Binary (assignment)
- `u[v]`: Binary (unit selection)
- `z`: Integer (worst satisfaction)

**Parameter**:
- `M`: Number of units to select (= number of families)

---

## Binary Search Fallback

### Why It Exists

**Problem**: Phase 1 can timeout on **degenerate problems** - cases where many equivalent optimal solutions exist. GLPK's branch-and-bound explores unnecessary branches.

**Example of Degeneracy**:
```
50 families, 50 units, all families have identical preferences
→ Any permutation is equally optimal
→ GLPK explores exponentially many branches
```

**Solution**: Binary search over candidate S values using Phase 2 **feasibility tests**.

### Algorithm

**Location**: `MinSatisfaction::binarySearchMinS()`

**Strategy**:

1. **Generate Candidates**: `[1, 2, 3, 4, 5, 10, 15, 20, 25, ..., max_rank]`
   - Dense sampling at low values (most common)
   - Sparse sampling at high values
   - Always include max rank (guaranteed feasible)

2. **Binary Search** to find first feasible S:
   ```
   Test S=10: INFEASIBLE → search higher
   Test S=20: FEASIBLE   → search lower
   Test S=15: FEASIBLE   → search lower
   Test S=12: INFEASIBLE → search higher
   Test S=13: FEASIBLE   → found boundary
   ```

3. **Refinement**: Test S-1, S-2, ... until INFEASIBLE
   ```
   S=13: FEASIBLE
   S=12: INFEASIBLE
   → Proven minimum is S*=13
   ```

4. **Return** proven minimum S

### Feasibility Testing

**Key Insight**: Testing "does S=k have a solution?" via Phase 2 is faster than minimizing S via Phase 1.

**Method**: Run Phase 2 with S=k:
- If GLPK returns assignments → FEASIBLE
- If GLPK returns INFEASIBLE → INFEASIBLE

**Why Phase 2?**: More stable on degenerate problems because it has extra constraints (S bound) that reduce search space.

### Monotonicity Guarantee

**Critical Property**: If S=k is FEASIBLE, then S=k+1 is also FEASIBLE.

**Proof**:
- Any assignment satisfying "all ranks ≤ k" also satisfies "all ranks ≤ k+1"
- Relaxing constraint can only expand feasible region
- QED

**Implication**: Binary search correctness guaranteed - feasibility forms a continuous zone.

### Performance Characteristics

**Typical Case**:
- Candidates tested: 5-10
- Total time: 10-30 seconds
- Result: Proven optimal (same as Phase 1 would produce)

**Worst Case**:
- Candidates tested: 15-20
- Total time: 60-120 seconds
- Result: Still proven optimal

**Trade-off**: Slower than Phase 1 (when it works), but guaranteed to succeed.

### Generator-Based Implementation

**Why Generators?**: Binary search needs bidirectional communication:
- **Yield** candidate S values
- **Receive** feasibility feedback (FEASIBLE/INFEASIBLE)

**Implementation** (`MinSatisfaction::binarySearchGenerator()`):
```php
while ($lo <= $hi) {
    $candidateS = $candidates[mid];
    yield $candidateS;  // Send candidate to orchestrator

    $feedback = yield;  // Receive feasibility result
    match($feedback) {
        FeasibilityResult::FEASIBLE => $hi = mid - 1,
        FeasibilityResult::INFEASIBLE => $lo = mid + 1,
    };
}
return $provenMinS;
```

**Consumption** (`HybridDistribution` composite runner):
```php
while ($generator->valid()) {
    $candidateS = $generator->current();

    $feasibility = $this->testFeasibility($candidateS);
    $generator->send($feasibility);
}

$minS = $generator->getReturn();
```

**Critical**: Must use `while` loop with `current()/send()`, NOT `foreach`. The `foreach` construct calls `next()` internally, causing double advancement when combined with `send()`.

---

## Error Handling & Recovery

### Exception Hierarchy

```
GlpkException (base)
├── GlpkTimeoutException      # Timeout exceeded
├── GlpkInfeasibleException   # Proven no solution
└── (other GLPK errors)        # Parsing, process, etc.
```

### Timeout Handling

**Phase 1 Timeout**:
```php
try {
    $minS = $this->tryDirectGlpk($spec, 5);
} catch (GlpkTimeoutException $e) {
    // Automatic fallback - not an error
    $minS = $this->binarySearch($spec, $remainingTime);
}
```

**Phase 2 Timeout**:
```php
// No fallback - Phase 2 timeout is genuine failure
throw $e;  // Propagates to orchestrator
```

**Rationale**: Phase 1 timeouts are expected on degenerate problems. Phase 2 timeouts indicate computational intractability.

### Infeasibility Handling

**When It Occurs**:
- Problem mathematically impossible (e.g., 3 families, 2 units)
- Preferences create conflicts (e.g., everyone wants same unit)

**Detection**:
```php
if (str_contains($solContent, 'SOLUTION IS INFEASIBLE')) {
    throw new GlpkInfeasibleException('GLPK proved no solution exists');
}
```

**Handling**:
- **In Binary Search**: Expected behavior (part of search process), caught and used for control flow
- **In Direct Execution**: Genuine error (propagates to orchestrator, creates FAILURE audit)

### Orchestrator Recovery

**Location**: `Glpk::distributeUnits()`

```php
// Strategy selection based on size
if ($spec->familyCount() >= $this->phase1MaxSize) {
    return $this->hybridDistribution($manifest, $spec);
}

// Try GLPK, fallback to Hybrid on timeout
try {
    return $this->glpkDistribution($manifest, $spec);
} catch (GlpkTimeoutException) {
    return $this->hybridDistribution($manifest, $spec);
}
```

**Composite Runners Handle**:
- Phase execution (atomic task coordination)
- Timeout management within tasks
- Audit creation with combined metadata
- Artifact preservation from all phases

**Orchestrator Only**:
- Strategy selection (GLPK vs Hybrid)
- Timeout fallback (GLPK → Hybrid)
- Final audit recording

**No Retries**: GLPK is deterministic - retrying won't help.

### Process-Level Errors

**Process Execution** (`Process::execute()`):

**Timeout Detection**:
```php
if ($exitCode === 124) {  // timeout command exit code
    throw new GlpkTimeoutException("GLPK timeout");
}
```

**Other Errors**:
```php
if ($exitCode !== 0) {
    throw new GlpkException("GLPK failed: $stderr");
}
```

**Command Structure**:
```bash
timeout 60s glpsol --model phase1.mod --data data.dat --output solution.sol 2>&1
```

---

## Auditing

### Audit Trail Purpose

1. **Transparency**: Complete record of execution
2. **Debugging**: Reconstruct what happened
3. **Compliance**: Proof of fair process
4. **Analysis**: Performance metrics

### Audit Types for GLPK

**`LotteryAuditType::GROUP_EXECUTION`**:
- Created after each group solves successfully
- Contains task results (S value, distribution, strategy used)
- Metadata includes execution time, algorithm used

**`LotteryAuditType::FAILURE`**:
- Created on any GLPK error
- Contains exception details, stack trace
- Enables debugging without logs

### Audit Creation

**Location**: `Glpk::auditTask()`

```php
protected function auditTask(
    LotteryManifest $manifest,
    TaskResult $result,
    string $status
): void {
    LotteryAudit::create([
        'lottery_id' => $manifest->lotteryId,
        'type' => LotteryAuditType::GROUP_EXECUTION,
        'status' => $status,
        'data' => [
            'task' => $result->task->value,
            'result' => $result->data,
            'metadata' => $result->metadata,
        ],
        'uuid' => $manifest->uuid,
    ]);
}
```

### What Gets Audited

**GLPK Distribution (Direct Strategy)**:
```json
{
  "task": "glpk_distribution",
  "result": {
    "distribution": {
      "1": 10,
      "2": 20,
      "3": 15
    }
  },
  "metadata": {
    "timeout_ms": 30000,
    "phase1": {
      "min_satisfaction": 3,
      "timeout_ms": 500,
      "time_ms": 234.56
    },
    "phase2": {
      "min_satisfaction": 3,
      "timeout_ms": 29500,
      "time_ms": 456.78
    },
    "artifacts": {
      "phase1_model.mod": "...",
      "phase1_data.dat": "...",
      "phase1_solution.sol": "...",
      "phase2_model.mod": "...",
      "phase2_data.dat": "...",
      "phase2_solution.sol": "..."
    }
  }
}
```

**Hybrid Distribution (Binary Search Strategy)**:
```json
{
  "task": "hybrid_distribution",
  "result": {
    "distribution": {
      "1": 10,
      "2": 20,
      "3": 15
    }
  },
  "metadata": {
    "timeout_ms": 30000,
    "iterations": 7,
    "feasible_steps": 5,
    "phase1": {
      "min_satisfaction": 5,
      "timeout_ms": 15000,
      "time_ms": 12345.67,
      "candidates_tested": [1, 5, 10, 7, 6, 5]
    },
    "phase2": {
      "min_satisfaction": 5,
      "timeout_ms": 15000,
      "time_ms": 678.90
    },
    "artifacts": {
      "phase1_candidate_1.sol": "...",
      "phase1_candidate_5.sol": "...",
      "phase2_model.mod": "...",
      "phase2_data.dat": "...",
      "phase2_solution.sol": "..."
    }
  }
}
```

**Failure**:
```json
{
  "exception": "GlpkTimeoutException",
  "message": "Phase 2 timeout exceeded",
  "trace": "...",
  "manifest": {...}
}
```

### UUID Grouping

All audits from single execution share `manifest->uuid`:
- Enables tracing complete execution flow
- Links Phase 1 → Phase 2 → Distribution
- Supports multiple lottery runs (each gets new UUID)

---

## Performance & Benchmarks

### Empirical Results

**See**: `BENCHMARKS.md` for comprehensive analysis

**Summary** (random preferences):

| Size | Success Rate | Avg Time | Max Time | Timeout Used |
|------|--------------|----------|----------|--------------|
| 5×5  | 100.00% | 205ms | ~250ms | 60s |
| 10×10 | 100.00% | 350ms | ~400ms | 60s |
| 20×20 | 99.98% | 469ms | 23.6s | 60s |
| 25×25 | 99.98% | 333ms | 116.8s | 120s |
| 30×30 | 99.52% | 696ms | 29.8s | 60s |

**Key Observations**:
1. **Bimodal Distribution**: Most problems solve in <1s, but some take >10s
2. **Heavy Tails**: Max time can be 40-100× average
3. **Deterministic Timeouts**: Same problem consistently times out (not random)

### Performance Characteristics

**Fast Cases** (80-90% of problems):
- Diverse preferences (no degeneracy)
- Solve in <1 second
- Direct GLPK works perfectly

**Slow Cases** (10-20% of problems):
- High preference similarity
- Degenerate optimal solutions
- Take 5-30 seconds
- Binary search may be faster

**Timeout Cases** (<1% of problems):
- Extreme degeneracy
- Phase 1 cannot converge
- Binary search required

### Timeout Strategy

**Current Settings**:
- Overall timeout: 30 seconds per task
- Phase 1 (direct GLPK): 0.5 seconds (aggressive early fallback)
- Strategy threshold: 25 families (≥25 → Hybrid from start)
- Hybrid distribution: Full 30s timeout (binary search + Phase2)

**Rationale**:
- 0.5s Phase1 timeout catches degeneracy very early
- 25 family threshold prevents Phase1 timeouts on large specs
- Hybrid strategy gets full budget for binary search
- Phase2 rarely times out (has proven feasible S)

### Production Sizing

**Safe Zone** (instant execution):
- Up to 70×70 problems
- <20 seconds typical
- 99.5%+ success rate

**Caution Zone** (may be slow):
- 70-100 entities
- 20-60 seconds possible
- Binary search may activate

**Danger Zone** (not recommended):
- 100+ entities
- Timeouts likely
- Consider splitting into smaller groups

---

## Configuration

### Config File

**Location**: `config/lottery.php`

```php
'solvers' => [
    'glpk' => [
        'class' => GlpkSolver::class,
        'config' => [
            'glpsol_path' => env('GLPSOL_PATH', '/usr/bin/glpsol'),
            'temp_dir' => env('GLPK_TEMP_DIR', sys_get_temp_dir()),
            'timeout' => env('GLPK_TIMEOUT', 30),
            'glpk_phase1_timeout' => env('GLPK_PHASE1_TIMEOUT', 0.5),
            'glpk_phase1_max_size' => env('GLPK_PHASE1_MAX_SIZE', 25),
        ],
    ],
],
```

### Environment Variables

**`GLPSOL_PATH`**:
- Default: `/usr/bin/glpsol`
- Production: Usually `/usr/bin/glpsol`
- Development: May be custom path

**`GLPK_TEMP_DIR`**:
- Default: System temp dir (`/tmp` on Linux)
- Can be customized for debugging (persistent files)

**`GLPK_TIMEOUT`**:
- Default: 60 seconds
- Can be increased for large problems
- Applies to individual task executions

### Docker Configuration

**Container**: GLPK installed in PHP container

```dockerfile
RUN apt-get update && apt-get install -y glpk-utils
```

**Verification**:
```bash
docker exec php glpsol --version
# GLPSOL 5.0
```

### Testing Configuration

**Test Environment**: Same as production

**Fixture Data**: `tests/Fixtures/universe.sql`
- Pre-populated test data (20-30x faster than factories)
- Realistic preference distributions
- Covers edge cases

---

## Testing Strategy

### Test Levels

**Unit Tests**:
- `ModelGeneratorTest`: Verify .mod file structure
- `DataGeneratorTest`: Verify .dat file format
- `SolutionParserTest`: Verify .sol parsing
- `TaskRunnerTest`: Verify TaskRunner infrastructure

**Integration Tests**:
- `GlpkTest`: End-to-end GLPK execution
- `MinSatisfactionTest`: Phase 1 + binary search
- `UnitDistributionTest`: Phase 2 execution

**Stress Tests**:
- `GlpkTimeoutTest`: 50×50, 60×60, 70×70 problems
- `GlpkBenchmarkTest`: Performance regression detection

**Feature Tests**:
- `GlpkSolverTest`: Full lottery execution
- `LotterySystemIntegrationTest`: Multi-type scenarios

### Test Coverage

**Critical Paths**:
- ✅ Direct GLPK success (Phase 1 → Phase 2)
- ✅ Phase 1 timeout → binary search fallback
- ✅ Infeasible problem detection
- ✅ Timeout exceeded error handling
- ✅ Audit trail creation

**Edge Cases**:
- ✅ Mock units (string IDs)
- ✅ Single family/unit
- ✅ Identical preferences
- ✅ Opposite preferences
- ✅ Empty preferences (auto-fill)

### Regression Tests

**Purpose**: Ensure model changes don't break functionality

**Test**: `GlpkTimeoutTest::Phase 2 with impossibly strict S returns INFEASIBLE`

**What It Catches**:
- Split constraint bug (OPTIMAL with all zeros)
- Equality constraint fix verification
- Ensures GLPK returns proper INFEASIBLE status

**Example**:
```php
test('Phase 2 with impossibly strict S returns INFEASIBLE', function () {
    $families = [
        1 => [10, 20],
        2 => [20, 10],
    ];
    $spec = new LotterySpec($families, [10, 20]);

    $phase2 = app(UnitDistribution::class);

    // S=1 is impossible (both want different first choices)
    expect(fn () => $phase2->execute($spec, 300, ['min_satisfaction' => 1]))
        ->toThrow(GlpkInfeasibleException::class);
});
```

---

## Known Edge Cases

### 1. OPTIMAL with All Zeros (FIXED)

**Problem**: With split constraints (`>= 1` and `<= 1`), GLPK could return "OPTIMAL" status with all decision variables set to 0.

**Root Cause**: GLPK's branch-and-bound relaxes `>= 1` when constraints conflict, but honors `<= 1`.

**Fix**: Changed to equality constraints (`= 1`) in Phase 1 and Phase 2 models.

**Detection**: `SolutionParser::extractAssignments()` checks for empty picks and throws `GlpkException`.

**Status**: ✅ Fixed (2025-12-18)

### 2. Generator Double Advancement

**Problem**: Using `foreach` with `Generator::send()` causes double advancement (skips every other `yield`).

**Root Cause**: `foreach` calls `next()` internally, `send()` also advances, resulting in two steps per iteration.

**Fix**: Use `while ($gen->valid())` with manual `current()/send()` calls.

**Example**:
```php
// ❌ Wrong (double advancement)
foreach ($generator as $value) {
    $generator->send($feedback);
}

// ✅ Correct (manual iteration)
while ($generator->valid()) {
    $value = $generator->current();
    $generator->send($feedback);
}
```

**Status**: ✅ Fixed in binary search implementation

### 3. Degenerate Problems

**Problem**: Many equivalent optimal solutions cause exponential branch exploration.

**Example**: All families have identical preferences → any permutation is optimal.

**Mitigation**: Binary search fallback for Phase 1 timeouts.

**Status**: ✅ Handled by fallback strategy

### 4. Infeasibility vs Timeout

**Problem**: How to distinguish "no solution exists" from "too slow to find solution"?

**Solution**:
- GLPK proves infeasibility mathematically (simplex certificate)
- Timeouts are pure computational limits
- Different exceptions: `GlpkInfeasibleException` vs `GlpkTimeoutException`

**Status**: ✅ Properly handled

### 5. Floating Point Precision

**Problem**: GLPK returns decimal activity values (e.g., `0.99999` instead of `1.0`).

**Solution**: `SolutionParser` uses threshold: `activity >= 0.99` counts as `1`.

**Example**:
```php
if ($activity >= 0.99) {
    $picks[$familyId] = $unitId;
}
```

**Status**: ✅ Handled in parser

---

## Future Improvements

### 1. Predictive Timeout Detection

**Goal**: Predict before execution if problem will timeout

**Approach**:
- Extract metrics from `LotterySpec` (preference diversity, size, etc.)
- Build ML model on benchmark data
- Skip Phase 1 if timeout predicted, go straight to binary search

**Benefits**: Faster execution, better UX

**Status**: Research phase (see `BENCHMARKS.md`)

### 2. Incremental Binary Search

**Goal**: Start binary search earlier (don't wait for Phase 1 timeout)

**Approach**:
- Run Phase 1 with 5s timeout
- Simultaneously start binary search
- Use whichever finishes first

**Benefits**: Reduce worst-case latency

**Status**: Design phase

### 3. Warm Start Phase 2

**Goal**: Use Phase 1 solution as starting point for Phase 2

**Approach**:
- GLPK supports warm start via `.bas` files
- Export Phase 1 basis, import to Phase 2

**Benefits**: Faster Phase 2 (skip initial simplex iterations)

**Status**: Experimental

### 4. Parallel Candidate Testing

**Goal**: Test multiple binary search candidates simultaneously

**Approach**:
- Spawn multiple GLPK processes
- Test candidates in parallel
- Aggregate results

**Benefits**: 3-5x speedup on binary search

**Challenges**: Resource management, process coordination

**Status**: Not prioritized

### 5. Alternative Solvers

**Goal**: Compare GLPK with other MIP solvers

**Candidates**:
- SCIP (open source)
- CBC (open source)
- Gurobi (commercial, academic license)

**Benefits**: Potentially faster, more robust

**Challenges**: Integration effort, licensing

**Status**: Not prioritized

---

## References

### GLPK Documentation

- [GLPK Official Site](https://www.gnu.org/software/glpk/)
- [GMPL Language Reference](https://www.gnu.org/software/glpk/gmpl.pdf)
- [GLPK Solver Manual](https://www.gnu.org/software/glpk/glpk.pdf)

### Related Documentation

- `LOTTERY.md`: High-level lottery system overview
- `BENCHMARKS.md`: Performance analysis and metrics
- `REFACTORING-2025-12-17.md`: TaskRunner architecture evolution
- `config/lottery.php`: Solver configuration

### Academic References

- **Max-Min Fairness**: Rawls, J. (1971). *A Theory of Justice*
- **Assignment Problems**: Kuhn, H. W. (1955). "The Hungarian Method"
- **Integer Programming**: Nemhauser & Wolsey (1988). *Integer and Combinatorial Optimization*

### Code Locations

**Primary Classes**:
- `app/Services/Lottery/Solvers/Glpk/Glpk.php` (strategy orchestrator)
- `app/Services/Lottery/Solvers/Glpk/TaskRunners/GlpkDistribution.php` (composite)
- `app/Services/Lottery/Solvers/Glpk/TaskRunners/HybridDistribution.php` (composite)
- `app/Services/Lottery/Solvers/Glpk/TaskRunners/MinSatisfaction.php` (atomic)
- `app/Services/Lottery/Solvers/Glpk/TaskRunners/UnitDistribution.php` (atomic)

**Tests**:
- `tests/Unit/Lottery/Glpk/`
- `tests/Stress/Lottery/GlpkTimeoutTest.php`
- `tests/Feature/Lottery/GlpkSolverTest.php`

**Configuration**:
- `config/lottery.php`
- `.env` (GLPSOL_PATH, GLPK_TIMEOUT)

---

## Appendix: Glossary

**GLPK**: GNU Linear Programming Kit - open source MIP solver

**MIP**: Mixed Integer Programming - optimization with integer constraints

**LP**: Linear Programming - optimization with continuous variables only

**GMPL**: GNU MathProg Language - GLPK's modeling language

**Simplex**: Algorithm for solving LP problems

**Branch-and-Bound**: Algorithm for solving MIP problems (explores solution tree)

**Degeneracy**: Multiple equivalent optimal solutions (causes slow convergence)

**Max-Min Fairness**: Maximize the minimum value (ensure nobody gets worst outcome)

**Lexicographic Optimization**: Multi-objective with strict priority ordering

**Infeasibility**: Mathematical proof that no solution exists

**Feasibility**: At least one solution exists

**Objective Function**: What we're optimizing (minimize/maximize)

**Constraint**: Restriction on valid solutions

**Decision Variable**: What the solver chooses (assignment x[c,v])

**Parameter**: Input data (preferences p[c,v])

---

**Document Version**: 2.0 (Composite TaskRunner Architecture)
**Last Review**: 2025-12-25
**Next Review**: 2026-03-01 (or on major changes)
