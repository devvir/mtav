#!/bin/bash

# Master test runner for all MTAV Docker functionality

set -e
cd "$(dirname "$0")/../.."

# Source common test utilities
source .docker/test/common.sh

# Test suite files
TEST_SUITES=(
    ".docker/test/scripts.sh"
    ".docker/test/build.sh"
    ".docker/test/mtav.sh"
    ".docker/test/dev.sh"
    ".docker/test/prod.sh"
)

# Master test counters
TOTAL_TESTS_RUN=0
TOTAL_TESTS_PASSED=0
TOTAL_TESTS_FAILED=0
SUITES_PASSED=0
SUITES_FAILED=0

echo ""
echo "🧪 MTAV Docker Test Suite Runner"
echo "=================================="
echo ""

log_info "Running comprehensive tests for MTAV Docker functionality"
log_info "Test suites to run: ${#TEST_SUITES[@]}"
echo ""

# Pre-flight checks
log_info "Pre-flight checks..."

# Check Docker availability
if ! command -v docker >/dev/null 2>&1; then
    log_error "Docker is not available. Please install Docker to run tests."
    exit 1
fi

# Check Docker daemon
if ! docker info >/dev/null 2>&1; then
    log_error "Docker daemon is not running. Please start Docker."
    exit 1
fi

# Check we're in the right directory
if [ ! -f "./mtav" ]; then
    log_error "Tests must be run from the MTAV project root directory."
    exit 1
fi

log_success "Pre-flight checks passed"
echo ""

# Run each test suite
for suite in "${TEST_SUITES[@]}"; do
    suite_name=$(basename "$suite" .sh)

    echo ""
    echo "🏃 Running test suite: $suite_name"
    echo "=================================================="

    # Reset counters for this suite
    TESTS_RUN=0
    TESTS_PASSED=0
    TESTS_FAILED=0

    # Make test script executable
    chmod +x "$suite"

    # Run the test suite
    if "$suite"; then
        log_success "✅ Test suite '$suite_name' PASSED"
        SUITES_PASSED=$((SUITES_PASSED + 1))
    else
        log_error "❌ Test suite '$suite_name' FAILED"
        SUITES_FAILED=$((SUITES_FAILED + 1))
    fi

    # Accumulate totals
    TOTAL_TESTS_RUN=$((TOTAL_TESTS_RUN + TESTS_RUN))
    TOTAL_TESTS_PASSED=$((TOTAL_TESTS_PASSED + TESTS_PASSED))
    TOTAL_TESTS_FAILED=$((TOTAL_TESTS_FAILED + TESTS_FAILED))

    echo ""
done

# Final results
echo ""
echo "🏁 FINAL RESULTS"
echo "=================================="
echo ""

log_info "Test Suites Summary:"
echo "  Total suites: $((SUITES_PASSED + SUITES_FAILED))"
echo "  Passed: $SUITES_PASSED"
echo "  Failed: $SUITES_FAILED"
echo ""

log_info "Individual Tests Summary:"
echo "  Total tests: $TOTAL_TESTS_RUN"
echo "  Passed: $TOTAL_TESTS_PASSED"
echo "  Failed: $TOTAL_TESTS_FAILED"
echo ""

# Calculate success rate
if [ $TOTAL_TESTS_RUN -gt 0 ]; then
    success_rate=$((TOTAL_TESTS_PASSED * 100 / TOTAL_TESTS_RUN))
    log_info "Success rate: ${success_rate}%"
fi

echo ""

# Final verdict
if [ $SUITES_FAILED -eq 0 ] && [ $TOTAL_TESTS_FAILED -eq 0 ]; then
    log_success "🎉 ALL TESTS PASSED!"
    log_success "MTAV Docker system is functioning correctly"
    exit 0
else
    log_error "💥 SOME TESTS FAILED"
    log_error "Please review the failures above and fix the issues"

    if [ $SUITES_FAILED -gt 0 ]; then
        log_error "Failed test suites: $SUITES_FAILED"
    fi

    if [ $TOTAL_TESTS_FAILED -gt 0 ]; then
        log_error "Failed individual tests: $TOTAL_TESTS_FAILED"
    fi

    exit 1
fi