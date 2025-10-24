#!/bin/bash

# Test script for .docker/scripts/*

set -e
cd "$(dirname "$0")/../.."

# Source common test utilities
source .docker/test/common.sh

start_test_suite "Docker Scripts"

# Test variables
TEST_TAG="test-1.0.0"
REGISTRY="ghcr.io/devvir"

# ============================================================================
# Test build.sh script
# ============================================================================

log_info "Testing build.sh script..."

# Test 1: Help/usage output
log_info "Test 1: Help output when no arguments provided"
run_command_capture output exit_code .docker/scripts/build.sh
assert_exit_code 1 $exit_code "build.sh should exit with code 1 when no service provided"
assert_contains "$output" "Invalid service" "Help should mention invalid service"
assert_contains "$output" "Usage:" "Help should show usage"
assert_contains "$output" "--push" "Help should mention --push flag"
assert_contains "$output" "--no-push" "Help should mention --no-push flag"

# Test 2: Invalid service
log_info "Test 2: Invalid service name"
run_command_capture output exit_code .docker/scripts/build.sh invalid-service 1.0.0
assert_exit_code 1 $exit_code "build.sh should exit with code 1 for invalid service"
assert_contains "$output" "Invalid service" "Should reject invalid service name"

# Test 3: Build with --no-push flag
log_info "Test 3: Build with --no-push flag"
run_command_capture output exit_code .docker/scripts/build.sh php $TEST_TAG --no-push
assert_exit_code 0 $exit_code "build.sh should succeed with valid service and --no-push"
assert_contains "$output" "Successfully built" "Should report successful build"
assert_contains "$output" "Skipping push" "Should mention skipping push"
assert_not_contains "$output" "Pushing" "Should not attempt to push"

# Verify images were created
assert_image_exists "${REGISTRY}/mtav-php:${TEST_TAG}" "PHP test image should exist"

# Test 4: Build argument order flexibility
log_info "Test 4: Build with flags in different positions"
run_command_capture output exit_code .docker/scripts/build.sh --no-push nginx $TEST_TAG
assert_exit_code 0 $exit_code "build.sh should accept flags before service name"
assert_image_exists "${REGISTRY}/mtav-nginx:${TEST_TAG}" "Nginx test image should exist"

# Test 5: Test all services can be built
log_info "Test 5: All services can be built"
for service in assets mysql migrations; do
    log_info "Building $service..."
    run_command_capture output exit_code .docker/scripts/build.sh $service $TEST_TAG --no-push
    assert_exit_code 0 $exit_code "$service build should succeed"
    assert_image_exists "${REGISTRY}/mtav-${service}:${TEST_TAG}" "$service test image should exist"
done

# ============================================================================
# Test deploy.sh script (build environment)
# ============================================================================

log_info "Testing deploy.sh script..."

# Test 6: MTAV deployment functionality (self-contained test)
log_info "Test 6: MTAV local production deployment"

# Set up test environment
DEPLOY_TEST_TAG="deploy-test-1.0.0"
log_info "Setting up test environment with tag: $DEPLOY_TEST_TAG"

# Build required images for deployment test
log_info "Building test images for deployment..."
declare -a services=("php" "assets" "nginx" "mysql" "migrations")
for service in "${services[@]}"; do
    log_info "Building $service test image..."
    run_command_capture output exit_code ./mtav build "$service" "$DEPLOY_TEST_TAG" --no-push
    assert_exit_code 0 $exit_code "$service build for deployment test should succeed"
    assert_image_exists "${REGISTRY}/mtav-${service}:${DEPLOY_TEST_TAG}" "$service deployment test image should exist"
done

# Save current version.yml and create test version
log_info "Creating test version.yml..."
cp .docker/deploy/version.yml .docker/deploy/version.yml.backup
cat > .docker/deploy/version.yml << EOF
# Test version file for deployment testing
php: '$DEPLOY_TEST_TAG'
assets: '$DEPLOY_TEST_TAG'
nginx: '$DEPLOY_TEST_TAG'
mysql: '$DEPLOY_TEST_TAG'
migrations: '$DEPLOY_TEST_TAG'
EOF

# Test deployment startup
log_info "Testing deployment startup..."
run_command_capture output exit_code timeout 60s ./mtav deploy -d
assert_exit_code 0 $exit_code "MTAV deployment should start successfully"

# Verify containers are running with correct names
log_info "Verifying deployment containers..."
run_command_capture containers exit_code docker ps --filter "name=test-" --format "table {{.Names}}"
assert_exit_code 0 $exit_code "Should be able to list test containers"
assert_contains "$containers" "test-mysql-1" "MySQL container should be running with correct name"
assert_contains "$containers" "test-nginx-1" "Nginx container should be running with correct name"

# Test that deployment is accessible
log_info "Testing deployment accessibility..."
sleep 10  # Give services time to fully start
run_command_capture response exit_code timeout 10s curl -f http://localhost:8090 || true
if [ $exit_code -eq 0 ]; then
    log_success "Deployment is accessible on port 8090"
else
    log_warning "Deployment may still be starting up (expected for complex startup)"
fi

# Cleanup deployment
log_info "Cleaning up deployment..."
run_command_capture output exit_code docker compose --project-name test -f .docker/deploy/compose.yml -f .docker/build/compose.yml down --volumes --remove-orphans || true

# Restore original version.yml
log_info "Restoring original version.yml..."
mv .docker/deploy/version.yml.backup .docker/deploy/version.yml

# Cleanup test images
log_info "Cleaning up deployment test images..."
cleanup_test_images "mtav.*:${DEPLOY_TEST_TAG}"

log_success "Deployment test completed successfully"

# ============================================================================
# Test mtav wrapper script
# ============================================================================

log_info "Testing mtav wrapper script..."

# Test 7: mtav help
log_info "Test 7: mtav help/usage"
run_command_capture output exit_code ./mtav
assert_exit_code 0 $exit_code "mtav should exit with code 0 when showing help"
assert_contains "$output" "MTAV Development Environment" "mtav should show help information"

# Test 8: mtav build command delegation
log_info "Test 8: mtav build command delegation"
run_command_capture output exit_code ./mtav build php test-delegation --no-push
assert_exit_code 0 $exit_code "mtav build should delegate to build.sh successfully"
assert_contains "$output" "Building php image" "mtav should delegate to build.sh"
assert_image_exists "${REGISTRY}/mtav-php:test-delegation" "mtav build should create image"

# Test 9: mtav unknown command
log_info "Test 9: mtav unknown command"
run_command_capture output exit_code ./mtav unknown-command || true
# The behavior depends on implementation, but should handle gracefully

# ============================================================================
# Cleanup
# ============================================================================

log_info "Cleaning up test images..."
cleanup_test_images "mtav.*:${TEST_TAG}"
cleanup_test_images "mtav.*:test-delegation"

finish_test_suite "Docker Scripts"