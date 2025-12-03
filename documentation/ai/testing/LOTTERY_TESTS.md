# Lottery Testing Plan

## Overview

Comprehensive test coverage for the lottery execution system, focusing on:
1. Individual solver behavior (RandomSolver, TestSolver)
2. Multi-phase orchestration logic (LotteryOrchestrator)
3. Event-driven integration (ExecutionService → Event → Listener → Orchestrator)

## Test Structure

```
tests/Unit/Lottery/
├── RandomSolverTest.php      # Random assignment validation
├── TestSolverTest.php         # Deterministic assignment verification
├── LotteryOrchestratorTest.php  # Multi-phase orchestration
└── ExecutionIntegrationTest.php # Event/listener bridge
```

## 1. RandomSolverTest

**Purpose**: Validate that RandomSolver produces valid, random assignments without duplicates.

### Test Cases

#### test_balanced_lottery()
- **Input**: 5 families, 5 units
- **Assertions**:
  - 5 picks returned
  - 0 orphan families
  - 0 orphan units
  - All family IDs appear exactly once in picks (keys)
  - All unit IDs appear exactly once in picks (values)
  - No duplicates in picks

#### test_more_units_than_families()
- **Input**: 3 families, 7 units
- **Assertions**:
  - 3 picks returned
  - 0 orphan families
  - 4 orphan units
  - All family IDs matched
  - 4 specific unit IDs remain unmatched
  - Total: 3 assigned + 4 orphaned = 7 units

#### test_more_families_than_units()
- **Input**: 8 families, 5 units
- **Assertions**:
  - 5 picks returned
  - 3 orphan families
  - 0 orphan units
  - All unit IDs matched
  - 3 specific family IDs remain unmatched
  - Total: 5 assigned + 3 orphaned = 8 families

#### test_no_duplicate_assignments()
- **Input**: 10 families, 10 units
- **Run**: Multiple times (randomness verification)
- **Assertions**:
  - Each execution produces different order (randomness)
  - No family assigned to multiple units
  - No unit assigned to multiple families
  - Set of family IDs in picks matches input
  - Set of unit IDs in picks matches input

## 2. TestSolverTest

**Purpose**: Validate deterministic behavior for predictable testing.

### Test Cases

#### test_balanced_deterministic_assignment()
- **Input**: Families [1, 3, 5], Units [2, 4, 6]
- **Expected Output**:
  - Picks: [1 => 2, 3 => 4, 5 => 6]
  - Orphans: families = [], units = []
- **Assertions**:
  - Exact match on picks
  - No orphans
  - Order by ID ascending

#### test_more_units_deterministic()
- **Input**: Families [10, 20], Units [5, 15, 25, 35]
- **Expected Output**:
  - Picks: [10 => 5, 20 => 15]
  - Orphans: families = [], units = [25, 35]
- **Assertions**:
  - Exact pick mapping
  - Exact orphan units (sorted by ID)

#### test_more_families_deterministic()
- **Input**: Families [2, 4, 6, 8], Units [1, 3]
- **Expected Output**:
  - Picks: [2 => 1, 4 => 3]
  - Orphans: families = [6, 8], units = []
- **Assertions**:
  - Exact pick mapping
  - Exact orphan families (sorted by ID)

#### test_single_pair()
- **Input**: Families [100], Units [200]
- **Expected Output**:
  - Picks: [100 => 200]
  - Orphans: families = [], units = []

#### test_empty_input()
- **Input**: Families [], Units []
- **Expected Output**:
  - Picks: []
  - Orphans: families = [], units = []

## 3. LotteryOrchestratorTest

**Purpose**: Validate three-phase execution strategy with complex scenarios.

### Test Cases

#### test_all_balanced_groups()
- **Manifest**:
  - Type 1: 3 families, 3 units
  - Type 2: 5 families, 5 units
- **Expected**:
  - Phase 1: Both types processed
  - Phase 2: Skipped (no groups with excess families)
  - Phase 3: Skipped (no orphans)
  - Total picks: 8
  - Orphan families: 0
  - Orphan units: 0

#### test_complete_and_partial_only()
- **Manifest**:
  - Type 1: 2 families, 5 units (partial)
  - Type 2: 4 families, 4 units (complete)
- **Expected**:
  - Phase 1: Both types processed
  - Phase 2: Skipped
  - Phase 3: Skipped
  - Total picks: 6
  - Orphan families: 0
  - Orphan units: 3 (from Type 1)

#### test_best_attempt_only()
- **Manifest**:
  - Type 1: 6 families, 3 units (best-attempt)
  - Type 2: 4 families, 2 units (best-attempt)
- **Expected**:
  - Phase 1: Skipped
  - Phase 2: Both types processed
  - Phase 3: Skipped (no orphan units to match)
  - Total picks: 5
  - Orphan families: 5 (3 from Type 1, 2 from Type 2)
  - Orphan units: 0

#### test_second_chance_redistribution()
- **Manifest**:
  - Type 1: 3 families, 7 units (partial - produces 4 orphan units)
  - Type 2: 8 families, 5 units (best-attempt - produces 3 orphan families)
- **Expected**:
  - Phase 1: Type 1 processed (3 picks, 4 orphan units)
  - Phase 2: Type 2 processed (5 picks, 3 orphan families)
  - Phase 3: Redistributes 3 orphan families + 4 orphan units (3 more picks)
  - Total picks: 11 (3 + 5 + 3)
  - Orphan families: 0
  - Orphan units: 1 (4 - 3 = 1 remaining)

#### test_mixed_all_phases()
- **Manifest**:
  - Type 1: 5 families, 5 units (complete)
  - Type 2: 2 families, 6 units (partial)
  - Type 3: 7 families, 3 units (best-attempt)
- **Expected**:
  - Phase 1: Types 1, 2 processed (7 picks, 4 orphan units)
  - Phase 2: Type 3 processed (3 picks, 4 orphan families)
  - Phase 3: Redistributes 4 families + 4 units (4 more picks)
  - Total picks: 14 (5 + 2 + 3 + 4)
  - Orphan families: 0
  - Orphan units: 0

#### test_logs_summary()
- **Manifest**: Any valid manifest
- **Assertions**:
  - Verify Log::info() called with correct structure
  - Contains: project_id, total_picks, orphan_families, orphan_units

## 4. ExecutionIntegrationTest

**Purpose**: Validate event-driven flow from ExecutionService through to orchestration.

### Test Cases

#### test_event_dispatched_with_manifest()
- **Setup**: Create lottery with families/units
- **Action**: Call ExecutionService::execute()
- **Assertions**:
  - LotteryExecution dispatched
  - Event contains LotteryManifest
  - Manifest has correct project_id
  - Manifest data structure matches expected format

#### test_listener_resolves_solver()
- **Setup**: Mock solver in config
- **Action**: Dispatch LotteryExecution
- **Assertions**:
  - ExecuteLotteryListener invoked
  - Correct solver resolved from config
  - Orchestrator created with solver + manifest

#### test_end_to_end_with_test_solver()
- **Setup**:
  - Configure TestSolver as default
  - Create project with multiple unit types
  - Create families and units
- **Action**: Execute lottery via ExecutionService
- **Assertions**:
  - Event dispatched
  - Listener processes event
  - Orchestrator executes all phases
  - Results logged
  - Deterministic output matches expectations

#### test_end_to_end_with_random_solver()
- **Setup**:
  - Configure RandomSolver as default
  - Create balanced project
- **Action**: Execute lottery via ExecutionService
- **Assertions**:
  - All families assigned
  - All units assigned
  - No orphans
  - Results logged with correct counts

## Test Data Helpers

### LotteryManifestFactory

```php
class LotteryManifestFactory
{
    public static function balanced(int $projectId): LotteryManifest
    {
        // Creates manifest with equal families/units per type
    }

    public static function withOrphans(int $projectId): LotteryManifest
    {
        // Creates manifest with mismatched counts
    }

    public static function multiPhase(int $projectId): LotteryManifest
    {
        // Creates complex manifest requiring all three phases
    }
}
```

### Assertions Helper

```php
trait LotteryAssertions
{
    protected function assertValidPicks(array $picks, array $families, array $units): void
    {
        // No duplicates
        // All family IDs valid
        // All unit IDs valid
        // Count matches min(families, units)
    }

    protected function assertValidOrphans(array $orphans, int $familyCount, int $unitCount, int $pickCount): void
    {
        // Orphan counts add up
        // No orphan overlaps with picks
    }
}
```

## Running Tests

```bash
# All lottery tests
./mtav test tests/Unit/Lottery/

# Individual test suites
./mtav test tests/Unit/Lottery/RandomSolverTest.php
./mtav test tests/Unit/Lottery/TestSolverTest.php
./mtav test tests/Unit/Lottery/LotteryOrchestratorTest.php
./mtav test tests/Unit/Lottery/ExecutionIntegrationTest.php

# With coverage
./mtav test --coverage tests/Unit/Lottery/
```

## Success Criteria

- ✅ **COMPLETED**: All tests pass without modifying implementation code
- ✅ **COMPLETED**: TestSolver - 6/6 tests passing (deterministic behavior verified)
- ✅ **COMPLETED**: RandomSolver - 6/6 tests passing (randomness verified)
- ✅ **COMPLETED**: LotteryOrchestrator - 8/8 tests passing (all phases verified)
- ✅ **COMPLETED**: All edge cases handled (empty, single, unbalanced)
- ✅ **COMPLETED**: Logging verified (reportResults)
- ⏳ **PENDING**: Event/listener integration tests (ExecutionIntegrationTest)
- ⏳ **PENDING**: Code coverage metrics

## Next Steps After Tests Pass

1. Implement audit collaborator service
2. Persist results to database
3. Apply assignments to units.family_id
4. Queue notifications
5. Update frontend to display results
6. Integration with GLPK optimization API
