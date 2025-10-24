#!/bin/bash

# Test script for development environment functionality

set -e
cd "$(dirname "$0")/../.."

# Source common test utilities
source .docker/test/common.sh

start_test_suite "Development Environment"

# Test variables
DEV_COMPOSE_FILE=".docker/compose.yml"

# ============================================================================
# Test Development Environment Structure
# ============================================================================

log_info "Testing development environment structure..."

# Test 1: Development files exist
log_info "Test 1: Development environment file structure"
assert_file_exists "$DEV_COMPOSE_FILE" "Development compose file should exist"
assert_file_exists "package.json" "Package.json should exist"
assert_file_exists "composer.json" "Composer.json should exist"

# Test development docker files
assert_file_exists ".docker/php/Dockerfile" "Development PHP Dockerfile should exist"
assert_file_exists ".docker/build/nginx/nginx.conf" "Development nginx config should exist"
assert_file_exists ".docker/assets/Dockerfile" "Development Vite Dockerfile should exist"

# ============================================================================
# Test Development Compose Configuration
# ============================================================================

log_info "Testing development compose configuration..."

# Test 2: Compose file validity
log_info "Test 2: Development compose file validation"
run_command_capture compose_config exit_code docker compose -f "$DEV_COMPOSE_FILE" config
assert_exit_code 0 $exit_code "Development compose file should be valid"

# Test 3: Development services definition
log_info "Test 3: Development services are properly defined"
dev_services=$(docker compose -f "$DEV_COMPOSE_FILE" config --services)

# Check expected development services
expected_services="php mysql nginx assets mailhog"
for service in $expected_services; do
    if echo "$dev_services" | grep -q "^${service}$"; then
        log_success "PASS: Development service '$service' is defined"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        log_error "FAIL: Development service '$service' is missing"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
    TESTS_RUN=$((TESTS_RUN + 1))
done

# ============================================================================
# Test Development Images Build
# ============================================================================

log_info "Testing development image building..."

# Test 4: Development images can be built
log_info "Test 4: Development images build successfully"

# Build development images
run_command_capture build_output exit_code docker compose -f "$DEV_COMPOSE_FILE" build
assert_exit_code 0 $exit_code "Development images should build successfully"

# Verify images were created
# The dev compose file sets project name to "docker"
# Only php and assets services build custom images; nginx uses base nginx image
dev_project="docker"
for service in php assets; do
    image_name="${dev_project}-${service}"
    assert_image_exists "$image_name" "Development $service image should exist"
done

# ============================================================================
# Test Development Environment Startup
# ============================================================================

log_info "Testing development environment startup..."

# Test 5: Development environment starts
log_info "Test 5: Development environment startup"

# Start development environment
log_info "Starting development environment..."
run_command_capture start_output exit_code docker compose -f "$DEV_COMPOSE_FILE" up -d
assert_exit_code 0 $exit_code "Development environment should start successfully"

# Wait for services to be ready
log_info "Waiting for services to be ready..."
sleep 10

# Test 6: Development services are running
log_info "Test 6: Development services health check"

# Check each service is running
for service in php mysql nginx assets mailhog; do
    container_name="${dev_project}-${service}-1"
    assert_container_running "$container_name" "Development $service container should be running"
done

# ============================================================================
# Test Development Service Connectivity
# ============================================================================

log_info "Testing development service connectivity..."

# Test 7: Service connectivity and functionality
log_info "Test 7: Development services are functional"

# Test PHP-FPM is working
log_info "Testing PHP-FPM connectivity..."
run_command_capture php_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T php php --version
assert_exit_code 0 $exit_code "PHP service should be functional"

# Test MySQL is accessible
log_info "Testing MySQL connectivity..."
run_command_capture mysql_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T mysql mariadb --version
assert_exit_code 0 $exit_code "MySQL service should be functional"

# Test web server accessibility (if ports are exposed)
log_info "Testing web server accessibility..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 | grep -q "200\|404\|500"; then
    log_success "PASS: Web server is accessible on port 8000"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_warning "WARNING: Web server not accessible (may be expected in CI)"
fi
TESTS_RUN=$((TESTS_RUN + 1))

# Test Vite dev server (if accessible)
log_info "Testing Vite dev server..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost:5173 | grep -q "200\|404"; then
    log_success "PASS: Vite dev server is accessible on port 5173"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_warning "WARNING: Vite dev server not accessible (may be expected in CI)"
fi
TESTS_RUN=$((TESTS_RUN + 1))

# ============================================================================
# Test Development Tools
# ============================================================================

log_info "Testing development tools and commands..."

# Test 8: Artisan commands work
log_info "Test 8: Laravel Artisan functionality"
run_command_capture artisan_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T php php artisan --version
assert_exit_code 0 $exit_code "Artisan should be functional"
assert_contains "$artisan_test" "Laravel Framework" "Should show Laravel version"

# Test 9: Composer is available
log_info "Test 9: Composer availability"
run_command_capture composer_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T php composer --version
assert_exit_code 0 $exit_code "Composer should be available"

# Test 10: Node.js and npm (in assets container)
log_info "Test 10: Node.js and npm availability"
run_command_capture node_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T assets node --version
assert_exit_code 0 $exit_code "Node.js should be available in assets container"

run_command_capture npm_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T assets npm --version
assert_exit_code 0 $exit_code "npm should be available in assets container"

# ============================================================================
# Test Development Volume Mounts
# ============================================================================

log_info "Testing development volume mounts..."

# Test 11: Source code is mounted
log_info "Test 11: Source code volume mounting"

# Create a test file and verify it appears in container
test_file="test-mount-verification.txt"
echo "test content" > "$test_file"

# Check if file appears in PHP container
run_command_capture mount_test exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T php cat "/var/www/$test_file"
assert_exit_code 0 $exit_code "Source code should be mounted in PHP container"
assert_contains "$mount_test" "test content" "Mounted file should have correct content"

# Cleanup test file
rm -f "$test_file"

# Test 12: Database persistence
log_info "Test 12: Database data persistence"

# Create a test database/table (basic smoke test)
run_command_capture db_create exit_code docker compose -f "$DEV_COMPOSE_FILE" exec -T mysql mariadb -u root -proot -e "CREATE DATABASE IF NOT EXISTS test_persistence;"
assert_exit_code 0 $exit_code "Should be able to create test database"

# ============================================================================
# Cleanup Development Environment
# ============================================================================

log_info "Cleaning up development environment..."

# Stop and remove development containers
log_info "Stopping development environment..."
docker compose -f "$DEV_COMPOSE_FILE" down -v >/dev/null 2>&1 || true

# Remove development images
log_info "Cleaning up development images..."
cleanup_test_images "${dev_project}-.*"

finish_test_suite "Development Environment"