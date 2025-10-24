#!/bin/bash

# Test script for Docker image building functionality

set -e
cd "$(dirname "$0")/../.."

# Source common test utilities
source .docker/test/common.sh

start_test_suite "Docker Image Building"

# Test variables
TEST_TAG="build-test-1.0.0"
REGISTRY="ghcr.io/devvir"

# ============================================================================
# Test Docker Build System
# ============================================================================

log_info "Testing Docker build system..."

# Test 1: Build environment structure
log_info "Test 1: Build environment file structure"
assert_file_exists ".docker/build/compose.yml" "Build compose file should exist"
assert_file_exists ".docker/build/.env.prod" "Production env file should exist"

# Test service directories
for service in php assets nginx mysql migrations; do
    assert_file_exists ".docker/build/${service}/Dockerfile" "$service Dockerfile should exist"
done

# Test 2: PHP Image Build
log_info "Test 2: PHP image build process"
run_command_capture output exit_code ./mtav build php $TEST_TAG --no-push
assert_exit_code 0 $exit_code "PHP build should succeed"
assert_image_exists "${REGISTRY}/mtav-php:${TEST_TAG}" "PHP image should be created"

# Verify PHP image contents
log_info "Verifying PHP image contents..."
run_command_capture php_info exit_code docker run --rm "${REGISTRY}/mtav-php:${TEST_TAG}" php --version
assert_exit_code 0 $exit_code "PHP should be executable in image"
assert_contains "$php_info" "PHP 8." "Should contain PHP 8.x"

# Test 3: Assets Image Build
log_info "Test 3: Assets image build process"
run_command_capture output exit_code ./mtav build assets $TEST_TAG --no-push
assert_exit_code 0 $exit_code "Assets build should succeed"
assert_image_exists "${REGISTRY}/mtav-assets:${TEST_TAG}" "Assets image should be created"

# Verify assets image
log_info "Verifying assets image contents..."
run_command_capture nginx_info exit_code docker run --rm "${REGISTRY}/mtav-assets:${TEST_TAG}" nginx -v
assert_exit_code 0 $exit_code "Nginx should be executable in assets image"

# Test 4: Nginx Image Build
log_info "Test 4: Nginx image build process"
run_command_capture output exit_code ./mtav build nginx $TEST_TAG --no-push
assert_exit_code 0 $exit_code "Nginx build should succeed"
assert_image_exists "${REGISTRY}/mtav-nginx:${TEST_TAG}" "Nginx image should be created"

# Test 5: MySQL Image Build
log_info "Test 5: MySQL image build process"
run_command_capture output exit_code ./mtav build mysql $TEST_TAG --no-push
assert_exit_code 0 $exit_code "MySQL build should succeed"
assert_image_exists "${REGISTRY}/mtav-mysql:${TEST_TAG}" "MySQL image should be created"

# Test 6: Migrations Image Build
log_info "Test 6: Migrations image build process"
run_command_capture output exit_code ./mtav build migrations $TEST_TAG --no-push
assert_exit_code 0 $exit_code "Migrations build should succeed"
assert_image_exists "${REGISTRY}/mtav-migrations:${TEST_TAG}" "Migrations image should be created"

# Verify migrations image
log_info "Verifying migrations image contents..."
run_command_capture migrate_help exit_code docker run --rm "${REGISTRY}/mtav-migrations:${TEST_TAG}" php artisan --version
assert_exit_code 0 $exit_code "Artisan should be executable in migrations image"

# ============================================================================
# Test Image Tagging System
# ============================================================================

log_info "Testing image tagging system..."

# Test 7: Specific version tag creation only
log_info "Test 7: Only specific version tags are created (no latest tags)"
for service in php assets nginx mysql migrations; do
    assert_image_exists "${REGISTRY}/mtav-${service}:${TEST_TAG}" "$service version tag should exist"

    # Verify latest tag does not exist (our new strategy)
    if docker inspect "${REGISTRY}/mtav-${service}:latest" >/dev/null 2>&1; then
        assert_fail "$service latest tag should not exist - we only create specific version tags"
    fi
done

# ============================================================================
# Test Build Dependencies and Context
# ============================================================================

log_info "Testing build context and dependencies..."

# Test 8: Build context includes necessary files
log_info "Test 8: Build context verification"

# Create a test container to inspect build artifacts
log_info "Checking PHP build artifacts..."
run_command_capture ls_output exit_code docker run --rm "${REGISTRY}/mtav-php:${TEST_TAG}" ls -la /var/www
assert_exit_code 0 $exit_code "Should be able to list PHP container contents"
assert_contains "$ls_output" "artisan" "PHP container should contain artisan"
assert_contains "$ls_output" "vendor" "PHP container should contain vendor directory"

# Test 9: Multi-stage build verification
log_info "Test 9: Multi-stage build verification for assets"
# Check that node_modules is not in final assets image (multi-stage should exclude it)
run_command_capture assets_contents exit_code docker run --rm "${REGISTRY}/mtav-assets:${TEST_TAG}" ls -la /usr/share/nginx/html || true
# We expect this to not contain node_modules (should be stripped by multi-stage build)
assert_not_contains "$assets_contents" "node_modules" "Assets final image should not contain node_modules"

# ============================================================================
# Test Build Performance and Caching
# ============================================================================

log_info "Testing build performance and caching..."

# Test 10: Rebuild should use cache
log_info "Test 10: Rebuild should use Docker cache"
start_time=$(date +%s)
run_command_capture output exit_code ./mtav build php "${TEST_TAG}-rebuild" --no-push
end_time=$(date +%s)
build_time=$((end_time - start_time))

assert_exit_code 0 $exit_code "Rebuild should succeed"
assert_contains "$output" "CACHED" "Rebuild should use cached layers"
log_info "Rebuild completed in ${build_time} seconds (should be fast due to caching)"

# ============================================================================
# Cleanup
# ============================================================================

log_info "Cleaning up test images..."
cleanup_test_images "mtav.*:${TEST_TAG}"
cleanup_test_images "mtav.*:${TEST_TAG}-rebuild"

finish_test_suite "Docker Image Building"