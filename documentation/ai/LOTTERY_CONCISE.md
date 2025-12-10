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
