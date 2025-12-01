# Lottery System - Complete Reference

## Executive Summary

The lottery is the **primary purpose** of the entire MTAV application - a fair, transparent, ONE-TIME housing unit assignment system for cooperative projects. It's an atomic event that permanently assigns ALL units to ALL families simultaneously using mathematical optimization to maximize overall satisfaction.

**Current Status**: Phase 1 HTTP Layer complete (controllers, routes, UI, validation, locking). Phase 2 Business Logic (execution algorithm) pending implementation.

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

## Lottery Execution Phases

### Overview

When the lottery executes (after all validation passes), it processes families and units in **four distinct distribution phases**. This multi-phase approach handles the reality that unit/family counts may not match perfectly per unit type, even when an admin overrides the consistency check.

The phases are ordered **optimistically** - handling the most common scenarios first, leaving edge cases for later phases.

### Phase 1: Complete Distribution

**Condition**: `units == families` for this unit type

**Process**:
- Standard lottery algorithm execution
- Perfect N:N match, no padding needed
- All families get units, all units get families

**Pre-fill**: None required

**Result**: All families assigned, all units assigned

**Fairness**: Pure algorithm fairness - everyone gets their ranked preferences considered equally

---

### Phase 2: Partial Distribution

**Condition**: `units > families` for this unit type

**Process**:
1. Run lottery for original families of this unit type
2. Algorithm decides which units each family gets
3. Least-preferred units remain unassigned (spare units)

**Pre-fill**: Mock families needed to pad to unit count (pending consultation)

**Result**:
- All families assigned to their preferred unit type
- Some units remain unlinked (overflow to Phase 4)

**Fairness**: Original families get same fairness as Phase 1. Unlinked units are those that were collectively least-preferred by this group.

---

### Phase 3: Best-Attempt Distribution

**Condition**: `units < families` for this unit type

**Process**: *(Pending consultation with tutor and cooperative representatives)*
- Need to determine how to handle mock units
- Need to decide fairness criteria for who doesn't get a unit

**Pre-fill**: Mock units needed to pad to family count

**Result**:
- Some families assigned to their preferred unit type
- Some families remain unlinked (overflow to Phase 4)

**Fairness**: Algorithm decides who gets real units vs mock units. Families who "lost" in their preferred type lottery get a second chance in Phase 4.

---

### Phase 4: Second-Chance Distribution

**Condition**: Has unlinked families from Phase 3 AND/OR unlinked units from Phase 2

**Process**: *(Pending consultation with tutor and cooperative representatives)*
1. Collect all unlinked families (from Phase 3, across all unit types)
2. Collect all unlinked units (from Phase 2, across all unit types)
3. Run single lottery with mixed unit types
4. Need to determine preference generation for cross-type assignments

**Pre-fill**:
- Mock families if `unlinked_units > unlinked_families`
- Mock units if `unlinked_families > unlinked_units`
- None if counts match

**Result**:
- Maximum possible assignments given global constraints
- May have remaining unlinked families (if `total_units < total_families` globally)
- May have remaining unlinked units (if `total_units > total_families` globally)

**Fairness**: These families already "lost" their preferred type lottery, so getting any unit (even non-preferred type) is better than no home. It's fair because they compete for units that were collectively least-preferred by others.

---

### Execution Order Rationale

**Why this order?**
1. **Complete first**: Most likely scenario in well-planned projects, gets them out of the way
2. **Partial second**: Creates spare units that can be used in Phase 4 if needed
3. **Best-attempt third**: Identifies families who need second chance
4. **Second-chance last**: Redistributes resources to maximize assignments

**Critical guarantee**: If `total_units >= total_families` globally, then all families will be assigned (no homeless families). The algorithm ensures we never have both unassigned families AND unassigned units simultaneously (that would mean the redistribution failed).

---

### Data Flow Between Phases

```
Phase 1 (Complete)     → Assignments only
Phase 2 (Partial)      → Assignments + Spare Units
Phase 3 (Best-Attempt) → Assignments + Unlinked Families
Phase 4 (Second-Chance) → Final Assignments + Final Unlinked (families/units)
```

**Final State**:
- `unlinked_families`: Only if global deficit (`total_units < total_families`)
- `unlinked_units`: Only if global surplus (`total_units > total_families`)
- **Never both**: Phase 4 guarantees maximum utilization

---

## Architecture Overview

### Layer Separation

The lottery system is built in distinct layers with clear boundaries:

1. **HTTP Layer (Phase 1)** - ✅ Complete
   - Controllers, routes, validation, authorization
   - User interface components
   - Exception handling

2. **Orchestration Layer (Phase 2)** - 🚧 In Progress
   - Event-driven execution trigger
   - Multi-phase lottery orchestration
   - Data transformation and execution delegation

3. **Execution Layer (Phase 2)** - 🚧 In Progress
   - Strategy pattern for different executors
   - Single-lottery execution (per unit type)
   - Result generation

4. **Persistence Layer (Phase 3)** - ⏳ Pending
   - Audit trail creation
   - Assignment application to database
   - Notification queueing

### Data Flow

```
ExecutionService (validates & transforms)
  → LotteryManifest (complete project data)
    → LotteryExecution (queued)
      → ExecuteLotteryListener (resolves executor)
        → LotteryOrchestrator (multi-phase coordination)
          ├─ Reports progress → LotteryService::executionReport()
          │                      ├─ ReportType (PHASE_1_START, etc.)
          │                      └─ ExecutionResult (picks + orphans)
          └─ Per phase:
              → LotterySpec (single unit type)
                → ExecutorInterface (RandomExecutor, TestExecutor, ApiExecutor...)
                  → Results (picks + orphans)
                    → [Audit Collaborator - TODO in executionReport()]
                      → Database & Notifications
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
- ✅ `executionReport()` - Receives execution reports from orchestrator (TODO: implement persistence)

**ConsistencyService** (`app/Services/Lottery/ConsistencyService.php`):
- ✅ `sanitizeBeforeFetch()` - Remove invalid preferences before fetch
- ✅ `validateBeforeUpdate()` - Ensure completeness and check execution lock

**ExecutionService** (`app/Services/Lottery/ExecutionService.php`):
- ✅ `execute()` - Entry point: validates and dispatches execution event
- ✅ `reserveLotteryForExecution()` - Atomic flag update (`is_published` → false)
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
│   └── LotteryManagement.vue         # Admin config & execution (Phase 1.B pending)
├── member/
│   └── PreferencesManager.vue        # Drag-and-drop preference ordering ✅
├── shared/
│   ├── LotteryHeader.vue            # Page header with description ✅
│   ├── LotteryContent.vue           # Role-based component router ✅
│   ├── LotteryFooter.vue            # Project plan integration ✅
│   └── ProjectPlan.vue              # Placeholder for Phase 3 ✅
├── composables/
│   └── useLottery.ts                # Shared lottery state/logic ✅
├── types.d.ts                       # TypeScript definitions ✅
└── index.ts                         # Public exports ✅
```

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

**Current State**: Event-driven architecture implemented with clean layer separation. Core execution components created.

#### Completed Components

**ExecutionService** (`app/Services/Lottery/ExecutionService.php`):
- ✅ Complete validation layer (atomic reservation, data integrity, count consistency)
- ✅ Transform high-level models to `LotteryManifest`
- ✅ Dispatch `LotteryExecution` (queued)
- ✅ Clean boundary - no return value, just triggers process

**Data Objects** (`app/Services/Lottery/DataObjects/`):
- ✅ `LotteryManifest` - Complete project lottery inventory (all unit types)
- ✅ `LotterySpec` - Single unit type specification for execution
- ✅ `ExecutionResult` - Encapsulates picks and orphans from execution phase
- ✅ `LotteryManifest` and `LotterySpec` implement `__serialize/__unserialize` for queue compatibility

**Event System**:
- ✅ `LotteryExecution` - Implements `ShouldQueue`, carries `LotteryManifest`
- ✅ `ExecuteLotteryListener` - Resolves executor from config, delegates to orchestrator
- ✅ Config-driven executor resolution (`config/lottery.php`)

**Executor Strategy** (`app/Services/Lottery/Contracts/ExecutorInterface.php`):
- ✅ Interface defined: `execute(LotterySpec $spec): array`
- ✅ Returns: `['picks' => [...], 'orphans' => ['families' => [...], 'units' => [...]]]`
- ✅ `RandomExecutor` - Fully implemented (shuffles families and units, pairs via array_combine)
- ✅ `TestExecutor` - Fully implemented (sorts both lists by ID, pairs via array_combine - deterministic)

**Configuration** (`config/lottery.php`):
- ✅ Default executor via `LOTTERY_EXECUTOR` env variable
- ✅ Executor definitions with class FQN and config array
- ✅ Example commented for external API executor (Acme)

**LotteryOrchestrator** (`app/Services/Lottery/LotteryOrchestrator.php`):
- ✅ Receives `LotteryManifest` and `ExecutorInterface`
- ✅ Unpacks manifest into `LotterySpec` objects (one per unit type)
- ✅ Three-phase execution strategy implemented:
  - Phase 1: Complete & Partial Distribution (units >= families)
  - Phase 2: Best-Attempt Distribution (units < families)
  - Phase 3: Second-Chance Distribution (remaining orphans)
- ✅ Tracks picks and orphans across all phases
- ✅ Reports progress to `LotteryService::executionReport()` at each phase
- ✅ Logs summary results via `reportResults()`
- ⏳ TODO: Audit collaborator for persistence (pending in `LotteryService::executionReport()`)

#### Architecture Details

**Clean Boundaries**:
```php
// ExecutionService → Event (no coupling to execution logic)
$manifest = new LotteryManifest($lottery->project);
LotteryExecution::dispatch($manifest);

// Listener → Orchestrator (just passes data)
$executor = $this->resolveExecutor();
$orchestrator = LotteryOrchestrator::make($executor, $event->manifest);
$orchestrator->execute(); // No return value

// Orchestrator → Executor (per unit type)
foreach ($this->manifest->getData() as $unitTypeId => $typeData) {
    $spec = new LotterySpec($typeData['families'], $typeData['units']);
    $result = $this->executor->execute($spec);
    // Aggregate picks and orphans, report progress
}
```

**Config-Driven Execution**:
```php
// config/lottery.php
'executors' => [
    'random' => [
        'executor' => RandomExecutor::class,
        'config' => [],
    ],
    'test' => [
        'executor' => TestExecutor::class,
        'config' => [],
    ],
],

// Resolved via Laravel container
$executor = app()->makeWith($executorClass, ['config' => $config]);
```

**Reporting System**:
- ✅ `ReportType` enum - Defines report types (PHASE_1_START, PHASE_1_COMPLETE, etc.)
- ✅ `LotteryService::executionReport()` - Receives execution progress reports
- ✅ `ExecutionResult` data object - Encapsulates picks and orphans for reporting
- ✅ Phase-by-phase reporting integrated into orchestrator
- ⏳ TODO: Implement persistence and audit trail in `executionReport()`

#### Pending Components

1. **Audit Collaborator Service** (invoked from `LotteryService::executionReport()`)
   - Persist execution results
   - Create audit records with phase progression
   - Track input/output data with metadata
   - Soft-delete lottery event on completion

2. **Assignment Application**
   - Bulk UPDATE `units.family_id`
   - Single transaction for atomicity

3. **Notification System**
   - Queue `LotteryResultNotification` to families
   - Email with assignment details

4. **Frontend Updates**
   - Loading states during execution
   - Display assignment results
   - Success/error messaging

#### Testing Strategy

**Unit Tests**:
- ✅ `RandomExecutorTest` - Validate pick/orphan counts, no duplicate IDs in picks
- ✅ `TestExecutorTest` - Validate exact deterministic output for balanced/unbalanced data
- ✅ `LotteryOrchestratorTest` - Three-phase logic with various manifest scenarios
- ⏳ Event/Listener integration - End-to-end event dispatch through orchestrator

**Feature Tests**:
- ✅ `PreferencesValidationTest` - Preference management and validation
- ✅ `ExecutionServiceTest` - Execution endpoint authorization, validation, and locking
- ⏳ End-to-end execution with RandomExecutor
- ⏳ Result persistence and retrieval

**Integration Tests**:
- ⏳ Complete flow with universe.sql fixture
- ⏳ Multi-type projects (apartments + houses)
- ⏳ Orphan handling (mismatched counts)

### 📅 Phase 3: Audit Trail & Persistence (NEXT)

#### Audit System Architecture

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

**UUID Grouping**: All audit records from a single orchestrator run share the same UUID:
- Allows grouping phase executions together
- Essential for debugging failed runs
- Enables tracing execution flow
- Supports multiple runs per lottery (e.g., after failures)

**Audit Types**:

1. **Type: `execution`** (Granular Trail)
   - Created by: `LotteryGroupExecuted` event (one per unit-type group)
   - Purpose: Track individual group executions for debugging/auditing
   - Multiple per project execution (one per unit type)
   - Contains: Unit type data, picks, orphans, executor config
   - User visibility: Internal only (auditing/debugging)
   - Example audit data:
     ```json
     {
       "status": "created|inprogress|complete",
       "unit_type_id": 1,
       "families": [1, 2, 3],
       "units": [10, 11, 12],
       "picks": {1: 10, 2: 11},
       "orphans": {"families": [3], "units": [12]},
       "executor": "random",
       "metadata": {...}
     }
     ```

2. **Type: `result`** (Completion Marker)
   - Created by: `LotteryExecuted` event (once per complete execution)
   - Purpose: Mark project lottery as complete
   - Single per successful execution
   - Contains: Final aggregated results, all picks, all orphans
   - User visibility: Determines lottery completion status
   - Example audit data:
     ```json
     {
       "status": "complete",
       "total_families": 10,
       "total_units": 10,
       "total_picks": 9,
       "total_orphans": 1,
       "picks": {1: 10, 2: 11, ...},
       "orphans": {"families": [3], "units": [12]},
       "execution_time_ms": 245,
       "phases_completed": 3
     }
     ```

**Status Tracking Strategy**:
- No dedicated status column in lottery_audits table
- Status derived from presence/absence of `type=result` audit
- Project/Lottery models check: `hasAudit('result')` → lottery complete
- Simple, reliable: either completed or not
- Audit JSON can include internal status for debugging (created/inprogress/complete)

**Multiple Runs Scenario**:
```
Project #1, Lottery #50:
  Run 1 (UUID: abc-123) - Failed during Phase 2
    ├─ execution audit (Phase 1, UnitType 1)
    ├─ execution audit (Phase 1, UnitType 2)
    └─ execution audit (Phase 2, partial) ← failure point

  Run 2 (UUID: def-456) - Succeeded
    ├─ execution audit (Phase 1, UnitType 1)
    ├─ execution audit (Phase 1, UnitType 2)
    ├─ execution audit (Phase 2, complete)
    └─ result audit (final completion) ← marks lottery as complete
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

**Model** (`app/Models/LotteryAudit.php`):
```php
use HasUuids;

protected $primaryKey = 'uuid';
protected $keyType = 'string';
public $incrementing = false;

protected $casts = [
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

// Query scopes
public function scopeExecution(Builder $query): void
{
    $query->where('type', 'execution');
}

public function scopeResult(Builder $query): void
{
    $query->where('type', 'result');
}

public function scopeForRun(Builder $query, string $uuid): void
{
    $query->where('uuid', $uuid);
}
```

**Events** (`app/Events/Lottery/`):
```php
// Dispatched after each group execution
class LotteryGroupExecuted
{
    public function __construct(
        public string $uuid,                    // Orchestrator run ID
        public LotteryManifest $manifest,       // Full project data
        public ExecutorInterface $executor,     // Which executor ran
        public ExecutionResult $result          // Picks + orphans
    ) {}
}

// Dispatched after all groups complete
class LotteryExecuted
{
    public function __construct(
        public string $uuid,                    // Orchestrator run ID
        public LotteryManifest $manifest,       // Full project data
        public ExecutorInterface $executor,     // Which executor ran
        public array $report                    // Aggregated results
    ) {}
}
```

**Listener** (`app/Listeners/Lottery/LotteryExecutedListener.php`):
- Handles both `LotteryGroupExecuted` and `LotteryExecuted`
- Creates appropriate audit records based on event type
- ⏳ TODO: Implement audit creation logic
- ⏳ TODO: Handle assignment application (bulk update units.family_id)
- ⏳ TODO: Queue notifications to families

**Integration Points**:
- `LotteryOrchestrator::execute()`: Generates UUID, dispatches events
- `LotteryOrchestrator::executeLottery()`: Dispatches `LotteryGroupExecuted` after each phase
- `LotteryOrchestrator::reportResults()`: Dispatches `LotteryExecuted` after completion
- Events queued for async processing (ShouldQueue)

**Benefits**:
- ✅ Complete audit trail for compliance
- ✅ Debug failed executions with grouped audits
- ✅ Support multiple lottery runs per project
- ✅ Handle lottery invalidations gracefully
- ✅ Simple status checking (has result audit = complete)
- ✅ Preserve historical data for legal requirements
- ✅ Easy to query: "Show me all runs for this lottery"

### 📅 Phase 4: Production Enhancements (FUTURE)

- Advanced validation (deadline enforcement)
- Real-time progress updates during execution
- Satisfaction scoring display
- Enhanced audit trail with digital signatures
- Result verification system
- Regulatory compliance features
- Performance monitoring and analytics
- Email notifications with assignment details

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

### Executor Selection

```php
// config/lottery.php
return [
    'default' => env('LOTTERY_EXECUTOR', 'random'),

    'executors' => [
        'random' => [
            'executor' => \App\Services\Lottery\Executors\RandomExecutor::class,
        ],

        'test' => [
            'executor' => \App\Services\Lottery\Executors\TestExecutor::class,
        ],

        // Example: External API executor
        // 'acme' => [
        //     'executor' => \App\Services\Lottery\Executors\AcmeExecutor::class,
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

**Usage**: Set `LOTTERY_EXECUTOR=random` (default) or `LOTTERY_EXECUTOR=test` in `.env`

### Executor Resolution

Executors are resolved in `ExecuteLotteryListener` using Laravel's service container:

```php
protected function resolveExecutor(): ExecutorInterface
{
    $default = config('lottery.default');
    $executorConfig = config("lottery.executors.{$default}");

    $executorClass = $executorConfig['executor'];
    $config = $executorConfig['config'] ?? [];

    return app()->makeWith($executorClass, ['config' => $config]);
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

## Related Documentation

- **`documentation/ai/KNOWLEDGE_BASE.md`** - Business domain overview
- **`documentation/ai/testing/PHILOSOPHY.md`** - Testing patterns
- **`tests/_fixtures/UNIVERSE.md`** - Test data structure
- **`documentation/ai/ProjectPlans.md`** - Phase 3 spatial visualization
- **`resources/js/components/lottery/README.md`** - Frontend technical details
- **`.github/copilot-instructions.md`** - Code style and conventions

---

*Last updated: 2 December 2025*
