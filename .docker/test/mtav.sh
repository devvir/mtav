#!/bin/bash

# Test script for mtav command functionality

set -e
cd "$(dirname "$0")/../.."

# Source common test utilities
source .docker/test/common.sh

start_test_suite "MTAV Command Interface"

# Test variables
TEST_TAG="mtav-test-1.0.0"

# ============================================================================
# Test MTAV Wrapper Script
# ============================================================================

log_info "Testing MTAV wrapper script..."

# Test 1: MTAV script exists and is executable
log_info "Test 1: MTAV script accessibility"
assert_file_exists "./mtav" "MTAV script should exist"

# Check if executable
if [ -x "./mtav" ]; then
    log_success "PASS: MTAV script is executable"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_error "FAIL: MTAV script is not executable"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi
TESTS_RUN=$((TESTS_RUN + 1))

# Test 2: MTAV help/usage
log_info "Test 2: MTAV help and usage information"
run_command_capture output exit_code ./mtav
assert_contains "$output" "MTAV Development Environment" "Should display MTAV help information"
assert_contains "$output" "build" "Should mention build command"

# Test 3: MTAV build command
log_info "Test 3: MTAV build command functionality"
run_command_capture output exit_code ./mtav build php $TEST_TAG --no-push
assert_exit_code 0 $exit_code "MTAV build should succeed"
assert_contains "$output" "Building php image" "Should delegate to build script"
assert_contains "$output" "Successfully built" "Should report successful build"

# Verify image was created
assert_image_exists "ghcr.io/devvir/mtav-php:${TEST_TAG}" "MTAV build should create image"

# Test 4: MTAV command delegation
log_info "Test 4: Command delegation to appropriate scripts"

# Test all build services
for service in assets nginx mysql migrations; do
    log_info "Testing MTAV build for $service..."
    run_command_capture output exit_code ./mtav build $service $TEST_TAG --no-push
    assert_exit_code 0 $exit_code "MTAV build $service should succeed"
    assert_image_exists "ghcr.io/devvir/mtav-${service}:${TEST_TAG}" "MTAV should create $service image"
done

# Test 5: MTAV parameter passing
log_info "Test 5: Parameter passing to underlying scripts"

# Test --no-push flag passing
run_command_capture output exit_code ./mtav build php "${TEST_TAG}-nopush" --no-push
assert_exit_code 0 $exit_code "MTAV should pass --no-push flag correctly"
assert_contains "$output" "Skipping push" "Should pass --no-push flag to build script"

# Test parameter order flexibility
run_command_capture output exit_code ./mtav build --no-push assets "${TEST_TAG}-order"
assert_exit_code 0 $exit_code "MTAV should handle flag order flexibility"

# ============================================================================
# Test MTAV Error Handling
# ============================================================================

log_info "Testing MTAV error handling..."

# Test 6: Invalid command handling
log_info "Test 6: Invalid command handling"
run_command_capture output exit_code ./mtav invalid-command 2>&1 || true
# Should handle invalid commands gracefully (exact behavior depends on implementation)
if [ $exit_code -ne 0 ]; then
    log_success "PASS: MTAV correctly handles invalid commands with non-zero exit"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_warning "WARNING: MTAV allows invalid commands (may be intentional)"
fi
TESTS_RUN=$((TESTS_RUN + 1))

# Test 7: Invalid build parameters
log_info "Test 7: Invalid build parameters"
run_command_capture output exit_code ./mtav build invalid-service $TEST_TAG 2>&1 || true
assert_exit_code 1 $exit_code "MTAV should fail with invalid service"
assert_contains "$output" "Invalid service" "Should show appropriate error message"

# ============================================================================
# Test MTAV Integration
# ============================================================================

log_info "Testing MTAV integration with Docker system..."

# Test 8: End-to-end build workflow
log_info "Test 8: End-to-end build workflow"

# Build a complete set of services
log_info "Building complete service set..."
services=(php assets nginx mysql migrations)
for service in "${services[@]}"; do
    run_command_capture output exit_code ./mtav build $service "${TEST_TAG}-complete" --no-push
    assert_exit_code 0 $exit_code "Complete workflow: $service build should succeed"
done

# Verify all images exist
for service in "${services[@]}"; do
    assert_image_exists "ghcr.io/devvir/mtav-${service}:${TEST_TAG}-complete" "Complete workflow: $service image should exist"
done

# Test 9: MTAV with concurrent builds (if supported)
log_info "Test 9: Build consistency"
# Build same service twice to ensure consistency
run_command_capture output1 exit_code1 ./mtav build php "${TEST_TAG}-consistency1" --no-push
run_command_capture output2 exit_code2 ./mtav build php "${TEST_TAG}-consistency2" --no-push

assert_exit_code 0 $exit_code1 "First consistency build should succeed"
assert_exit_code 0 $exit_code2 "Second consistency build should succeed"

# Both images should be created
assert_image_exists "ghcr.io/devvir/mtav-php:${TEST_TAG}-consistency1" "First consistency image should exist"
assert_image_exists "ghcr.io/devvir/mtav-php:${TEST_TAG}-consistency2" "Second consistency image should exist"

# ============================================================================
# Test MTAV Configuration and Environment
# ============================================================================

log_info "Testing MTAV configuration handling..."

# Test 10: Environment handling
log_info "Test 10: Environment and configuration handling"

# Test that MTAV works from different directories
original_dir=$(pwd)
mkdir -p /tmp/mtav-test-dir
cd /tmp/mtav-test-dir

# Should fail when not in MTAV directory
run_command_capture output exit_code "$original_dir/mtav" build php test-dir-fail --no-push 2>&1 || true
if [ $exit_code -ne 0 ]; then
    log_success "PASS: MTAV correctly requires being in project directory"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_warning "WARNING: MTAV works from any directory (may be intentional)"
fi
TESTS_RUN=$((TESTS_RUN + 1))

# Return to original directory
cd "$original_dir"
rm -rf /tmp/mtav-test-dir

# ============================================================================
# Cleanup
# ============================================================================

log_info "Cleaning up test images..."
cleanup_test_images "mtav.*:${TEST_TAG}"
cleanup_test_images "mtav.*:${TEST_TAG}-.*"

finish_test_suite "MTAV Command Interface"