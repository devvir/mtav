<!-- Copilot - Pending review -->

# Stress Tests

Stress tests validate edge cases, timeouts, degenerate scenarios, and features under extreme conditions that are not part of normal operation. These tests are kept separate from unit/feature tests to avoid slowing down regular test runs.

## When to Run

### Regular Development
```bash
# Normal test suite (excludes stress tests) - ~30-40 seconds
mtav pest
```

### Before Deployment / Major Changes
```bash
# Include stress tests - ~5-10 minutes additional
mtav pest --testsuite Stress
# Or all tests
mtav pest
```

### Debugging Edge Cases
```bash
# Run specific stress test
mtav pest tests/Stress/Lottery/DegeneracyTimeoutTest.php

# Run all Lottery stress tests
mtav pest tests/Stress/Lottery/
```

## Test Categories

### Degeneracy Timeout (`DegeneracyTimeoutTest.php`)
- **Purpose**: Verify GLPK solver handles degenerate preference sets without hanging
- **Scenario**: All families have identical preference rankings (pathological case)
- **Expected**: Solver completes within timeout (not infinite loops)
- **Runtime**: ~1-2 seconds

### Confirmation Flow (`ConfirmationFlowTest.php`)
- **Purpose**: Validate user confirmation flow for lottery execution options
- **Scenarios**:
  - Option mismatch detection
  - Option accumulation across requests
  - UI option passing and persistence
- **Status**: Tests incomplete features (will pass once option confirmation is implemented)
- **Runtime**: ~1 second

### System Integration (`LotterySystemIntegrationTest.php`)
- **Purpose**: End-to-end system tests with various data scenarios
- **Scenarios**:
  - Balanced families/units (normal)
  - Unbalanced units (more units than families) - requires mismatch-allowed option
  - Multi-unit-type lotteries
  - Fairness verification
- **Status**: 4 passing, 1 incomplete (unbalanced - requires HTTP options layer)
- **Runtime**: ~2-3 seconds per test

## Future Work

These tests serve as acceptance criteria for:
1. Option confirmation flow implementation (currently incomplete)
2. Mismatch-allowed option handling in HTTP layer
3. Advanced solver edge case handling

Once these features are complete, the tests can potentially be moved back to Feature tests if they become part of normal operation.
