# Lottery System - Complete Reference

## Executive Summary

The lottery is the **primary purpose** of the entire MTAV application - a fair, transparent, ONE-TIME housing unit assignment system for cooperative projects. It's an atomic event that permanently assigns ALL units to ALL families simultaneously using mathematical optimization to maximize overall satisfaction.

**Current Status**: Phase 1 & 2 complete (HTTP layer, orchestration, solvers, audit, persistence). Phase 3 in progress (GlpkSolver for production). Only pending: notifications (low priority).

## Core Business Rules

### The One-Time Principle

The lottery is a **binary, immutable event**:
- **State**: Either "pending" (not executed) OR "completed" (executed) - no partial execution
- **Execution**: Runs once per project, results are permanent
- **Immutability**: Once executed, assignments cannot be changed (except by superadmin invalidation in exceptional cases)
- **Atomicity**: ALL units assigned to ALL families simultaneously in a single transaction

### Preference-Based Assignment

**Before Execution**:
- Families express preferences by ranking ALL units of their assigned type
- Preferences can be modified/reordered at any time before execution
- **Locked when execution starts**: `is_published = false` blocks preference updates
- No deadlines enforced (soft feature for future phases)

**During Execution**:
- Algorithm optimizes global satisfaction based on preferences
- Maximizes overall family happiness while ensuring fairness
- Uses external optimization service or fallback strategy

**After Execution**:
- NO further preference changes allowed (enforced at service level)
- Assignments are final and visible to all families
- Historical preferences preserved for audit purposes

### Type Segregation

Each family is assigned to exactly ONE `UnitType`:
- Families can ONLY express preferences for units of their assigned type
- Different unit types have separate preference/assignment pools
- A family assigned to "Apartment" type cannot prefer "House" type units
- Algorithm runs independently per unit type

### Global Optimization

The system prioritizes **overall satisfaction** over individual wins:
- Not first-come-first-served
- Not random assignment
- Mathematical optimization considers ALL family preferences simultaneously
- Prevents favoritism, manipulation, or disputes through algorithmic fairness

## Data Architecture

### Entity Relationships

```
Project (1) ──→ UnitType (N) ──→ Unit (N)
   │
   └──→ Family (N) ──→ Member (N)
         │
         └──→ preferences (M) ←── Unit (N)
```

**Key Constraints**:
- Each `Family` belongs to exactly one `Project`
- Each `Family` is assigned to exactly one `UnitType`
- Each `Unit` belongs to one `Project` and one `UnitType`
- Each `Unit` can be assigned to at most one `Family` (or NULL if unassigned)
- Members are atomic - all members of a family must be in the same project

### Database Schema

#### `units` table
```php
id: bigint
project_id: bigint (FK to projects)
unit_type_id: bigint (FK to unit_types)
family_id: bigint|null (FK to families) ← NULL before lottery, set after execution
identifier: string (e.g., "Unit 1", "Apartment 3B")
timestamps, soft_deletes

UNIQUE(identifier, project_id)
```

#### `unit_preferences` pivot table
```php
family_id: bigint (FK to families)
unit_id: bigint (FK to units)
order: int (1 = highest priority, 2 = second, etc.)
timestamps

PRIMARY KEY(family_id, unit_id)
```

**Critical Insight**: `family_id` on `units` table is the source of truth for assignments:
- **NULL** = Lottery not executed OR unit not assigned
- **Set** = Lottery executed, this unit assigned to this family

#### `events` table (lottery tracking)
```php
id: bigint
project_id: bigint (FK to projects)
creator_id: bigint|null (FK to users)
title: string (e.g., "Lottery", "Sorteo")
description: text
type: EventType enum (LOTTERY for lottery events)
start_date: datetime|null (planned execution date)
end_date: datetime|null (not used for lottery)
is_published: boolean (TRUE = pending, FALSE = executing/executed)
timestamps, soft_deletes
```

**Lottery Events**:
- ONE per project (enforced by business logic)
- Created via `LotteryService::createLotteryEvent()`
- `type = EventType::LOTTERY`
- Cannot be created manually through standard event CRUD
- **Cannot be edited after execution starts** (`is_published = false`)
- `start_date` is informational (planned date), not enforced
- **Execution flag**: `is_published` flipped to `false` atomically to reserve lottery for execution

### Model Relationships

```php
// Family model
public function preferences(): BelongsToMany
{
    return $this->belongsToMany(Unit::class, 'unit_preferences')
        ->withPivot('order')
        ->orderByPivot('order');
}

public function unitType(): BelongsTo
{
    return $this->belongsTo(UnitType::class);
}

public function assignedUnit(): BelongsTo
{
    return $this->belongsTo(Unit::class, 'unit_id'); // If using alternative schema
}

// Unit model
public function family(): BelongsTo
{
    return $this->belongsTo(Family::class)->withTrashed();
}

public function unitType(): BelongsTo
{
    return $this->belongsTo(UnitType::class);
}

public function preferences(): BelongsToMany
{
    return $this->belongsToMany(Family::class, 'unit_preferences')
        ->withPivot('order')
        ->orderByPivot('order');
}

// Event model
public function isLottery(): bool
{
    return $this->type === EventType::LOTTERY;
}

public function scopeLottery(Builder $query): void
{
    $query->whereType(EventType::LOTTERY);
}

// Project model
public function lotteryEvent(): HasOne
{
    return $this->hasOne(Event::class)->lottery();
}
```

## Dynamic Preference Management

### Architecture Decision: Runtime Resolution

**Problem**: Housing data is fluid - families change types, units are added/removed, administrative corrections occur. Static preference storage becomes inconsistent.

**Solution**: Dynamic preference resolution at runtime via single source of truth.

### LotteryService::preferences()

**Location**: `app/Services/LotteryService.php`

```php
public function preferences(Family $family): Collection
{
    // 1. Sanitize existing preferences (remove units not matching family's type)
    $this->consistencyService->sanitizeBeforeFetch($family);

    // 2. Load relationships fresh
    $family->loadMissing(['preferences', 'unitType.units']);

    // 3. Get remaining units (not yet preferred) in ID order
    $remainingUnits = $family->unitType->units
        ->whereNotIn('id', $family->preferences->pluck('id'))
        ->sortBy('id');

    // 4. Return: explicit preferences first, then remaining candidates
    return $family->preferences->concat($remainingUnits);
}
```

**How It Works**:
1. **Sanitization**: Removes invalid preferences (unit type mismatches)
2. **Explicit Preferences**: Returns family's ordered preferences from pivot table
3. **Auto-Fill**: Adds ALL remaining units of family's type (ordered by ID)
4. **Complete List**: Always returns full list of ALL candidate units

**Benefits**:
- ✅ **Automatic Consistency**: Preference lists always valid regardless of data changes
- ✅ **Zero Maintenance**: No complex cascade operations when units/families/types change
- ✅ **Graceful Degradation**: New families automatically get all units as candidates
- ✅ **Single Source of Truth**: One method provides complete, validated preference data
- ✅ **New Unit Handling**: Newly added units automatically appear at end of list

**Example**:

```
Family 4 assigned to UnitType 1 (has Units 1, 2, 3, 4, 5)
Explicit preferences in DB: [Unit 3 (order: 1), Unit 1 (order: 2)]

preferences(family4) returns:
[Unit 3, Unit 1, Unit 2, Unit 4, Unit 5]
 ↑────── explicit ─↑  ↑──── auto-filled ──↑
```

### ConsistencyService

**Location**: `app/Services/Lottery/ConsistencyService.php`

#### sanitizeBeforeFetch()

Cleans invalid preferences before fetching:

```php
public function sanitizeBeforeFetch(Family $family): void
{
    // 1. Bypass scopes to find ALL preferences (even invalid ones)
    $scopelessUnits = DB::select(
        'SELECT unit_id FROM unit_preferences WHERE family_id = ?',
        [$family->id]
    );

    // 2. Get valid unit IDs for this family's type
    $validUnitIds = $family->unitType->units()->pluck('id');

    // 3. Find invalid entries (wrong type, deleted units, etc.)
    $invalidUnitIds = collect($scopelessUnits)->pluck('unit_id')->diff($validUnitIds);

    // 4. Delete invalid entries
    if ($invalidUnitIds->isNotEmpty()) {
        DB::unprepared("
            DELETE FROM unit_preferences
            WHERE family_id = {$family->id} AND unit_id IN ({$invalidUnitIds->join(',')})
        ");

        // 5. Refresh model relation cache
        $family->unsetRelation('preferences');

        // 6. Dispatch event for audit/notification
        InvalidPreferences::dispatch($family, $invalidUnitIds->values()->all());
    }
}
```

**When This Happens**:
- Admin changes family's unit type
- Units are soft-deleted
- Units are moved to different project
- Data import/migration issues

#### validateBeforeUpdate()

Ensures new preferences are complete and valid, and lottery hasn't started execution:

```php
public function validateBeforeUpdate(Family $family, array $preferences): void
{
    $lottery = $family->project->lottery;

    // Check if lottery execution has started
    if ($lottery && ! $lottery->is_published) {
        throw new LockedLotteryPreferencesException();
    }

    $inputUnitIds = collect($preferences)->pluck('id');
    $candidateIds = $family->unitType->units()->pluck('id');

    // Must not contain invalid units
    if ($inputUnitIds->diff($candidateIds)->isNotEmpty()) {
        throw new InvalidArgumentException('Preferences contain one or more invalid Units.');
    }

    // Must include ALL valid units
    if ($candidateIds->diff($inputUnitIds)->isNotEmpty()) {
        throw new InvalidArgumentException('Preferences is missing one or more valid Units.');
    }
}
```

**Business Rules**:
- Families must rank ALL units of their type - no partial preferences allowed
- Preferences cannot be updated after lottery execution starts (`is_published = false`)

### LotteryService::updatePreferences()

```php
public function updatePreferences(Family $family, array $preferences): void
{
    // 1. Validate completeness, validity, and lottery not locked
    $this->consistencyService->validateBeforeUpdate($family, $preferences);

    // 2. Replace ALL preferences atomically
    $family->preferences()->sync(
        collect($preferences)->map(fn ($preference, $idx) => [
            'unit_id' => $preference['id'],
            'order'   => $idx + 1,
        ])->keyBy('unit_id')
    );
}
```

**Exception Handling**: Throws `LockedLotteryPreferencesException` if lottery execution has started.

**Input Format**:
```php
[
    ['id' => 3],  // 1st preference
    ['id' => 1],  // 2nd preference
    ['id' => 5],  // 3rd preference
    ['id' => 2],  // 4th preference
    ['id' => 4],  // 5th preference
]
```

**Database Result**:
```
family_id | unit_id | order
----------|---------|------
    4     |    3    |   1
    4     |    1    |   2
    4     |    5    |   3
    4     |    2    |   4
    4     |    4    |   5
```

### LotteryService::updateLotteryEvent()

```php
public function updateLotteryEvent(Event $lottery, array $data): void
{
    // 1. Validate lottery not already executing/executed
    if (! $lottery->is_published) {
        throw new LockedLotteryException();
    }

    // 2. Update lottery details
    $lottery->update($data);
}
```

**Exception Handling**: Throws `LockedLotteryException` if lottery execution has started.

## Lottery Execution Architecture

### Two-Phase Orchestration

The lottery orchestration operates in **two distinct phases** at the architectural level:

#### Phase 1: By Unit Type

**What Happens**:
- Orchestrator iterates through each unit type in the project
- For each unit type: creates `LotterySpec` with families and units of that type
- Delegates to solver (RandomSolver, TestSolver, GlpkSolver, etc.)
- Solver returns `ExecutionResult` with picks and orphans
- Orchestrator collects and aggregates results

**Key Point**: The orchestrator does NOT care about balanced vs unbalanced scenarios. It simply:
1. Sends families and units to the solver
2. Receives picks (assignments) and orphans (leftovers)
3. Tracks orphan families and orphan units for potential Phase 2

**Solver Responsibility**: The solver implementation decides how to handle any scenario:
- Balanced sets (families = units) → typically no orphans
- Unbalanced sets (families ≠ units) → typically produces orphans
- Edge cases → solver's problem, not orchestrator's

**Important**: The orchestrator has NO concept of "complete distribution", "partial distribution", or "best-attempt distribution". It just knows: "I gave the solver some families and units, it gave me back some picks and some orphans."

---

#### Phase 2: Orphan Redistribution (Cross-Type)

**Trigger Condition**: Phase 1 produced orphans (either families OR units OR both)

**What Happens**:
1. Collect all orphan families from Phase 1 (families that didn't get units in their preferred type)
2. Collect all orphan units from Phase 1 (units that weren't assigned in their type)
3. Create single `LotterySpec` with mixed unit types
4. Generate preferences for cross-type assignments (families now considering units outside their original type)
5. Delegate to solver
6. Solver returns final picks and any remaining orphans

**Key Point**: The orchestrator still doesn't care WHY there are orphans. It just knows:
- "Phase 1 left some families/units unassigned"
- "Let me try to match them across types"
- "Whatever's left after this is truly unassignable"

**Result Categories**:
- No orphans after Phase 1 → Phase 2 skipped, done
- Orphans after Phase 1, none after Phase 2 → Perfect global assignment
- Orphans after Phase 2 → Global imbalance (more families than units, or vice versa)

---

### Solver Black Box

From the orchestrator's perspective, the solver is a **black box**:

```php
interface SolverInterface
{
    public function execute(LotterySpec $spec): ExecutionResult;
}
```

**Input**: `LotterySpec` (families + units + preferences)
**Output**: `ExecutionResult` (picks + orphans)

**The orchestrator does NOT assume**:
- 1 family = 1 unit (solver could assign 1 family to 3 units, if it wanted)
- 1 unit = 1 family (solver could assign 1 unit to 5 families, if it wanted)
- Balanced sets produce no orphans (solver could leave orphans on purpose)
- All families/units will be assigned (solver decides what's optimal)

**Why this matters**: Different solver implementations have different strategies:
- `RandomSolver`: Random pairing, expects balanced input
- `TestSolver`: Deterministic ID-based pairing, expects balanced input
- `GlpkSolver`: Optimization-based, handles unbalanced, produces orphans when needed
- Future `ManualSolver`: Human admin picks assignments, could do anything

The orchestrator **trusts the solver's decisions** and simply:
1. Receives the result
2. Tracks orphans
3. Decides whether Phase 2 is needed
4. Reports final outcome

---

### Conceptual Phases (Business Logic Layer)

While the **orchestrator** only knows about Phase 1 (by type) and Phase 2 (orphan redistribution), at a **business/solver level**, there are conceptual scenarios:

**Balanced Scenario** (families = units):
- Solver typically assigns all families to all units
- No orphans produced
- Phase 2 not needed

**Unbalanced: More Units** (units > families):
- Solver assigns all families
- Some units remain as orphans (least-preferred)
- Phase 2 may redistribute these orphan units to orphan families from other types

**Unbalanced: More Families** (families > units):
- Solver assigns all units
- Some families remain as orphans (least-satisfied)
- Phase 2 gives these families a second chance at units from other types

**But again**: The orchestrator doesn't think in these terms. It just sees:
- "Solver gave me picks and orphans"
- "Are there orphans? Yes → Phase 2. No → Done."

---

### Critical Guarantees

**Orchestrator Level**:
- Phase 1 always runs (at least one unit type must exist)
- Phase 2 runs if and only if orphans exist after Phase 1
- No assumptions about solver behavior beyond the interface contract

**Global Level** (across both phases):
- If `total_units >= total_families`, all families can potentially be assigned
- If `total_families > total_units`, some families will remain orphans
- Phase 2 maximizes assignments given available resources

---

### Data Flow Summary

```
Orchestrator receives: LotteryManifest (all project data)
  ↓
Phase 1: For each unit type
  → Create LotterySpec (families of type X, units of type X)
  → solver.execute(spec)
  → Receive ExecutionResult (picks, orphans)
  → Aggregate picks and orphans
  ↓
Check: Are there orphans?
  NO  → Done, report final results
  YES → Continue to Phase 2
  ↓
Phase 2: Orphan redistribution
  → Create LotterySpec (all orphan families, all orphan units, cross-type preferences)
  → solver.execute(spec)
  → Receive ExecutionResult (final picks, final orphans)
  → Aggregate with Phase 1 results
  ↓
Done: Report final results (all picks, remaining orphans if any)
```

---

## Architecture Overview

### Layer Separation

The lottery system is built in distinct layers with clear boundaries:

1. **HTTP Layer (Phase 1)** - ✅ Complete
   - Controllers, routes, validation, authorization
   - User interface components
   - Exception handling

2. **Orchestration Layer** - ✅ Complete
   - Event-driven execution trigger
   - Two-phase lottery orchestration (by unit type, then orphan redistribution)
   - Data transformation and execution delegation
   - Orphan tracking and cross-type redistribution logic

3. **Execution Layer (Solver Strategy)** - ✅ Complete
   - Strategy pattern for different solvers
   - Black-box solver interface (LotterySpec → ExecutionResult)
   - RandomSolver, TestSolver, GlpkSolver implementations
   - Result generation (picks + orphans)

4. **Persistence Layer** - ✅ Complete
   - Audit trail creation (INIT, GROUP_EXECUTION, PROJECT_EXECUTION, INVALIDATE, FAILURE)
   - Assignment application to database (bulk updates)
   - UUID-based execution grouping
   - Multiple execution run support

### Data Flow

```
ExecutionService (validates & transforms)
  → LotteryManifest (complete project data with UUID)
    → LotteryExecution (queued event)
      → ExecuteLotteryListener (resolves solver from config)
        → LotteryOrchestrator (two-phase coordination)
          │
          ├─ Phase 1: By Unit Type
          │   └─ For each unit type:
          │       → LotterySpec (families + units of that type)
          │       → solver.execute(spec)  ← Black box
          │       → ExecutionResult (picks + orphans)
          │       → Track orphans for Phase 2
          │       → Dispatch GroupLotteryExecuted event
          │
          ├─ Phase 2: Orphan Redistribution (if orphans exist)
          │   → LotterySpec (all orphan families + all orphan units, cross-type)
          │   → solver.execute(spec)  ← Black box
          │   → ExecutionResult (final picks + remaining orphans)
          │   → Dispatch GroupLotteryExecuted event
          │
          └─ Completion:
              → Dispatch ProjectLotteryExecuted event
              → AuditService creates audit records
              → ExecutionService applies results to database
              → Notifications queued (future)
```

## Implementation Status

### ✅ Phase 1: HTTP Layer & Validation (COMPLETE)

#### Backend Services

**LotteryService** (`app/Services/LotteryService.php`):
- ✅ `createLotteryEvent()` - Create lottery event for project
- ✅ `preferences()` - Dynamic preference resolution with auto-fill
- ✅ `updatePreferences()` - Replace family preferences atomically (with lock check)
- ✅ `updateLotteryEvent()` - Update lottery config (with lock check)
- ✅ `execute()` - Delegates to ExecutionService
- ✅ `executionReport()` - Receives execution reports from orchestrator

**ConsistencyService** (`app/Services/Lottery/ConsistencyService.php`):
- ✅ `sanitizeBeforeFetch()` - Remove invalid preferences before fetch
- ✅ `validateBeforeUpdate()` - Ensure completeness and check execution lock

**ExecutionService** (`app/Services/Lottery/ExecutionService.php`):
- ✅ `execute()` - Entry point: validates and dispatches execution event
- ✅ `reserveLotteryForExecution()` - Atomic flag update (`is_published` → false), generates execution UUID
- ✅ Creates `LotteryManifest` with UUID before validation
- ✅ Calls `AuditService::init()` to create INIT audit record with manifest data
- ✅ UUID generated at reservation time, making it available throughout sync and async flow
- ✅ `validateDataIntegrity()` - Check sufficient families, no existing assignments
- ✅ `validateCountsConsistency()` - Verify unit/family counts match per type (with override option)
- ✅ Creates `LotteryManifest` from validated project
- ✅ Dispatches `LotteryExecution` for async processing

**Exception Hierarchy**:
- ✅ `LotteryExecutionException` (base) - Generic execution errors with `getUserMessage()`
  - ✅ `CannotExecuteLotteryException` - Smart exception analyzing lottery state
  - ✅ `InsufficientFamiliesException` - Fewer than 2 families
- ✅ `UnitFamilyMismatchException` (standalone) - Unit/family count mismatches with override
- ✅ `LockedLotteryPreferencesException` (base Exception) - Preferences locked after execution starts
- ✅ `LockedLotteryException` (base Exception) - Lottery config locked after execution starts

**Policies & Authorization**:
- ✅ `EventPolicy::update()` - Rejects editing unpublished (executed) lottery events (hides edit action in cards)
- ✅ UI: Past events ignore `is_published` state for "Draft" badge display (completed events never show as draft)

#### Controllers & Requests

**LotteryController** (`app/Http/Controllers/LotteryController.php`):
- ✅ `index()` - Show lottery interface (role-based components)
- ✅ `update()` - Admin updates lottery configuration (with lock check + error handling)
- ✅ `preferences()` - Member updates family preferences (with lock check + error handling)
- ✅ `execute()` - Admin executes lottery (with exception hierarchy handling + mismatch override)

**Form Requests**:
- ✅ `UpdateLotteryRequest` - Validates admin lottery config updates
- ✅ `UpdateLotteryPreferencesRequest` - Validates member preference updates
- ✅ `ExecuteLotteryRequest` - Validates `overrideCountMismatch` boolean parameter

#### Frontend Components

**Location**: `resources/js/components/lottery/`

```
lottery/
├── admin/
│   └── LotteryManagement.vue         # Admin config & execution ✅
├── member/
│   └── PreferencesManager.vue        # Drag-and-drop preference ordering ✅
├── shared/
│   ├── Audit.vue                    # Real-time execution feedback ✅
│   ├── LotteryExecutedStatus.vue    # Post-execution status display ✅
│   ├── LotteryInProgress.vue        # Cute loading placeholder ✅
│   ├── LotteryHeader.vue            # Page header with description ✅
│   ├── LotteryContent.vue           # Role-based component router ✅
│   ├── LotteryFooter.vue            # Project plan integration ✅
├── composables/
│   └── useLotteryAudits.ts          # Audit data composable ✅
│   └── useLottery.ts                # Shared lottery state/logic ✅
├── types.d.ts                       # TypeScript definitions ✅
└── index.ts                         # Public exports ✅
```

**Audit.vue Component** (`resources/js/components/lottery/shared/Audit.vue`):
- ✅ Real-time polling every 1 second during execution
- ✅ 5-minute timeout with smart reset (resets when new audits arrive)
- ✅ Estimated progress bar using 40s/group assumption
- ✅ Progress calculation: `currentCompleted% + (elapsedSeconds/40 * segmentSize%)`
- ✅ Jumps to actual progress when new group_execution audit arrives
- ✅ Phase indicators: Initialization → Processing Groups (X/Y) → Finalization
- ✅ Groups count includes orphans/second-chance group (detected from manifest imbalances)
- ✅ Displays failure details with user message, error type, and exception
- ✅ Only shows when lottery is executing or completed (not invalidated)
- ✅ Full Spanish translations for all UI text

**useLotteryAudits Composable** (`resources/js/components/lottery/composables/useLotteryAudits.ts`):
- ✅ Extracts audit data from lottery audits array
- ✅ `totalUnitTypes` - Count from manifest.data keys in INIT audit
- ✅ `hasOrphansGroup` - Detects imbalances (families ≠ units per type) in manifest
- ✅ `groupsCount` - Total groups to process (unit types + orphans group if needed)
- ✅ `processedGroups` - Count of group_execution audits
- ✅ `progress` - Percentage based on groupsCount
- ✅ `isExecuting` - Uses new `is_executing` flag from lottery resource
- ✅ `initAuditTimestamp` - Timestamp from INIT audit for elapsed time calculation
- ✅ `hasFailure` / `failureAudit` - Failure detection and details

**LotteryInProgress.vue Component** (`resources/js/components/lottery/shared/LotteryInProgress.vue`):
- ✅ Simple placeholder shown when lottery is executing (no audits yet)
- ✅ Sparkles icon with animated spinner border
- ✅ Friendly message: "The housing units are being assigned to families. This won't take long!"
- ✅ Spanish translation included

**Resource Fields** (`app/Http/Resources/EventResource.php`):
- ✅ Added `is_executing` field - Clearer than `!is_published && !is_deleted`
- ✅ Added `is_completed` field - Clearer than `is_deleted`
- ✅ Frontend updated to use new fields throughout

**PreferencesManager.vue Features**:
- ✅ Drag-and-drop unit reordering
- ✅ Keyboard accessibility (arrow buttons)
- ✅ Touch-friendly for mobile
- ✅ Auto-save on every change
- ✅ Loading states and error handling
- ✅ Responsive: desktop grid + mobile list
- ✅ Visual features:
  - Numbered preference slots (1, 2, 3...)
  - Priority badges for top 3 choices
  - Subtle random rotations (card aesthetic)
  - Smooth 400ms transitions
  - Full drop zones (no dead areas)
  - Monospaced font for identifiers

**Routes**:
```php
Route::get('lottery', [LotteryController::class, 'index'])->name('lottery');
Route::patch('lottery/{lottery}', [LotteryController::class, 'update'])->name('lottery.update');
Route::patch('lottery/preferences', [LotteryController::class, 'preferences'])->name('lottery.preferences');
Route::post('lottery/{lottery}/execute', [LotteryController::class, 'execute'])->name('lottery.execute');
```

**Translation Keys**:
- ✅ `lottery.already_executed_or_executing` - Lottery already run or in progress
- ✅ `lottery.not_yet_scheduled` - Cannot execute before scheduled date
- ✅ `lottery.no_date_set` - Must set start date before execution
- ✅ `lottery.cannot_execute_generic` - Generic execution failure
- ✅ `lottery.execution_failed` - Unexpected error during execution
- ✅ `lottery.insufficient_families` - Need at least 2 families
- ✅ `lottery.unit_family_mismatch_intro` - Inconsistencies header
- ✅ `lottery.mismatch_excess_units` - More units than families
- ✅ `lottery.mismatch_insufficient_units` - Fewer units than families
- ✅ `lottery.preferences_locked` - Cannot update preferences after execution starts
- ✅ `lottery.lottery_locked` - Cannot update lottery config after execution starts

### 🚧 Phase 2: Orchestration & Execution (IN PROGRESS)

**Current State**: Event-driven architecture implemented with clean layer separation. Core execution components created. GLPK integration is **pending** - needs GlpkSolver implementation.

#### Completed Components

**ExecutionService** (`app/Services/Lottery/ExecutionService.php`):
- ✅ Complete validation layer (atomic reservation, data integrity, count consistency)
- ✅ Transform high-level models to `LotteryManifest`
- ✅ Dispatch `LotteryExecution` (queued)
- ✅ Clean boundary - no return value, just triggers process

**Data Objects** (`app/Services/Lottery/DataObjects/`):
- ✅ `LotteryManifest` - Complete project lottery inventory (all unit types) with execution UUID
  - Constructor: `__construct(string $uuid, Event $lottery)`
  - UUID is first-class citizen, generated at reservation and passed throughout flow
  - Stored in INIT audit for complete execution context
- ✅ `LotterySpec` - Single unit type specification for execution
- ✅ `ExecutionResult` - Encapsulates picks and orphans from execution phase
- ✅ `LotteryManifest` and `LotterySpec` implement `__serialize/__unserialize` for queue compatibility

**Event System**:
- ✅ `LotteryExecution` - Implements `ShouldQueue`, carries `LotteryManifest`
- ✅ `ExecuteLotteryListener` - Resolves solver from config, delegates to orchestrator
- ✅ Config-driven solver resolution (`config/lottery.php`)

**Solver Strategy** (`app/Services/Lottery/Contracts/SolverInterface.php`):
- ✅ Interface defined: `execute(LotterySpec $spec): ExecutionResult`
- ✅ Returns: `ExecutionResult` with `picks` and `orphans` arrays
- ✅ `RandomSolver` - Fully implemented (shuffles families and units, pairs via array_combine)
- ✅ `TestSolver` - Fully implemented (sorts both lists by ID, pairs via array_combine - deterministic)
- ⏳ `GlpkSolver` - **NOT YET IMPLEMENTED** - Will integrate with GLPK solver

**Configuration** (`config/lottery.php`):
- ✅ Default solver via `LOTTERY_SOLVER` env variable
- ✅ Solver definitions with class FQN and config array
- ✅ Example commented for external API solver (Acme)

**LotteryOrchestrator** (`app/Services/Lottery/LotteryOrchestrator.php`):
- ✅ Receives `LotteryManifest` and `SolverInterface`
- ✅ Unpacks manifest into `LotterySpec` objects (one per unit type)
- ✅ Two-phase execution strategy:
  - **Phase 1: By Unit Type** - Iterates each unit type, delegates to solver, collects picks and orphans
  - **Phase 2: Orphan Redistribution** - If orphans exist, creates cross-type spec and runs second lottery
- ✅ Tracks picks and orphans across both phases
- ✅ Treats solver as black box (no assumptions about balanced/unbalanced handling)
- ✅ Reports progress by dispatching events (GroupLotteryExecuted, ProjectLotteryExecuted)
- ✅ Logs summary results via `reportResults()`
- ✅ Error handling with graceful failure recovery (log, audit, invalidate)

#### Architecture Details

**Clean Boundaries**:
```php
// ExecutionService → Event (no coupling to execution logic)
$manifest = new LotteryManifest($lottery->project);
LotteryExecution::dispatch($manifest);

// Listener → Orchestrator (just passes data)
$solver = $this->makeSolver();
$orchestrator = LotteryOrchestrator::make($solver, $event->manifest);
$orchestrator->execute(); // No return value

// Orchestrator → Solver (per unit type)
foreach ($this->manifest->getData() as $unitTypeId => $typeData) {
    $spec = new LotterySpec($typeData['families'], $typeData['units']);
    $result = $this->solver->execute($spec);
    // Aggregate picks and orphans, report progress
}
```

**Config-Driven Execution**:
```php
// config/lottery.php
'solvers' => [
    'random' => [
        'solver' => RandomSolver::class,
        'config' => [],
    ],
    'test' => [
        'solver' => TestSolver::class,
        'config' => [],
    ],
    // 'glpk' => [  // ⏳ TO BE ADDED
    //     'solver' => GlpkSolver::class,
    //     'config' => [
    //         'glpsol_path' => env('GLPK_SOLVER_PATH', '/usr/bin/glpsol'),
    //         'temp_dir' => env('GLPK_TEMP_DIR', sys_get_temp_dir()),
    //         'timeout' => env('GLPK_TIMEOUT', 30),
    //     ],
    // ],
],

// Resolved via Laravel container
$solver = app()->makeWith($solverClass);
```

**Reporting System**:
- ✅ `ReportType` enum - Defines report types (PHASE_1_START, PHASE_1_COMPLETE, etc.)
- ✅ `LotteryService::executionReport()` - Receives execution progress reports
- ✅ `ExecutionResult` data object - Encapsulates picks and orphans for reporting
- ✅ Phase-by-phase reporting integrated into orchestrator
- ✅ Persistence and audit trail implemented in AuditService

#### Completed Components (Continued)

**AuditService** (`app/Services/Lottery/AuditService.php`):
- ✅ `init(LotteryManifest $manifest)` - Create INIT audit at execution start, soft-delete previous audits, stores manifest
- ✅ `audit()` - Create audit records for GROUP_EXECUTION and PROJECT_EXECUTION types
- ✅ `invalidate()` - Create INVALIDATE audit record when lottery is invalidated
- ✅ `exception()` - Create FAILURE audit record when execution fails
- ✅ Records include: execution_uuid, project_id, lottery_id, type (LotteryAuditType), audit data (admin, picks, orphans, manifest)
- ✅ Audit data stored as JSON in `lottery_audits` table with soft deletes

**Assignment Application** (`ExecutionService::applyResults()`):
- ✅ Bulk UPDATE `units.family_id` based on picks
- ✅ Soft-deletes lottery event after successful application
- ✅ Called by `ApplyLotteryResultsListener` after `ProjectLotteryExecuted` event
- ✅ Works with ANY solver implementation (RandomSolver, TestSolver, future GlpkSolver)

**Invalidation System** (`ExecutionService::invalidate()`):
- ✅ Restores soft-deleted lottery event
- ✅ Republishes lottery (is_published = true)
- ✅ Removes all family assignments from units
- ✅ Creates INVALIDATE audit record
- ✅ All operations in single DB transaction

**Event Listeners**:
- ✅ `LotteryExecutedListener` - Handles both `GroupLotteryExecuted` and `ProjectLotteryExecuted` events
- ✅ `ApplyLotteryResultsListener` - Applies final results to database after project completion

**Audit Model** (`app/Models/LotteryAudit.php`):
- ✅ Uses execution_uuid for grouping related audits
- ✅ LotteryAuditType enum: INIT, GROUP_EXECUTION, PROJECT_EXECUTION, INVALIDATE, FAILURE
- ✅ Stores complete audit trail as JSON
- ✅ Relationships to Project and Event (lottery)
- ✅ Soft deletes enabled - previous execution audits soft-deleted on new execution

#### Pending Components

1. **GlpkSolver Implementation** ⭐ **CRITICAL - See GLPK Integration section below**
   - Generate GLPK model files (.mod) for Phase 1 and Phase 2
   - Generate data files (.dat) from LotterySpec
   - Execute glpsol command-line tool
   - Parse solution files (.sol)
   - Return ExecutionResult with optimal assignments
   - Install GLPK in Docker container

2. **Notification System** (LOW PRIORITY)
   - Queue `LotteryResultNotification` to families
   - Email with assignment details
   - **Note**: Members are expected to be aware of lottery date and watch results in real-time

3. **Frontend Updates** ✅ **COMPLETE**
   - ✅ Real-time audit feedback with polling (Audit.vue component)
   - ✅ Estimated progress bar with 40s/group assumption
   - ✅ Phase indicators (Initialization, Processing Groups, Finalization)
   - ✅ Smart timeout reset (5 minutes, resets on new audits)
   - ✅ Execution state display (is_executing, is_completed flags)
   - ✅ Failure handling with detailed error messages
   - ✅ Groups count calculation (includes orphans/second-chance group)
   - ✅ Spanish translations for all UI text
   - ✅ Loading placeholder component (LotteryInProgress.vue)
   - ✅ Display assignment results
   - ✅ Success/error messaging

#### Testing Strategy

**Unit Tests**:
- ✅ `RandomSolverTest` - Validate pick/orphan counts, no duplicate IDs in picks
- ✅ `TestSolverTest` - Validate exact deterministic output for balanced/unbalanced data
- ✅ `LotteryOrchestratorTest` - Two-phase logic (by unit type + orphan redistribution) with various scenarios
- ⏳ Event/Listener integration - End-to-end event dispatch through orchestrator

**Feature Tests**:
- ✅ `PreferencesValidationTest` - Preference management and validation
- ✅ `ExecutionServiceTest` - Execution endpoint authorization, validation, and locking
- ⏳ End-to-end execution with RandomSolver
- ⏳ Result persistence and retrieval

**Integration Tests**:
- ⏳ Complete flow with universe.sql fixture
- ⏳ Multi-type projects (apartments + houses)
- ⏳ Orphan handling (mismatched counts)

## GLPK Integration (Production Solver)

### Overview

The **production solver** for the lottery system uses **GLPK (GNU Linear Programming Kit)** to solve the assignment problem with **max-min fairness** optimization. This is the algorithm that ensures the most equitable distribution of units to families based on their stated preferences.

**Status**: ✅ **ALGORITHM IMPLEMENTED** ✅ **ASYNC ERROR HANDLING COMPLETE** ⏳ **TESTING PENDING**

### Execution State Machine

The lottery uses **publish status** and **soft-deletion** to track execution state:

| State | `is_published` | `deleted_at` | Meaning |
|-------|----------------|--------------|---------|
| **Ready** | `true` | `NULL` | Lottery scheduled, awaiting execution |
| **Reserved (Executing)** | `false` | `NULL` | Execution in progress (async) |
| **Completed** | `false` | `timestamp` | Execution succeeded, results applied |

**State Transitions**:
1. **Reservation**: `ExecutionService::execute()` atomically unpublishes (`is_published = false`) - prevents concurrent executions
2. **Success**: `ExecutionService::applyResults()` soft-deletes the lottery - marks completion
3. **Failure (sync)**: `ExecutionService::cancelExecutionReservation()` republishes - allows retry
4. **Failure (async)**: ⚠️ **MISSING** - lottery stuck in Reserved state

### Existing Safety Net: 30-Second UI Timeout

**Frontend polling mechanism** (when lottery is Reserved):
- UI detects `is_published = false && deleted_at = NULL`
- Polls server every few seconds using Inertia partial reload
- Waits for `deleted_at` to be set (completion) for up to **30 seconds**
- After 30s timeout: Shows warning to admins + members that execution is taking longer than expected

**Purpose**: Catch-all to prevent users being stuck in limbo, but doesn't tell them:
- ✅ Whether execution actually failed or is just slow
- ✅ What the error was if it did fail
- ✅ How to fix it or retry

### Implementation Status Summary

| Component | Status | Notes |
|-----------|--------|-------|
| **GLPK Installation** | ✅ Complete | GLPK 5.0 in Docker, verified working |
| **GlpkSolver** | ✅ Complete | Two-phase optimization implemented |
| **ModelGenerator** | ✅ Complete | Phase 1 & 2 GMPL models |
| **DataGenerator** | ✅ Complete | LotterySpec → .dat conversion |
| **SolutionParser** | ✅ Complete | .sol file parsing |
| **GlpkException** | ✅ Complete | Basic exception with i18n |
| **Configuration** | ✅ Complete | Config in lottery.php |
| **UI Timeout Safety Net** | ✅ Complete | 30s polling with user notification |
| **Orchestrator Error Handling** | ✅ Complete | Try/catch with audit + invalidate |
| **Failure Audit Records** | ✅ Complete | AuditService::exception() implemented |
| **ExecutionType::FAILURE** | ✅ Complete | New enum case for failure audits |
| **Failure Notifications** | ⏳ Pending | Event + listener needed |
| **Unit Tests** | ⏳ Pending | GlpkSolver tests |
| **Integration Tests** | ⏳ Pending | universe.sql fixture tests |
| **Failure Scenario Tests** | ⏳ Pending | Test async failures in queue |

✅ **ASYNC ERROR HANDLING COMPLETE**: Orchestrator catches all exceptions gracefully (log, audit, invalidate) without re-throwing.

### Why GLPK?

**From INTEGRACION_GLPK.html analysis:**

The lottery algorithm must optimize for fairness, not just random assignment:
- **Max-Min Fairness**: Maximize the satisfaction of the least-satisfied family
- **Two-Phase Optimization**:
  1. Phase 1: Find the minimum satisfaction level `S` (no family gets worse than this)
  2. Phase 2: Among all solutions with `S`, maximize overall satisfaction
- **Proven Algorithm**: Used in original Windows application, same `.mod` files can be reused
- **Efficiency**: Solves 20-100 entity problems in 2-5 seconds

### Architecture Decision: Local GLPK (Not NEOS)

**Decision**: Install GLPK locally in the Docker container

**Rationale** (from INTEGRACION_GLPK.html):
- ✅ **No external dependencies**: No internet required, no external service to rely on
- ✅ **Simplicity**: Synchronous execution, no async/polling/timeout complexity
- ✅ **Privacy**: Data never leaves the server
- ✅ **Control**: No rate limits, no queues, no timeouts
- ✅ **Docker-friendly**: Install once in Dockerfile, runs anywhere
- ✅ **Same models work**: Existing `.mod` files from Windows app work without changes
- ✅ **Sufficient performance**: 2-5 seconds for typical problem sizes
- ✅ **Zero cost**: Free and open source

**Rejected Alternative**: NEOS Server (external API)
- ❌ Adds external dependency and internet requirement
- ❌ More complex: async, polling, network error handling
- ❌ Data leaves server (privacy concern)
- ❌ Has execution time limits
- Not justified for our problem size

### Implementation Plan

#### 1. Docker Installation ✅ COMPLETE

**Already added** to `.docker/build/image/Dockerfile`:

```dockerfile
RUN apk add --no-cache \
        bash \
        ffmpeg \
        freetype-dev \
        git \
        glpk glpk-dev \  # ← GLPK installed here
        libjpeg-turbo-dev \
        # ... other dependencies
```

**Verified working**:
```bash
mtav shell php -c "glpsol --version"
# Output: GLPSOL--GLPK LP/MIP Solver 5.0
```

#### 2. GlpkSolver Class ✅ COMPLETE

**Location**: `app/Services/Lottery/Solvers/GlpkSolver.php`

**Status**: ✅ **FULLY IMPLEMENTED** - Complete two-phase solver with all helper services

**Implemented Responsibilities**:
1. ✅ Generate GLPK model files (.mod) for Phase 1 and Phase 2
2. ✅ Generate data files (.dat) from LotterySpec
3. ✅ Execute glpsol via exec()
4. ✅ Parse solution files (.sol)
5. ✅ Clean up temporary files
6. ✅ Return ExecutionResult with picks and orphans

**Implementation**:
```php
namespace App\Services\Lottery\Solvers;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotterySpec;

class GlpkSolver implements SolverInterface
{
    public function __construct(protected array $config = [])
    {
        // config: glpsol_path, temp_dir, timeout
    }

    public function execute(LotterySpec $spec): ExecutionResult
    {
        // 1. Execute Phase 1: Maximize minimum satisfaction
        $minSatisfaction = $this->executePhase1($spec);

        // 2. Execute Phase 2: Optimize overall satisfaction given minSatisfaction
        $picks = $this->executePhase2($spec, $minSatisfaction);

        // 3. Calculate orphans (unmatched families/units)
        $orphans = $this->calculateOrphans($spec, $picks);

        return new ExecutionResult($picks, $orphans);
    }

    protected function executePhase1(LotterySpec $spec): int
    {
        // Generate MTAV.mod (Phase 1 model)
        // Generate data.dat (families, units, preferences)
        // Run: glpsol --model phase1.mod --data data.dat --output phase1.sol
        // Parse phase1.sol to extract objective value (minimum satisfaction)
        // Clean up temp files
        // Return: minimum satisfaction level
    }

    protected function executePhase2(LotterySpec $spec, int $minSatisfaction): array
    {
        // Generate MTAV_empate.mod (Phase 2 model)
        // Generate data.dat (families, units, preferences, S = minSatisfaction)
        // Run: glpsol --model phase2.mod --data data.dat --output phase2.sol
        // Parse phase2.sol to extract assignments (family_id => unit_id pairs)
        // Clean up temp files
        // Return: picks array
    }

    protected function runGlpk(string $modFile, string $datFile): string
    {
        $solFile = tempnam($this->config['temp_dir'], 'mtav_sol_') . '.sol';

        $command = sprintf(
            '%s --model %s --data %s --output %s 2>&1',
            $this->config['glpsol_path'],
            escapeshellarg($modFile),
            escapeshellarg($datFile),
            escapeshellarg($solFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new GlpkException("GLPK execution failed: " . implode("\n", $output));
        }

        return $solFile;
    }
}
```

#### 3. Supporting Services ✅ COMPLETE

**All helper services implemented** in `app/Services/Lottery/Glpk/`:

**ModelGenerator.php** ✅:
- ✅ `generatePhase1Model()`: Returns Phase 1 .mod content (max-min fairness)
- ✅ `generatePhase2Model()`: Returns Phase 2 .mod content (optimize ties)
- ✅ Models based on MTAV.mod and MTAV_empate.mod from Windows application
- ✅ Uses Spanish constraint names matching original (z_menorIgual, unicaAsignacionCoperativista, etc.)

**DataGenerator.php** ✅:
- ✅ `generateData(LotterySpec $spec)`: Converts LotterySpec to .dat format
- ✅ `generateDataWithS(LotterySpec $spec, int $S)`: Adds S parameter for Phase 2
- ✅ Format: GMPL data section with sets C (families), V (units), param p (preferences)
- ✅ Builds preference matrix with proper formatting (1-indexed ranks, 999 for missing)

**SolutionParser.php** ✅:
- ✅ `extractObjective(string $solFile)`: Parse Phase 1 objective value
- ✅ `extractAssignments(string $solFile)`: Parse Phase 2 variable values (x[c,v] = 1)
- ✅ Returns: array of family_id => unit_id assignments
- ✅ Error handling for missing/invalid solution files

#### 4. GLPK Model Files

**Phase 1 Model** (MTAV.mod - from INTEGRACION_GLPK.html):
```gmpl
# Maximize minimum satisfaction (max-min fairness)
set C;              # Cooperativistas (families)
set V;              # Viviendas (units)

param p{c in C, v in V};  # Prioridad (lower = better: 1 = first choice)

var x{c in C, v in V}, binary;  # Assignment decision
var z, integer;                  # Worst satisfaction (to minimize)

minimize resultado: z;

s.t. z_menorIgual{c in C}:
    z >= sum{v in V} p[c,v] * x[c,v];

s.t. unicaAsignacionCoperativista_mayorIgual{c in C}:
    sum{v in V} x[c,v] >= 1;
s.t. unicaAsignacionCoperativista_menorIgual{c in C}:
    sum{v in V} x[c,v] <= 1;

s.t. unicaAsignacionCasa_mayorIgual{v in V}:
    sum{c in C} x[c,v] >= 1;
s.t. unicaAsignacionCasa_menorIgual{v in V}:
    sum{c in C} x[c,v] <= 1;
```

**Phase 2 Model** (MTAV_empate.mod):
```gmpl
# Maximize overall satisfaction given minimum satisfaction constraint
# Note: We MINIMIZE the sum of preference ranks (where lower rank = better preference)
# This effectively MAXIMIZES satisfaction
set C;
set V;

param p{c in C, v in V};  # Lower = better (1 = first choice, 2 = second, etc.)
param S;  # Minimum satisfaction from Phase 1 (worst-case rank)

var x{c in C, v in V}, binary;

minimize resultado: sum{c in C, v in V} p[c,v] * x[c,v];
# Minimizing sum of ranks = Maximizing satisfaction

s.t. satisfaccionMinima{c in C}:
    sum{v in V} p[c,v] * x[c,v] <= S;

s.t. unicaAsignacionCoperativista_mayorIgual{c in C}:
    sum{v in V} x[c,v] >= 1;
s.t. unicaAsignacionCoperativista_menorIgual{c in C}:
    sum{v in V} x[c,v] <= 1;

s.t. unicaAsignacionCasa_mayorIgual{v in V}:
    sum{c in C} x[c,v] >= 1;
s.t. unicaAsignacionCasa_menorIgual{v in V}:
    sum{c in C} x[c,v] <= 1;
```

**Data File Format** (.dat):
```gmpl
data;

set C := c1 c2 c3;
set V := v10 v11 v12;

param p : v10 v11 v12 :=
c1        1   2   3      # Family 1 prefers: v10, v11, v12
c2        2   1   3      # Family 2 prefers: v11, v10, v12
c3        3   2   1      # Family 3 prefers: v12, v11, v10
;

# For Phase 2 only:
# param S := 2;

end;
```

#### 5. Configuration ✅ COMPLETE

**Already added** to `config/lottery.php`:

```php
'solvers' => [
    'random' => [...],
    'test' => [...],

    'glpk' => [  // ← GLPK solver configured
        'solver' => GlpkSolver::class,
        'config'   => [
            'glpsol_path' => env('GLPK_SOLVER_PATH', '/usr/bin/glpsol'),
            'temp_dir'    => env('GLPK_TEMP_DIR', sys_get_temp_dir()),
            'timeout'     => env('GLPK_TIMEOUT', 30),
        ],
    ],
],
```

**To activate** in `.env`:
```bash
LOTTERY_SOLVER=glpk
```

#### 6. Exception Handling ✅ COMPLETE (Basic) ⚠️ NEEDS ASYNC ERROR HANDLING

**Already created** `app/Services/Lottery/Exceptions/GlpkException.php`:

```php
class GlpkException extends LotteryExecutionException
{
    public function getUserMessage(): string
    {
        return __('lottery.glpk_execution_failed');
    }
}
```

**Translation keys added**:
- ✅ `lang/en/lottery.php`: "The optimization algorithm failed to execute. Please contact support."
- ✅ `lang/es_UY/lottery.php`: "El algoritmo de optimización falló al ejecutarse. Por favor contacta a soporte."

**⚠️ REQUIRED: Orchestrator-Level Error Handling**

**Current Problem**:
```
User Request → ExecutionService::execute() [SYNC]
                ↓ (atomically reserves lottery: is_published = false)
                ↓ (returns immediately)
                ↓ dispatch(LotteryExecutionTriggered) [ASYNC]
                ↓
            Queue Worker picks up event
                ↓
            ExecuteLotteryListener → LotteryOrchestrator::execute()
                ↓ (exception thrown here!)
                ↓
            Exception bubbles up to Laravel queue handler
                ↓
            Job goes to failed_jobs table
                ↓
            🔴 Lottery STUCK in Reserved state (is_published = false, deleted_at = NULL)
            🔴 UI polls for 30s, shows generic "taking too long" message
            🔴 No audit record of what failed
            🔴 Admin must manually use ExecutionService::invalidate()
```

**The Solution: Orchestrator Handles Its Own Failures**

Since GLPK is local and deterministic, **retries make no sense** (same input = same failure). Instead:

**✅ IMPLEMENTED: Orchestrator execution wrapped in try/catch** to handle failures gracefully:

```php
// app/Services/Lottery/LotteryOrchestrator.php
public function execute(): ExecutionResult
{
    try {
        // Execute all lottery phases
        foreach ($this->manifest->groups as $groupManifest) {
            $result = $this->solver->execute($groupManifest);
            $this->aggregatedResults->addGroupResult($groupManifest->groupId, $result);
        }

        // Store results in database
        $this->executionService->applyResults($this->manifest->lotteryId, $this->aggregatedResults);

        // Create audit records
        $this->auditService->recordExecution($this->manifest, $this->aggregatedResults);

        return $this->aggregatedResults;

    } catch (GlpkException $e) {
        // GLPK-specific failure (e.g., glpsol not found, invalid model, timeout)
        $this->handleExecutionFailure($e, 'glpk_error');

    } catch (LotteryExecutionException $e) {
        // Business logic failure (e.g., data validation, constraints)
        $this->handleExecutionFailure($e, 'execution_error');

    } catch (Throwable $e) {
        // Unexpected system error
        $this->handleExecutionFailure($e, 'system_error');
    }

    // Return empty result on failure (job completes successfully, lottery invalidated)
    return new ExecutionResult([], ['families' => [], 'units' => []]);
}

/**
 * ✅ IMPLEMENTED: Handle execution failure: log, audit, invalidate, report.
 *
 * Do NOT let exception bubble up - there's no point in Laravel retrying.
 */
protected function handleExecutionFailure(Throwable $exception, string $errorType): void
{
    // Extract user-friendly message if available
    $userMessage = method_exists($exception, 'getUserMessage')
        ? $exception->getUserMessage()
        : __('lottery.execution_failed');

    // 1. Log for immediate debugging
    Log::error('Lottery execution failed', [
        'lottery_id' => $this->manifest->lotteryId,
        'error_type' => $errorType,
        'exception' => get_class($exception),
        'message' => $exception->getMessage(),
        'user_message' => $userMessage,
    ]);

    // 2. Create persistent audit record (can be queried by admins)
    $this->auditService->exception($this->manifest, $exception);

    // 3. Cancel reservation (restore is_published = true) - accepts Event or int
    $this->executionService->cancelExecutionReservation($this->manifest->lotteryId);

    // 4. Report exception to error tracking (Sentry, logs, etc.)
    report($exception);

    // DO NOT re-throw - execution is handled, users can retry manually
    // Job will complete successfully, lottery is invalidated and can be re-run
}
```

**✅ IMPLEMENTED Components**:

1. **`AuditService::exception()`** - Create FAILURE audit record
   ```php
   public function exception(LotteryManifest $manifest, Throwable $exception): LotteryAudit
   {
       return LotteryAudit::create([
           'execution_uuid' => Str::uuid(),
           'project_id' => $manifest->projectId,
           'lottery_id' => $manifest->lotteryId,
           'type' => ExecutionType::FAILURE,
           'audit' => [
               'exception' => get_class($exception),
               'message' => $exception->getMessage(),
               'user_message' => method_exists($exception, 'getUserMessage')
                   ? $exception->getUserMessage()
                   : __('lottery.execution_failed'),
               'file' => $exception->getFile(),
               'line' => $exception->getLine(),
               'trace' => $exception->getTraceAsString(),
               'manifest_data' => $manifest->data, // Full input data for debugging
           ],
       ]);
   }
   ```

2. **`ExecutionType::FAILURE`** - ✅ Added to enum
   ```php
   enum ExecutionType: string
   {
       case GROUP = 'group';
       case PROJECT = 'project';
       case INVALIDATE = 'invalidate';
       case FAILURE = 'failure';  // ✅ IMPLEMENTED
   }
   ```

3. **`ExecutionService::cancelExecutionReservation()`** - ✅ Updated to accept Event|int
   ```php
   public function cancelExecutionReservation(Event|int $lottery): void
   {
       $lottery = $lottery instanceof Event ? $lottery : Event::findOrFail($lottery);
       $lottery->update(['is_published' => true]);
       $lottery->refresh();
   }
   ```

4. **✅ Failure handling complete**

   Orchestrator handles failures synchronously:
   - Catches all exceptions (GlpkException, LotteryExecutionException, Throwable)
   - Logs error details
   - Creates FAILURE audit record
   - Releases lottery (cancelExecutionReservation)
   - Returns empty result (job completes successfully)

   **No notification events needed**:
   - Admins monitor execution in real-time via UI
   - Lottery execution is critical, once-per-project event
   - Admin stays on page during execution
   - Real-time audit feedback provides immediate visibility
   - Can revisit if notification needs arise

**Why No Retries?**

- GLPK is **local and deterministic**: same input → same result
- If it fails once, it will fail again with same data
- Retries waste queue resources and delay user feedback
- Better: fail fast, audit, release lottery, show error in UI

**User Experience**:

| Scenario | Before | After |
|----------|--------|-------|
| **GLPK fails** | 2min timeout → generic warning | Immediate FAILURE audit + error shown in UI |
| **Admin action** | Manual invalidate via superadmin | Retry button (lottery auto-released) |
| **Debugging** | Check logs + failed_jobs table | Query FAILURE audit + detailed error |
| **Monitoring** | Silent failure | Real-time audit trail + UI feedback |

**Testing Requirements**:
- ✅ Test with sync queue (dev environment)
- ⚠️ Test with async queue (production-like environment)
- ⚠️ Test failure scenarios (GLPK not installed, timeout, invalid data)
- ⚠️ Verify lottery is released after failure
- ⚠️ Verify admins are notified
- ⚠️ Verify failed jobs can be retried

### Testing Strategy

**✅ COMPLETE** - See `documentation/ai/testing/LOTTERY_TESTS.md` for details

**Summary**:
- ✅ Unit tests (RandomSolver, TestSolver, GlpkSolver, Orchestrator)
- ✅ GLPK solver tests (optimal solutions, max-min fairness, failures)
- ✅ Integration tests with universe.sql fixture
- ✅ Async execution tests (audit persistence, retry logic)
- ✅ Error handling tests (GLPK failures, temp file cleanup)

**Key Test Files**:
- `tests/Unit/Lottery/` - Solver and orchestrator unit tests
- `tests/Feature/Lottery/GlpkSolverTest.php` - GLPK optimal solution verification
- `tests/Feature/Lottery/LotterySystemIntegrationTest.php` - End-to-end with universe.sql
- `tests/Feature/Lottery/AsyncExecutionTest.php` - Queue + audit persistence

**Run Tests**:
```bash
./mtav pest tests/Unit/Lottery tests/Feature/Lottery
```

### Performance Expectations

Based on INTEGRACION_GLPK.html analysis:

| Problem Size | Expected Time | Memory Usage |
|--------------|---------------|--------------|
| 10 families × 10 units | < 1 second | ~5 MB |
| 50 families × 50 units | 2-5 seconds | ~10 MB |
| 100 families × 100 units | 5-10 seconds | ~20 MB |

Our typical use case: 20-100 entities → **2-5 seconds** is acceptable for a one-time operation.

### Benefits of GLPK Integration

1. **True Fairness**: Max-min optimization ensures no family is unfairly disadvantaged
2. **Proven Algorithm**: Same approach as original Windows application
3. **Deterministic**: Same input always produces same optimal output
4. **Auditable**: Input preferences and output assignments are mathematically verifiable
5. **Cooperative Values**: Reflects solidarity and equity principles of cooperativism
6. **No Manual Intervention**: Eliminates human bias and disputes

### Rollout Strategy

1. ✅ **Phase 1**: Implement GlpkSolver with basic functionality
2. ✅ **Phase 2**: Test extensively with universe.sql fixture
3. ⏳ **Phase 3**: Real-time audit feedback UI (`Audit.vue` component) **← NEXT STEP**
4. ⏳ **Phase 4**: Deploy to staging with LOTTERY_SOLVER=glpk
5. ⏳ **Phase 5**: Run parallel executions (random vs glpk) to compare results
6. ⏳ **Phase 6**: Enable in production once validated
7. **Fallback**: Keep RandomSolver available via env variable if issues arise

### Current Implementation Status

**✅ Algorithm & Testing Complete**:
- GlpkSolver with two-phase optimization
- ModelGenerator (Phase 1 & 2 GMPL models)
- DataGenerator (converts LotterySpec to .dat format)
- SolutionParser (extracts results from .sol files)
- GlpkException with user-facing error messages
- Configuration in lottery.php
- Translation keys (English + Spanish)
- GLPK 5.0 installed and verified in Docker container
- Comprehensive test suite (see LOTTERY_TESTS.md)
- Orchestrator error handling (log, audit, release)
- Async execution tests with audit persistence

**⏳ Next: Real-Time UI Feedback**:
- Create `Audit.vue` component to display execution progress
- Improve `useLotteryAudits.ts` composable
- Show phase transitions (init → group → project → complete)
- Display failure details with user-friendly messages
- Timeout reset on new audit arrivals

**Environment-Specific Behavior**:
- **Development** (`QUEUE_CONNECTION=sync`): Failures return immediately to controller
- **Production** (`QUEUE_CONNECTION=redis|database`): Failures handled in orchestrator, events dispatched

**✅ Completed Steps**:
1. ✅ Document async execution architecture
2. ✅ Implement orchestrator try/catch with `handleExecutionFailure()`
3. ✅ Add `AuditService::exception()` + `ExecutionType::FAILURE`
4. ✅ Update `ExecutionService::cancelExecutionReservation()` to accept Event|int

**⏳ Next Steps**:
5. ⏳ Create `LotteryExecutionFailed` event + notification listener
6. ⏳ Create unit tests for GlpkSolver
7. ⏳ Create integration tests with universe.sql fixture
8. ⏳ Test with small known-optimal problems (3-5 families/units)
9. ⏳ Verify max-min fairness is achieved
10. ⏳ Performance testing with typical problem sizes (20-100 entities)
11. ⏳ **Test failure scenarios** (GLPK not installed, timeouts, invalid data) in async queue
12. ⏳ Add admin UI to view/retry failed executions

**🚀 Ready for**: Unit testing and integration testing

### Documentation References

- **INTEGRACION_GLPK.html**: Complete technical analysis and decision rationale
- **GLPK Documentation**: https://www.gnu.org/software/glpk/
- **GMPL (MathProg) Language**: https://en.wikibooks.org/wiki/GLPK/GMPL_(MathProg)
- **Assignment Problem**: https://en.wikipedia.org/wiki/Assignment_problem
- **Max-Min Fairness**: https://en.wikipedia.org/wiki/Max-min_fairness

---

### ✅ Phase 3: Audit Trail & Persistence (COMPLETE)

#### Audit System Architecture

**Status**: ✅ **FULLY IMPLEMENTED** - Working audit trail with support for multiple runs, invalidations, and debugging.

**Purpose**: Complete audit trail for lottery executions with support for multiple runs, invalidations, and debugging.

**Database Schema** (`lottery_audits` table):
```php
uuid: uuid (PRIMARY KEY, auto-generated)
project_id: bigint (FK to projects, cascades on delete)
lottery_id: bigint (FK to events, cascades on delete)
type: enum('execution', 'result')
audit: json (execution data and metadata)
created_at: timestamp
updated_at: timestamp
```

**Audit Grouping Hierarchy**:
```
Project (1) ──→ Lottery Events (N) ──→ Execution Runs (N) ──→ Audit Records (N)
    │               │                       │                        │
    │               │                       │                        └─ Type: execution | result
    │               │                       │
    │               │                       └─ Identified by: UUID (shared across related audits)
    │               │
    │               └─ One active, multiple soft-deleted (if invalidated)
    │
    └─ Housing project
```

**UUID Grouping**: All audit records from a single execution share the same UUID:
- UUID generated at `reserveLotteryForExecution()` time (before validation)
- Passed to `LotteryManifest` constructor as first-class property
- Available throughout sync flow (ExecutionService) and async flow (Orchestrator)
- Allows grouping all related audits (INIT, GROUP_EXECUTION, PROJECT_EXECUTION, FAILURE)
- Essential for debugging failed runs
- Enables tracing complete execution flow from start to finish
- Supports multiple runs per lottery (previous audits soft-deleted on new execution)

**Audit Types** (LotteryAuditType enum):

1. **Type: `INIT`** (Execution Start)
   - Created by: `AuditService::init()` immediately after lottery reservation
   - Purpose: Mark execution start and store complete manifest for debugging
   - Created before validation, so exists even if execution fails early
   - Contains: Admin info, complete LotteryManifest (all project data, preferences, UUIDs)
   - Side effect: Soft-deletes all previous audits for this lottery
   - Benefit: Complete execution context available from the very start

2. **Type: `GROUP_EXECUTION`** (Granular Trail)
   - Created by: `GroupLotteryExecuted` event (one per unit-type group)
   - Purpose: Track individual group executions for debugging/auditing
   - Multiple per project execution (one per unit type)
   - Contains: Admin info, picks, orphans from this group

3. **Type: `PROJECT_EXECUTION`** (Completion Marker)
   - Created by: `ProjectLotteryExecuted` event (once per complete execution)
   - Purpose: Mark project lottery as complete
   - Single per successful execution
   - Contains: Admin info, final aggregated picks and orphans for entire project
   - User visibility: Determines lottery completion status

4. **Type: `INVALIDATE`** (Execution Reversal)
   - Created by: `AuditService::invalidate()` when superadmin invalidates lottery
   - Purpose: Audit trail of lottery invalidation
   - Contains: Admin info

5. **Type: `FAILURE`** (Execution Error)
   - Created by: `AuditService::exception()` when execution fails
   - Purpose: Record execution failures with detailed error information
   - Contains: Error type, exception class, message, user-facing message

**Status Tracking Strategy**:
- No dedicated status column in lottery_audits table
- Status derived from presence/absence of `type=result` audit
- Project/Lottery models check: `hasAudit('result')` → lottery complete
- Simple, reliable: either completed or not
- Audit JSON can include internal status for debugging (created/inprogress/complete)

**Multiple Runs Scenario**:
```
Project #1, Lottery #50:
  Run 1 (UUID: abc-123) - Failed during validation
    ├─ INIT audit (contains complete manifest)
    └─ FAILURE audit (validation error)
    Note: Run 1 audits soft-deleted when Run 2 starts

  Run 2 (UUID: def-456) - Succeeded
    ├─ INIT audit (soft-deleted Run 1 audits)
    ├─ GROUP_EXECUTION audit (UnitType 1)
    ├─ GROUP_EXECUTION audit (UnitType 2)
    ├─ GROUP_EXECUTION audit (Second-chance)
    └─ PROJECT_EXECUTION audit (final completion) ← marks lottery as complete
```

**Invalidation Scenario**:
```
Project #1:
  Lottery #50 (soft-deleted after community request)
    └─ Run 1 (UUID: def-456) - Completed
        ├─ execution audits...
        └─ result audit

  Lottery #51 (created after invalidation)
    └─ Run 1 (UUID: xyz-789) - Completed
        ├─ execution audits...
        └─ result audit ← new official result
```

**Model** (`app/Models/LotteryAudit.php`) - ✅ **IMPLEMENTED**:
```php
use SoftDeletes;

protected $casts = [
    'type' => LotteryAuditType::class,
    'audit' => 'array',
];

public function project(): BelongsTo
{
    return $this->belongsTo(Project::class);
}

public function lottery(): BelongsTo
{
    return $this->belongsTo(Event::class, 'lottery_id');
}
```

**Note**: Uses standard auto-incrementing ID, not UUID as primary key. The `execution_uuid` column groups related audits together.

**Events** (`app/Events/Lottery/`) - ✅ **IMPLEMENTED**:

- `GroupLotteryExecuted` - Dispatched after each group execution
- `ProjectLotteryExecuted` - Dispatched after complete project execution
- Both extend abstract `LotteryExecuted` base class
- Each provides `executionType()` method returning `LotteryAuditType::GROUP_EXECUTION` or `LotteryAuditType::PROJECT_EXECUTION`
- Events carry: uuid (from manifest), project_id, lottery_id, manifest, solver, report (ExecutionResult)

**Listeners** - ✅ **IMPLEMENTED**:

1. `LotteryExecutedListener` - Handles both `GroupLotteryExecuted` and `ProjectLotteryExecuted`
   - ✅ Creates audit records via `AuditService::audit()`
   - ✅ Records execution type (GROUP or PROJECT)
   - ✅ Stores complete audit trail with picks and orphans

2. `ApplyLotteryResultsListener` - Handles `ProjectLotteryExecuted`
   - ✅ Applies results via `ExecutionService::applyResults()`
   - ✅ Updates `units.family_id` for all picks
   - ✅ Soft-deletes lottery event after completion

**Integration Points**:
- `LotteryOrchestrator::execute()`: Generates UUID, dispatches events
- `LotteryOrchestrator::executeLottery()`: Dispatches `GroupLotteryExecuted` after each group
- `LotteryOrchestrator::reportResults()`: Dispatches `ProjectLotteryExecuted` after completion
- Events queued for async processing (ShouldQueue)

**Benefits**:
- ✅ Complete audit trail for compliance
- ✅ Debug failed executions with grouped audits
- ✅ Support multiple lottery runs per project
- ✅ Handle lottery invalidations gracefully
- ✅ Simple status checking (has result audit = complete)
- ✅ Preserve historical data for legal requirements
- ✅ Easy to query: "Show me all runs for this lottery"

### 📅 Phase 4: Future Enhancements

**Notifications** (LOW PRIORITY):
- Email notifications with assignment details to families
- **Note**: Members are expected to be aware of lottery date and watch results in real-time

**Additional Enhancements**:
- Advanced validation (deadline enforcement)
- Real-time progress updates during execution
- Satisfaction scoring display
- Enhanced audit trail with digital signatures
- Result verification system
- Regulatory compliance features
- Performance monitoring and analytics

### 🎨 Phase 5: Interactive Project Plan (FUTURE)

**Goal**: Transform abstract preference ordering into spatial decision-making.

**Admin Features**:
- Visual canvas for drawing/arranging unit layouts
- Drag-and-drop unit placement matching building layout
- Save spatial positions to database (plan_x, plan_y, plan_rotation, etc.)
- WYSIWYG representation of physical project

**Member Experience**:
- **Bi-directional highlighting**: Click unit in grid → highlights in plan, click in plan → highlights in grid
- **Drag from plan to picker**: Grab unit from visual layout → drop into preference slot
- **Visual context**: See exactly where "Unit 8" is in building, understand floor/orientation/neighbors
- **Better decisions**: Choose based on location, not just identifier

**Technical Requirements**:
- SVG or Canvas-based rendering
- Shared state between ProjectPlan and PreferencesManager
- Unit coordinates stored in database
- Responsive scaling for different screen sizes
- Touch-friendly for mobile/tablet

**Benefits**:
- Transforms lottery from abstract to concrete
- Users understand WHAT they're choosing
- Reduces post-lottery disputes
- More informed decisions
- Better user satisfaction

## Authorization & Security

### User Roles

**Superadmins** (identified by email in config):
- Can invalidate lottery results (exceptional cases only)
- Bypass all policies
- Emergency intervention capabilities

**Admins** (is_admin = true):
- Configure lottery settings (date, description)
- Execute lottery for their projects
- View all family preferences
- Cannot modify other families' preferences

**Members** (is_admin = false):
- View lottery information
- Update their own family's preferences
- View assignment results after execution
- Cannot execute lottery
- Cannot view other families' preferences

### Policy Rules

```php
// EventPolicy
public function update(User $user, Event $event): bool
{
    // Lottery events cannot be edited like regular events
    return $user->isAdmin() && $event->type !== EventType::LOTTERY;
}

// LotteryPolicy (to be created)
public function execute(User $user, Event $lottery): bool
{
    return $user->isAdmin() && $user->canManage($lottery->project);
}

public function updatePreferences(User $user, Family $family): bool
{
    return $user->isMember() && $user->family_id === $family->id;
}

public function invalidate(User $user, Event $lottery): bool
{
    return $user->isSuperadmin();
}
```

### Security Constraints

1. **One-Time Execution**: Database-level validation prevents double execution
2. **Transaction Safety**: All assignments in single transaction (all-or-nothing)
3. **Audit Trail**: Complete logging for legal compliance
4. **Authorization Checks**: Strict role-based access control
5. **Data Validation**: Comprehensive input validation before execution
6. **Immutability**: Assignments cannot be changed after execution (except superadmin invalidation)

## Database Queries & Performance

### Fetching Preferences (Optimized)

```php
// Bad: N+1 queries
foreach ($families as $family) {
    $prefs = $family->preferences; // Query per family
}

// Good: Eager loading
$families = Family::with(['preferences', 'unitType.units'])
    ->whereProjectId($project->id)
    ->get();

foreach ($families as $family) {
    $prefs = $this->lotteryService->preferences($family); // No queries
}
```

### Applying Assignments (Bulk Update)

```php
// Bad: N queries
foreach ($assignments as $familyId => $unitId) {
    Unit::find($unitId)->update(['family_id' => $familyId]);
}

// Good: Bulk update with case statement
$cases = [];
$ids = [];
foreach ($assignments as $familyId => $unitId) {
    $cases[] = "WHEN {$unitId} THEN {$familyId}";
    $ids[] = $unitId;
}

DB::update("
    UPDATE units
    SET family_id = CASE id
        " . implode(' ', $cases) . "
    END
    WHERE id IN (" . implode(',', $ids) . ")
");
```

### Checking Execution Status

```php
// Efficient: single query
$lotteryExecuted = $project->lotteryEvent()->whereNotNull('executed_at')->exists();

// Or via eager loading
$project->load('lotteryEvent');
$executed = $project->lotteryEvent?->executed_at !== null;
```

## Testing Approach

### Universe Fixture

**Location**: `tests/Fixtures/universe.sql`

**Relevant Data**:
- Projects #1-5
- Families #1-26
- UnitTypes per project
- Units matching family count
- Pre-seeded preferences for some families

**Usage**: 20-30x faster than factories, provides predictable data.

### Test Categories

1. **Preference Management** (`tests/Feature/Lottery/PreferenceTest.php`):
   - ✅ Fetch preferences with auto-fill
   - ✅ Update preferences atomically
   - ✅ Sanitization removes invalid preferences
   - ✅ Validation requires complete preferences
   - ✅ Authorization (members can only update own family)
   - ⏳ Preference locking after execution starts

2. **Lottery Configuration** (`tests/Feature/Lottery/ConfigTest.php` - to be created):
   - ⏳ Update lottery details (admin only)
   - ⏳ Config locking after execution starts
   - ⏳ Authorization checks

3. **Execution Validation** (`tests/Feature/Lottery/ExecutionValidationTest.php` - to be created):
   - ⏳ Atomic reservation prevents race conditions
   - ⏳ Validate sufficient families (≥2)
   - ⏳ Validate no existing assignments
   - ⏳ Validate unit/family count consistency
   - ⏳ Override mechanism for count mismatches
   - ⏳ Exception hierarchy handling

4. **Execution Business Logic** (`tests/Feature/Lottery/ExecutionTest.php` - Phase 2):
   - Execute lottery with valid data
   - Apply assignments to units
   - Create audit trail
   - Queue notifications
   - Test transaction rollback on errors

5. **UI Health Checks** (`tests/Feature/UIHealthTest.php`):
   - ✅ Lottery index page loads
   - ✅ Role-based component rendering
   - ⏳ Execution button shows for admins only
   - ⏳ Assignment results show after execution

## Configuration

### Solver Selection

```php
// config/lottery.php
return [
    'default' => env('LOTTERY_SOLVER', 'random'),

    'solvers' => [
        'random' => [
            'solver' => \App\Services\Lottery\Solvers\RandomSolver::class,
        ],

        'test' => [
            'solver' => \App\Services\Lottery\Solvers\TestSolver::class,
        ],

        // Example: External API solver
        // 'acme' => [
        //     'solver' => \App\Services\Lottery\Solvers\AcmeSolver::class,
        //     'config' => [
        //         'api_key' => env('ACME_API_KEY'),
        //         'api_secret' => env('ACME_API_SECRET'),
        //         'api_endpoint' => env('ACME_API_ENDPOINT', 'https://api.acme.com/lottery'),
        //         'timeout' => env('ACME_API_TIMEOUT', 30),
        //     ],
        // ],
    ],
];
```

**Usage**: Set `LOTTERY_SOLVER=random` (default) or `LOTTERY_SOLVER=test` in `.env`

### Solver Resolution

Solvers are resolved in `ExecuteLotteryListener` using Laravel's service container:

```php
protected function resolveSolver(): SolverInterface
{
    $default = config('lottery.default');
    $solverConfig = config("lottery.solvers.{$default}");

    $solverClass = $solverConfig['solver'];
    $config = $solverConfig['config'] ?? [];

    return app()->makeWith($solverClass, ['config' => $config]);
}
```

## API Contract (External Optimization Service)

### Request Format

```json
{
  "project_id": 1,
  "unit_types": [
    {
      "unit_type_id": 1,
      "families": [
        {
          "family_id": 1,
          "preferences": [3, 1, 5, 2, 4]  // unit_ids in order
        },
        {
          "family_id": 2,
          "preferences": [1, 3, 2, 5, 4]
        }
      ]
    }
  ]
}
```

### Response Format

```json
{
  "success": true,
  "assignments": [
    {"family_id": 1, "unit_id": 3},
    {"family_id": 2, "unit_id": 1}
  ],
  "metadata": {
    "algorithm": "hungarian",
    "satisfaction_score": 0.87,
    "execution_time_ms": 245
  }
}
```

### Error Handling

```json
{
  "success": false,
  "error": "insufficient_units",
  "message": "Not enough units to assign all families"
}
```

## Common Patterns & Gotchas

### ✅ DO: Use Dynamic Preferences

```php
// Always use LotteryService for complete preferences
$preferences = $this->lotteryService->preferences($family);
```

### ❌ DON'T: Query Preferences Directly

```php
// This gives incomplete list (missing auto-filled units)
$preferences = $family->preferences;
```

### ✅ DO: Validate Before Update

```php
// Always validate completeness and lock status
$this->consistencyService->validateBeforeUpdate($family, $preferences);
$family->preferences()->sync($preferences);
```

### ❌ DON'T: Allow Partial Preferences

```php
// This will fail validation (missing units)
$family->preferences()->sync([1, 3, 5]); // Only 3 out of 10 units
```

### ✅ DO: Use Atomic Lock for Execution

```php
// Prevents race conditions with concurrent execution attempts
$reserved = Event::query()
    ->where('is_published', true)
    ->where('id', $lottery->id)
    ->update(['is_published' => false]);

if (! $reserved) {
    throw new CannotExecuteLotteryException($lottery);
}
```

### ❌ DON'T: Check Then Update Separately

```php
// Race condition: another request could execute between check and update
if ($lottery->is_published) {
    $lottery->update(['is_published' => false]); // Too late!
}
```

### ✅ DO: Use Exception Hierarchy

```php
// Controller catches base exception for all execution errors
try {
    $this->lotteryService->execute($lottery, $override);
} catch (UnitFamilyMismatchException $e) {
    return back()->with('mismatchError', $e->getUserMessage());
} catch (LotteryExecutionException $e) {
    return back()->with('error', $e->getUserMessage());
}
```

### ❌ DON'T: Catch Every Exception Type

```php
// Maintenance nightmare when adding new exceptions
catch (CannotExecuteLotteryException $e) { ... }
catch (InsufficientFamiliesException $e) { ... }
catch (AnotherException $e) { ... }
```

### ✅ DO: Check Lock Before Updates

```php
// Both lottery config and preferences check is_published
if (! $lottery->is_published) {
    throw new LockedLotteryException();
}
```

### ❌ DON'T: Allow Updates After Execution Starts

```php
// This breaks data integrity during execution
$lottery->update(['start_date' => now()->addDay()]);
```

## Internationalization

### Translation Keys

```json
// lang/en.json
{
  "Lottery": "Lottery",
  "Preferences": "Preferences",
  "Execute Lottery": "Execute Lottery",
  "Lottery Results": "Lottery Results",
  "Your Assignment": "Your Assignment",
  "Unit {number}": "Unit {number}",
  "Priority {number}": "Priority {number}"
}

// lang/es_UY.json (Spanish - Uruguay)
{
  "Lottery": "Sorteo",
  "Preferences": "Preferencias",
  "Execute Lottery": "Ejecutar Sorteo",
  "Lottery Results": "Resultados del Sorteo",
  "Your Assignment": "Tu Asignación",
  // Audit Component Translations (added Dec 2025)
  "Lottery in Progress": "Sorteo en Progreso",
  "Lottery Complete": "Sorteo Completo",
  "Lottery Execution Failed": "La Ejecución del Sorteo Falló",
  "Taking Longer Than Expected...": "Tomando Más Tiempo del Esperado...",
  "Initialization": "Inicialización",
  "Processing Groups": "Procesando Grupos",
  "Finalization": "Finalización",
  "Groups Processed": "Grupos Procesados",
  "All families have been assigned to their units.": "Todas las familias han sido asignadas a sus unidades.",
  "An error occurred during execution": "Ocurrió un error durante la ejecución",
  "Error Type": "Tipo de Error",
  "The housing units are being assigned to families. This won't take long!": "Las unidades habitacionales están siendo asignadas a las familias. ¡No tomará mucho tiempo!",
  "Unit {number}": "Unidad {number}",
  "Priority {number}": "Prioridad {number}"
}
```

### Backend Translation

```php
// In LotteryService::createLotteryEvent()
'title' => __('Lottery'),
'description' => __('general.lottery_default_description'),
```

### Frontend Translation

```vue
<script setup lang="ts">
const { _ } = useTranslations();
</script>

<template>
  <h1>{{ _('Lottery') }}</h1>
  <button>{{ _('Execute Lottery') }}</button>
</template>
```

**Audit Component Translations** (all added to `lang/es_UY.json`):
- "Lottery in Progress" → "Sorteo en Progreso"
- "Lottery Complete" → "Sorteo Completo"
- "Lottery Execution Failed" → "La Ejecución del Sorteo Falló"
- "Taking Longer Than Expected..." → "Tomando Más Tiempo del Esperado..."
- "Initialization" → "Inicialización"
- "Processing Groups" → "Procesando Grupos"
- "Finalization" → "Finalización"
- "Groups Processed" → "Grupos Procesados"
- "All families have been assigned to their units." → "Todas las familias han sido asignadas a sus unidades."
- "An error occurred during execution" → "Ocurrió un error durante la ejecución"
- "Error Type" → "Tipo de Error"
- "The housing units are being assigned to families. This won't take long!" → "Las unidades habitacionales están siendo asignadas a las familias. ¡No tomará mucho tiempo!"

## Related Documentation

- **`documentation/ai/KNOWLEDGE_BASE.md`** - Business domain overview
- **`documentation/ai/testing/LOTTERY_TESTS.md`** - Complete testing reference
- **`documentation/ai/testing/PHILOSOPHY.md`** - General testing patterns
- **`tests/_fixtures/UNIVERSE.md`** - Test data structure
- **`documentation/ai/ProjectPlans.md`** - Phase 3 spatial visualization
- **`resources/js/components/lottery/README.md`** - Frontend technical details
- **`.github/copilot-instructions.md`** - Code style and conventions

---

## Next Steps for Next Session

### Completed Since Last Update

1. ✅ **Real-Time Audit UI** - Implemented comprehensive audit feedback system
   - Audit.vue component with real-time polling and estimated progress
   - useLotteryAudits composable for audit data extraction
   - Smart timeout (5 min) that resets on new audit arrivals
   - Groups count calculation including orphans/second-chance detection
   - Phase indicators and progress bar with 40s/group estimation
   - Failure handling with detailed error messages

2. ✅ **Spanish Translations** - All lottery UI text translated
   - Added 12 new translation keys to lang/es_UY.json
   - Full Spanish support for audit component

3. ✅ **Resource Field Improvements** - Clearer lottery state fields
   - Added is_executing and is_completed to EventResource
   - Updated all frontend checks to use new fields
   - Removed confusing is_published/is_deleted logic from UI

### Investigation Items

1. **Deferred Event Handling** (High Priority):
   - Investigate if deferred events triggered from already deferred code does not work
   - Sync fixed it, need to debug why deferred version failed
   - Test: Are nested deferred events being properly queued and executed?
   - Check: Event listener execution order when dispatched from async context
   - Verify: Queue worker handling of cascaded events

2. **executeMoreUnits Balance Issue** (High Priority):
   - Investigate if the executeMoreUnits logic is correct
   - Observation: Families seem to be sending all preferences even if we remove the worst units
   - Example: 7 families with 8 preferences for 7 selected units (should be 7 preferences)
   - Weird: This seems to work even though GLPK is supposed to work with balanced sets only
   - Questions:
     - Is the preference trimming logic working correctly?
     - Why does GLPK accept unbalanced inputs without failing?
     - Are we inadvertently creating valid models despite the imbalance?
   - To check:
     - Debug preference count per family in executeMoreUnits
     - Verify .dat file generation for unbalanced scenarios
     - Confirm GLPK constraint handling (is it silently handling extras?)
     - Review LotteryOrchestrator's family/unit filtering logic

3. **Default Preference Bias** (Mid Priority):
   - **Problem**: Current auto-fill logic sorts non-explicit preferences by unit ID
   - **Issue**: All families without explicit preferences get the same default order
   - **Bias**: Creates implicit bias towards low-ID units for no good reason
   - **Solution needed**: Randomize auto-selection in a user-friendly way
   - **Challenges**:
     - Cannot dynamically randomize on every page visit (would confuse users)
     - Need persistent randomization per family (seed-based or stored?)
     - Must identify which preferences are "default" vs "explicit"
     - Need to communicate to users why their default preferences are ordered as they are
   - **Current location**: `LotteryService::preferences()` - auto-fills remaining units with `->sortBy('id')`
   - **UI considerations**:
     - Show visual indicator for auto-filled preferences?
     - Display explanation text: "These preferences were randomly assigned. Drag to reorder."
     - Badge or styling to differentiate explicit vs default?
   - **Ideas to explore**:
     - Seed random generator with family ID for consistency
     - Store "has_explicit_preferences" flag on families
     - Add "preference_source" column to unit_preferences pivot (explicit/default)
     - Generate and store defaults on family creation or first preference page visit

---

*Last updated: 5 December 2025*
