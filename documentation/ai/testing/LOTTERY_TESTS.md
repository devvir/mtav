# Lottery System - Testing Reference

**Status**: Comprehensive test coverage with minor async queue integration gaps

## Test Organization

```
tests/
├── Unit/Lottery/
│   ├── RandomSolverTest.php           ✅ Random assignment validation
│   ├── TestSolverTest.php             ✅ Deterministic assignment
│   ├── LotteryOrchestratorTest.php    ✅ Two-phase orchestration + error handling
│   ├── LotteryExecutionFlowTest.php   ✅ Event/listener integration
│   └── Glpk/
│       ├── GlpkSolverTest.php         ✅ GLPK failure scenarios
│       └── GlpkSolverMoreUnitsTest.php ✅ Unbalanced preference handling
│
└── Feature/Lottery/
    ├── GlpkSolverTest.php              ✅ GLPK optimal solutions
    ├── LotterySystemIntegrationTest.php ✅ End-to-end with universe.sql
    ├── ExecutionServiceTest.php         ✅ Validation & authorization
    ├── PreferencesValidationTest.php    ✅ Preference management
    └── AsyncExecutionTest.php           ✅ Async queue + audit persistence
```

---

## ✅ Unit Tests - COMPLETE

### RandomSolverTest.php
**Purpose**: Validate random assignment without duplicates

- ✅ Balanced lottery (5 families, 5 units)
- ✅ More units than families (3 families, 7 units → 4 orphan units)
- ✅ More families than units (8 families, 5 units → 3 orphan families)
- ✅ No duplicate assignments across multiple runs
- ✅ Randomness verification

### TestSolverTest.php
**Purpose**: Deterministic behavior for predictable testing

- ✅ Balanced deterministic (families [1,3,5], units [2,4,6] → [1=>2, 3=>4, 5=>6])
- ✅ More units deterministic (orphan units sorted by ID)
- ✅ More families deterministic (orphan families sorted by ID)
- ✅ Single pair edge case
- ✅ Empty input edge case

### LotteryOrchestratorTest.php
**Purpose**: Two-phase orchestration logic

- ✅ All balanced groups (no orphans)
- ✅ Mixed unit types (partial + complete)
- ✅ Orphan redistribution (cross-type assignments)
- ✅ Event dispatching (GroupLotteryExecuted, ProjectLotteryExecuted)
- ✅ **Error handling**:
  - Exception caught and logged
  - `AuditService::exception()` called
  - `ExecutionService::cancelExecutionReservation()` called
  - Empty result returned (job completes successfully)

### Glpk/GlpkSolverTest.php
**Purpose**: GLPK-specific failure scenarios

- ✅ Exception when glpsol binary not found
- ✅ Exception when temp directory not writable
- ✅ Temp file cleanup even on failure
- ✅ Timeout handling (impossible time limits)

---

## ✅ Feature Tests - COMPLETE

### GlpkSolverTest.php
**Purpose**: GLPK optimal solution verification

- ✅ Balanced optimal assignment respecting preferences
- ✅ Max-min fairness optimization
- ✅ Tie-breaking with overall satisfaction
- ✅ Deterministic across runs (same input → same output)
- ✅ Larger problems (10×10)
- ✅ More units than families (orphan units)
- ✅ More families than units (orphan families)
- ✅ Single family/unit edge case
- ✅ User-friendly exception messages
- ✅ Temp file cleanup (success + failure)

### LotterySystemIntegrationTest.php
**Purpose**: End-to-end with universe.sql fixture

- ✅ Complete balanced execution (Project #4, 3 unit types, 6 families)
- ✅ Unbalanced execution (Project #2, more units than families)
- ✅ Preference optimization verification
- ✅ Max-min fairness verification
- ✅ Multi-unit-type coordination

### ExecutionServiceTest.php
**Purpose**: Execution validation & authorization

- ✅ Atomic lottery reservation (race condition prevention)
- ✅ Validation: sufficient families (≥2)
- ✅ Validation: no existing assignments
- ✅ Validation: unit/family count consistency
- ✅ Override mechanism for count mismatches
- ✅ Authorization checks

### PreferencesValidationTest.php
**Purpose**: Preference management

- ✅ Dynamic preference resolution with auto-fill
- ✅ Sanitization removes invalid preferences
- ✅ Validation requires complete preferences
- ✅ Preference locking after execution starts

### AsyncExecutionTest.php
**Purpose**: Async queue + audit persistence

- ✅ Successful execution creates audit trail in database
- ✅ Solver failure creates FAILURE audit and releases lottery
- ✅ Failed execution can be retried with new audit trail
- ✅ Audit records share execution UUID
- ✅ INIT audit contains complete manifest data

---

## Test Execution

### Run All Lottery Tests
```bash
./mtav pest tests/Unit/Lottery tests/Feature/Lottery
```

### Run Specific Suites
```bash
# Solvers
./mtav pest tests/Unit/Lottery/RandomSolverTest.php
./mtav pest tests/Unit/Lottery/TestSolverTest.php
./mtav pest tests/Unit/Lottery/Glpk/

# GLPK Integration
./mtav pest tests/Feature/Lottery/GlpkSolverTest.php

# System Integration
./mtav pest tests/Feature/Lottery/LotterySystemIntegrationTest.php

# Async Queue
./mtav pest tests/Feature/Lottery/AsyncExecutionTest.php
```

### With Coverage
```bash
./mtav pest --coverage tests/Unit/Lottery tests/Feature/Lottery
```

---

## Key Testing Patterns

### 1. Using universe.sql Fixture
```php
setCurrentProject(4); // Use Project #4 from universe.sql
$lottery = Event::find(13); // Lottery for Project #4
$family = Family::find(16); // Family in Project #4
```

**Benefits**: 20-30x faster than factories, predictable data

### 2. Testing Error Handling
```php
// Force failure
Config::set('lottery.solvers.glpk.config.glpsol_path', '/nonexistent');
Config::set('lottery.default', 'glpk');

// Execute and verify graceful handling
$this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

// Check for FAILURE audit
$failureAudit = LotteryAudit::where('type', 'failure')->first();
expect($failureAudit)->not->toBeNull();
```

### 3. Testing Audit Trail
```php
// Execute lottery
$this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

// Verify audit types
$audits = LotteryAudit::where('lottery_id', $lottery->id)->get();
expect($audits->pluck('type')->unique()->toArray())
    ->toContain('init', 'group_execution', 'project_execution');
```

### 4. Testing Retries
```php
// First attempt: fail
Config::set('lottery.default', 'glpk');
Config::set('lottery.solvers.glpk.config.glpsol_path', '/nonexistent');
$this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

// Second attempt: succeed
Config::set('lottery.default', 'test');
$lottery->refresh();
expect($lottery->is_published)->toBeTrue(); // Released after failure
$this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

// Verify different execution UUIDs
$uuids = LotteryAudit::where('lottery_id', $lottery->id)
    ->where('type', 'init')
    ->pluck('execution_uuid')
    ->unique();
expect($uuids->count())->toBe(2);
```

---

## Success Criteria

- ✅ **Solver Tests**: All passing (RandomSolver, TestSolver, GlpkSolver)
- ✅ **Orchestrator Tests**: All passing (two-phase logic, error handling)
- ✅ **Integration Tests**: All passing (universe.sql fixture)
- ✅ **Async Tests**: All passing (audit persistence, retry logic)
- ⏳ **Coverage**: Target 90%+ (run with `--coverage` to verify)

---

## Outstanding Items

### None - All Critical Tests Complete ✅

The lottery testing suite is comprehensive and covers:
- Unit-level solver behavior
- Orchestration logic (two-phase + orphan redistribution)
- Error handling and recovery
- End-to-end integration with real fixture data
- Async queue behavior and audit persistence
- Multiple execution attempts (retry scenarios)

### Future Enhancements (Optional)
- Performance benchmarks for large datasets (100+ families/units)
- Load testing for concurrent lottery executions (multi-project)
- UI/E2E tests for admin lottery execution flow
- Notification delivery tests (when implemented)

---

## Related Documentation

- **`documentation/ai/LOTTERY.md`** - Complete lottery system reference
- **`tests/_fixtures/UNIVERSE.md`** - Test data structure details
- **`documentation/ai/testing/PHILOSOPHY.md`** - General testing patterns

---

*Last updated: 5 December 2025*
