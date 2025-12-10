<!-- Copilot - Pending review -->

# Lottery System Reference

The lottery is MTAV's primary feature: a **one-time, fair housing assignment** for cooperative projects using mathematical optimization to maximize overall satisfaction.

## Current Status
- ✅ HTTP layer (controllers, validation, authorization)
- ✅ Preference management (dynamic with non-biased randomized auto-fill)
- ✅ Orchestration (two-phase: by unit type, then orphan redistribution)
- ✅ Persistence & audit trail (INIT, GROUP_EXECUTION, PROJECT_EXECUTION, FAILURE, INVALIDATE)
- ✅ Real-time UI feedback (Audit.vue polling component)
- ⏳ GlpkSolver (implemented, needs testing)
- ⏳ Failure notifications (low priority)

## Core Rules

**One-Time & Immutable**: Binary state (pending/completed), runs once per project, permanent assignments.

**Preference-Based**: Families rank ALL units of their assigned type before execution. Locked (`is_published=false`) during execution. Cannot be changed after.

**Type Segregation**: Each family → ONE UnitType. Algorithm runs independently per type, then handles orphans cross-type in Phase 2.

**Global Optimization**: Not random, not first-come-first-served. Math-based optimization maximizes overall satisfaction considering all preferences simultaneously.

## Data Model

### Schema

**units table**:
```
id, project_id, unit_type_id, family_id (NULL before lottery, set after),
identifier, timestamps, soft_deletes
UNIQUE(identifier, project_id)
```

**unit_preferences pivot**:
```
family_id, unit_id, order (1=highest), timestamps
PRIMARY KEY(family_id, unit_id)
```

**events table**:
```
id, project_id, creator_id, title, description, type (EventType::LOTTERY),
start_date, is_published (TRUE=pending, FALSE=executing/executed),
timestamps, soft_deletes
```

**lottery_audits table**:
```
id, project_id, execution_uuid, type (INIT, GROUP_EXECUTION, PROJECT_EXECUTION, FAILURE, INVALIDATE),
phase, group_number, manifest, result, error_message, timestamps
```

### Model Relationships

```php
// Family
public function preferences(): BelongsToMany
{
    return $this->belongsToMany(Unit::class, 'unit_preferences')
        ->withPivot('order')
        ->orderByPivot('order');
}
public function unitType(): BelongsTo { return $this->belongsTo(UnitType::class); }

// Unit
public function family(): BelongsTo
{
    return $this->belongsTo(Family::class)->withTrashed();
}
public function unitType(): BelongsTo { return $this->belongsTo(UnitType::class); }
public function preferences(): BelongsToMany
{
    return $this->belongsToMany(Family::class, 'unit_preferences')
        ->withPivot('order')->orderByPivot('order');
}

// Event
public function isLottery(): bool { return $this->type === EventType::LOTTERY; }
public function scopeLottery(Builder $query): void { $query->whereType(EventType::LOTTERY); }

// Project
public function lotteryEvent(): HasOne
{
    return $this->hasOne(Event::class)->lottery();
}
```

### State Machine

- **Ready**: `is_published=true`, `deleted_at=NULL` (families can modify preferences)
- **Executing**: `is_published=false`, `deleted_at=NULL` (async processing, preferences locked)
- **Completed**: `is_published=false`, `deleted_at=timestamp` (immutable, assignments final)

**Key field**: `units.family_id` is the source of truth (NULL=unassigned, set=assigned)

## Key Services & Methods

### Preference Management
**`app/Services/Lottery/PreferencesService.php`**:

```php
// Returns complete list: explicit prefs + randomized auto-fill for missing
public function preferences(Family $family): Collection
{
    $this->consistencyService->sanitizeBeforeFetch($family);
    $family->loadMissing(['preferences', 'unitType.units']);
    $remaining = $family->unitType->units
        ->whereNotIn('id', $family->preferences->pluck('id'))
        ->shuffle(); // Non-biased randomization
    return $family->preferences->concat($remaining);
}

// Replace all preferences atomically (validates completeness & lottery not executing)
public function updatePreferences(Family $family, array $preferences): void

// Add missing units randomized, validate complete set before lottery start
public function addMissingPreferences(Family $family): void
```

**`app/Services/Lottery/ConsistencyService.php`**:

```php
// Remove invalid preferences (type mismatches, deleted units, wrong project)
public function sanitizeBeforeFetch(Family $family): void

// Ensure new preferences complete and valid, lottery hasn't started execution
public function validateBeforeUpdate(Family $family, array $preferences): void
```

**Why Non-Biased?** Auto-fill randomized (not ID-sorted) prevents implicit bias toward low-ID units. Deterministic per family, persistent in DB.

### Execution & Orchestration
**`app/Services/Lottery/ExecutionService.php`**:

```php
// Validates data completeness, atomically sets is_published=false (reserves lottery),
// dispatches async job. Returns immediately.
public function execute(Event $lottery): void

// Bulk updates units.family_id with assignment results, soft-deletes lottery event
public function applyResults(ExecutionResult $result, Event $lottery): void

// Releases lottery: restores is_published=true, removes assignments, creates INVALIDATE audit
public function invalidate(Event $lottery): void
```

**`app/Services/Lottery/LotteryOrchestrator.php`**:

```php
// Two-phase orchestration:
// 1. Run solver per unit type → collect orphans
// 2. If orphans exist → redistribute cross-type
// Treats solver as black box: LotterySpec → ExecutionResult (picks + orphans)
public function execute(Event $lottery, LotterySpec $spec): ExecutionResult
```

**Audit Integration** (`app/Services/Lottery/AuditService.php`):
- `init()` - INIT audit at execution start (stores manifest)
- `audit()` - GROUP_EXECUTION or PROJECT_EXECUTION records per phase
- `exception()` - FAILURE with error details + user-friendly message
- `invalidate()` - INVALIDATE record
- All audits grouped by `execution_uuid`

### Solvers

All implement `SolverInterface`:

```php
public function execute(LotterySpec $spec): ExecutionResult
// $spec: families with preferences, available units, constraints
// returns: assigned picks + orphan families (couldn't assign)
```

Implementations:
- **RandomSolver** ✅ - Shuffles & pairs (testing only)
- **TestSolver** ✅ - Deterministic ID-based pairing (reproducible tests)
- **GlpkSolver** - Two-phase max-min fairness (GLPK 5.0 at `/usr/bin/glpsol`)
  - Synchronous execution, 2-5s for 20-100 entities
  - Local only (no external deps, privacy-preserving)
  - Installed in Docker

### Audit System
**`app/Services/Lottery/AuditService.php`** + **`app/Models/LotteryAudit.php`**:
- `init()` - Create INIT audit at execution start (stores manifest)
- `audit()` - Create GROUP_EXECUTION or PROJECT_EXECUTION records
- `exception()` - Create FAILURE with error details + user-friendly message
- `invalidate()` - Create INVALIDATE record
- All audits grouped by `execution_uuid`

### Frontend
**`resources/js/components/lottery/`**:
- `shared/Audit.vue` - Real-time polling (1s interval), 5min timeout, progress bar with 40s/group estimate, failure display
- `member/PreferencesManager.vue` - Drag-and-drop preference ordering, auto-save
- `admin/LotteryManagement.vue` - Admin controls & execution

## Configuration

**`config/lottery.php`**:
```php
'default' => env('LOTTERY_SOLVER', 'random'),
'solvers' => [
    'random' => ['solver' => RandomSolver::class],
    'test' => ['solver' => TestSolver::class],
    'glpk' => ['solver' => GlpkSolver::class, 'config' => [...]],
]
```

Set `LOTTERY_SOLVER=glpk` in `.env` to use production solver (after testing).

## Why Non-Biased Preferences?

Auto-fill randomized instead of sorted by ID:
- Prevents implicit bias toward low-ID units
- Fair to all families without explicit preferences
- Deterministic per family (same order on repeat visits)
- Persistent in DB (no dynamic shuffling confusion)
- Implemented: `PreferencesService::missingPreferences()` calls `->shuffle()`

## Why Two-Phase?

Handles unbalanced scenarios (families ≠ units):
- Phase 1 optimizes within unit types
- Phase 2 gives orphans second chance across types
- Orchestrator treats solver as black box (no assumptions about input balance)

## Why Local GLPK?

- No external dependencies or internet
- Synchronous execution, simple error handling
- Privacy: data never leaves server
- 2-5 seconds for typical 20-100 entity problems
- Installed in Docker: GLPK 5.0 at `/usr/bin/glpsol`

## Testing

**Universe fixture** (`tests/Fixtures/universe.sql`): Projects, families, units, pre-seeded preferences. 20-30x faster than factories.

Run tests:
```bash
mtav pest --filter Lottery
mtav pest --testsuite Lottery
```

Key test files:
- `tests/Feature/Lottery/PreferencesValidationTest.php` - Preference management
- `tests/Feature/Lottery/ExecutionServiceTest.php` - Validation & authorization
- `tests/Feature/Lottery/AsyncExecutionTest.php` - Queue + audit persistence

## Quick Reference

**Check lottery state**:
```php
$lottery->is_published        // true = ready, false = executing/completed
$lottery->deleted_at          // null = active, timestamp = completed
!$lottery->is_published && !$lottery->deleted_at  // currently executing

// Check if family can modify preferences
$family->project->lotteryEvent?->is_published === true
```

**Get preferences**: `$this->lotteryService->preferences($family)` (always complete)

**Update preferences**: `$this->lotteryService->updatePreferences($family, $unitIds)`

**Execute lottery**: `$this->lotteryService->execute($lottery)` (returns immediately, async)

**Debug failure**: `LotteryAudit::where('type', ExecutionType::FAILURE)->latest()->first()`

**Invalidate**: `$this->lotteryService->invalidate($lottery)` (reset & allow re-execution)

## References

- **LOTTERY.md** - Full technical details, code implementations, entity relationships
- **testing/LOTTERY_TESTS.md** - Test reference & testing fixtures
- **KNOWLEDGE_BASE.md** - Business domain concepts
- **.github/copilot-instructions.md** - Code style & conventions

---

# Missing Piece: Group-Level Algorithm Switching (Per-Type Degeneracy Handling)

## The Problem

When executing the lottery at project level, we run the solver **independently for each unit type** (Phase 1 of orchestration). Empirical testing reveals that:

- **Unit types ≤30 families**: GLPK performs reliably (<2 seconds)
- **Unit types ≥40 families**: GLPK can timeout (301+ seconds observed)
- **Decision point**: At execution time, not design time

**Current approach**: Use one algorithm (GLPK or Greedy) for entire project.

**Required approach**: Use GLPK for well-behaved types, switch to Greedy for problematic types—**within a single lottery execution**.

This is **fundamentally a per-group decision**, not per-project. The complexity comes from maintaining **transactional consistency**: either the entire lottery succeeds (all types executed) or the entire lottery is reverted (no partial state).

## Architecture Requirements

### 1. Detection Exception: `DegeneracyDetectedException`

**When to throw**:
- After calculating group manifest (families + units for a specific type)
- BEFORE invoking solver
- Based on empirical thresholds from `config('lottery.solvers.glpk.config.degeneracy_detection')`

**What to include**:
```php
class DegeneracyDetectedException extends Exception
{
    public function __construct(
        public readonly int $size,                    // max(num_families, num_units)
        public readonly float $dominanceRatio,        // % of families with same preferences
        public readonly string $reason,               // 'size_exceeds_critical' | 'degeneracy_pattern'
        public readonly int $groupNumber,             // Which type in orchestration
    ) {}

    // Accessor for recovery logic
    public function shouldUseGreedy(): bool
    {
        return $this->reason === 'size_exceeds_critical' || $this->dominanceRatio >= 0.80;
    }
}
```

**Where to throw** (in solver flow):
```php
// app/Services/Lottery/ExecutionService.php or SolverFactory
if ($this->detectDegeneracy($spec)) {
    throw new DegeneracyDetectedException(
        size: max($spec->families->count(), $spec->units->count()),
        dominanceRatio: $this->calculateDominanceRatio($spec->families),
        reason: $this->determinateReason(...),
        groupNumber: $spec->groupNumber, // Track which type in orchestration
    );
}
```

### 2. Orchestrator Pattern: Group-Level Recovery

**Current `LotteryOrchestrator::execute()`** is atomic per type:
```php
foreach ($unitTypes as $unitType) {
    $result = $this->solver->execute($spec);  // Single algo for all types
    // No recovery option
}
```

**Required pattern** (pseudo-code):
```php
public function execute(Event $lottery, LotterySpec $spec): ExecutionResult
{
    $executionUuid = Str::uuid();
    $results = [];
    $groupNumber = 0;

    foreach ($unitTypes as $unitType) {
        $groupNumber++;
        $groupSpec = $this->buildGroupSpec($unitType, $lottery, $spec);

        try {
            // Phase 1: Attempt with primary solver (GLPK)
            $result = $this->primarySolver->execute($groupSpec);
            $results[$unitType->id] = $result;

            // Audit: GROUP_EXECUTION with solver=glpk, time=X
            $this->auditService->audit(
                executionUuid: $executionUuid,
                type: AuditType::GROUP_EXECUTION,
                groupNumber: $groupNumber,
                manifest: $groupSpec,
                result: $result,
                solverUsed: 'glpk',
                executionTime: $elapsed,
            );

        } catch (DegeneracyDetectedException $e) {
            // Phase 1 FAILED: Degeneracy detected
            // Decision: Switch to alternate algorithm for THIS GROUP ONLY

            // Audit: Record detection (not failure yet)
            $this->auditService->audit(
                executionUuid: $executionUuid,
                type: AuditType::DEGENERACY_DETECTED,
                groupNumber: $groupNumber,
                manifest: $groupSpec,
                error: $e->getMessage(),
                solverUsed: 'glpk',
                degeneracyReason: $e->reason,
            );

            // Phase 1.5: Request confirmation or auto-switch?
            // Option A: Auto-switch (if configured: greedy_on_degeneracy=true)
            if (config('lottery.solvers.glpk.config.auto_fallback_on_degeneracy')) {
                $result = $this->fallbackSolver->execute($groupSpec);

                $this->auditService->audit(
                    executionUuid: $executionUuid,
                    type: AuditType::GROUP_EXECUTION,
                    groupNumber: $groupNumber,
                    manifest: $groupSpec,
                    result: $result,
                    solverUsed: 'greedy_fallback',
                    executionTime: $elapsed,
                    note: 'Fallback after degeneracy detection',
                );

                $results[$unitType->id] = $result;

            } else {
                // Option B: Manual confirmation (UI workflow)
                // Store state in cache/DB, return partial result with "needs_confirmation"
                throw new DegeneracyConfirmationRequired(
                    executionUuid: $executionUuid,
                    groupNumber: $groupNumber,
                    unitType: $unitType,
                    exception: $e,
                    // UI will show: "Unit Type X has complex preferences. Use faster
                    // algorithm (Greedy) with same fairness guarantees?"
                );
            }
        }

        // Both paths: continue to next type
    }

    // Phase 2: Orphan redistribution (unchanged)
    $orphans = collect($results)->pluck('orphans')->flatten();
    // ...

    // Final: Consistency check
    $this->validateExecutionConsistency($results, $orphans);

    // Return combined result
    return new ExecutionResult(
        picks: $this->mergePicks($results),
        orphans: $orphans,
        executionUuid: $executionUuid,
        groupResults: $results,  // NEW: Track per-group solver used
    );
}
```

### 3. Transactional Safety: All-or-Nothing Semantics

**Critical requirement**: If any phase fails, entire lottery is reverted.

**Current state**: Results applied atomically via `ExecutionService::applyResults()`.

**Required enhancement**:

```php
// app/Services/Lottery/ExecutionService.php

public function execute(Event $lottery): void
{
    $executionUuid = Str::uuid();

    try {
        // Step 1: Validate data integrity (existing)
        $this->validatePrerequisites($lottery);

        // Step 2: Reserve lottery (existing)
        // Mark is_published=false atomically
        DB::transaction(function () use ($lottery) {
            $lottery->update(['is_published' => false]);
        });

        // Step 3: Execute orchestration with recovery
        try {
            $result = DB::transaction(function () use ($lottery, $executionUuid) {
                return $this->orchestrator->execute(
                    lottery: $lottery,
                    spec: $this->buildSpec($lottery),
                    executionUuid: $executionUuid,
                    // NEW: Callback for confirmation handling
                    onDegeneracyDetected: function (DegeneracyDetectedException $e) {
                        return $this->handleDegeneracyConfirmation($e);
                    },
                );
            });

            // Step 4: Apply results (existing, but within transaction)
            // units.family_id = X for all assigned families
            DB::transaction(function () use ($result, $lottery, $executionUuid) {
                $this->applyResults($result, $lottery, $executionUuid);
                // Soft-delete lottery: is_published=false, deleted_at=now()
                $lottery->delete();
            });

            // Step 5: Audit final success (existing)
            $this->auditService->audit(
                type: AuditType::PROJECT_EXECUTION,
                result: "Success",
                executionUuid: $executionUuid,
            );

        } catch (DegeneracyConfirmationRequired $e) {
            // User hasn't confirmed yet: SUSPEND state
            // Lottery remains is_published=false, deleted_at=NULL
            // Units have NO family_id assignments yet

            $this->auditService->audit(
                type: AuditType::DEGENERACY_PENDING_CONFIRMATION,
                error: $e->getMessage(),
                executionUuid: $executionUuid,
                suspensionData: [
                    'groupNumber' => $e->groupNumber,
                    'unitType' => $e->unitType->name,
                    'size' => $e->exception->size,
                ],
            );

            // Return to user for confirmation
            // Frontend shows: "Confirm using Greedy for Type X?"
            // Cache/store the suspended execution state
            Cache::put(
                key: "lottery_suspended:{$executionUuid}",
                value: $e,
                minutes: 15,
            );

            // Signal to frontend that confirmation needed
            throw $e;  // Will be caught by controller, displayed to admin

        } catch (Exception $e) {
            // ANY OTHER FAILURE: Full rollback
            DB::transaction(function () use ($lottery, $executionUuid) {
                // Revert lottery to "ready" state
                $lottery->update([
                    'is_published' => true,
                    'deleted_at' => null,
                ]);

                // Clear any partial assignments (should be none if transaction worked)
                // (Handled by DB rollback)
            });

            $this->auditService->exception(
                type: AuditType::FAILURE,
                error: $e->getMessage(),
                executionUuid: $executionUuid,
            );

            throw LotteryExecutionException::fromOriginal($e, $executionUuid);
        }
    } catch (DegeneracyConfirmationRequired $e) {
        // Let this bubble to controller for UI handling
        throw $e;
    } catch (LotteryExecutionException $e) {
        // Controlled failure, already audited
        throw $e;
    } catch (Exception $e) {
        // Unexpected error, log and fail safely
        Log::critical("Lottery execution critical error: {$e->getMessage()}");
        throw new LotteryExecutionException("Unexpected error during lottery execution", 500, $e);
    }
}
```

### 4. Confirmation Workflow

When `DegeneracyConfirmationRequired` is thrown:

**Backend state**:
- Lottery: `is_published=false` (locked), `deleted_at=NULL` (not executed yet)
- Units: No `family_id` assignments
- Audit: DEGENERACY_PENDING_CONFIRMATION record with suspension data
- Cache: Suspended execution state stored for 15 minutes

**Frontend UI**:
```
┌─────────────────────────────────────────────────────┐
│ ⚠️ Complex Preferences Detected                      │
│                                                     │
│ Unit Type: Apartments                               │
│ Number of families: 42                              │
│                                                     │
│ The standard optimization algorithm (GLPK) would   │
│ take too long (>5 minutes) to solve for this       │
│ group.                                              │
│                                                     │
│ We can use a faster, equally fair algorithm        │
│ (Greedy with randomized rotation) instead.         │
│                                                     │
│ ┌──────────────────────────────────────────────┐   │
│ │ ✓ Continue with Greedy algorithm              │   │
│ │ ✗ Cancel lottery execution                    │   │
│ └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

**User action: "Continue with Greedy"**:
```php
// POST /api/lottery/{executionUuid}/confirm-degeneracy
public function confirmDegeneracy(Request $request, string $executionUuid)
{
    // 1. Retrieve suspended state from cache
    $suspended = Cache::get("lottery_suspended:{$executionUuid}");
    if (!$suspended) {
        return response()->json(['error' => 'Confirmation expired'], 410);
    }

    // 2. Validate authorization (admin only)
    $this->authorize('admin');

    // 3. Resume execution with fallback solver
    try {
        DB::transaction(function () use ($suspended, $executionUuid) {
            // Continue from same point, but use greedy for that group
            $result = $this->orchestrator->executeGroupWithFallback(
                suspended: $suspended,
                executionUuid: $executionUuid,
                fallbackSolver: $this->fallbackSolver,
            );

            // Apply all results (for this group + all previous groups)
            $this->executionService->applyResults($result, $suspended->lottery, $executionUuid);

            // Mark lottery as completed
            $suspended->lottery->delete();
        });

        // Audit: Confirmation accepted, execution resumed
        $this->auditService->audit(
            type: AuditType::DEGENERACY_CONFIRMED,
            executionUuid: $executionUuid,
            fallbackAlgorithm: 'greedy',
        );

        return response()->json(['status' => 'completed']);

    } catch (Exception $e) {
        // If resume fails, revert entirely
        $suspended->lottery->update(['is_published' => true, 'deleted_at' => null]);
        throw new LotteryExecutionException("Failed to resume execution: {$e->getMessage()}");
    }
}
```

**User action: "Cancel"**:
```php
// POST /api/lottery/{executionUuid}/cancel
public function cancelLottery(Request $request, string $executionUuid)
{
    $suspended = Cache::get("lottery_suspended:{$executionUuid}");
    if (!$suspended) {
        return response()->json(['error' => 'Confirmation expired'], 410);
    }

    // Full rollback
    DB::transaction(function () use ($suspended) {
        $suspended->lottery->update(['is_published' => true, 'deleted_at' => null]);
        // All partial assignments auto-rollback with DB transaction
    });

    $this->auditService->audit(
        type: AuditType::DEGENERACY_REJECTED,
        executionUuid: $executionUuid,
    );

    Cache::forget("lottery_suspended:{$executionUuid}");
    return response()->json(['status' => 'cancelled']);
}
```

## Implementation Checklist

- [ ] Create `DegeneracyDetectedException` class
- [ ] Create `DegeneracyConfirmationRequired` exception
- [ ] Add `DEGENERACY_DETECTED`, `DEGENERACY_PENDING_CONFIRMATION`, `DEGENERACY_CONFIRMED`, `DEGENERACY_REJECTED` audit types
- [ ] Implement `detectDegeneracy()` in solver validation layer (uses empirical thresholds from config)
- [ ] Modify `LotteryOrchestrator::execute()` to catch `DegeneracyDetectedException` per group
- [ ] Enhance `ExecutionService::execute()` with transactional safety and fallback handling
- [ ] Implement `confirmDegeneracy()` and `cancelLottery()` endpoints
- [ ] Update `LotteryAudit` model to support new audit types
- [ ] Add cache-based state management for suspended executions
- [ ] Create UI component for degeneracy confirmation dialog
- [ ] Add tests for:
  - Degeneracy detection triggering correctly
  - Transactional rollback on failure
  - Confirmation workflow (confirm, cancel, timeout)
  - Per-group algorithm switching (GLPK + Greedy in same execution)
  - Audit trail consistency across confirmation workflow

## Key Invariants

1. **No Partial Execution**: Entire lottery succeeds or entire lottery reverts. Never partial state.
2. **Per-Group Decisions**: Algorithm chosen independently per unit type, not globally.
3. **Fairness Preserved**: Greedy fallback maintains same fairness guarantees as GLPK.
4. **Audit Trail**: Every decision recorded with execution UUID for traceability.
5. **Timeout Handling**: Confirmation request timesout after 15 minutes, triggering full rollback.
6. **Consistency After Cancel**: Lottery reverts to "ready" state (is_published=true), can be re-executed.


