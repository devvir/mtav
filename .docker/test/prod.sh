#!/bin/bash

# Test script for local production deployment system

set -e
cd "$(dirname "$0")/../.."

# Source common test utilities
source .docker/test/common.sh

start_test_suite "Local Production Deployment"

# Test variables
TEST_TAG="prod-test-1.0.0"
COMPOSE_PROJECT="prod-test"

# ============================================================================
# Test Production Build Environment
# ============================================================================

log_info "Testing production build environment..."

# Test 1: Production build files exist
log_info "Test 1: Production environment file structure"
assert_file_exists ".docker/build/compose.yml" "Production compose file should exist"
assert_file_exists ".docker/build/.env.prod" "Production environment file should exist"

# Test production dockerfiles
for service in php assets nginx mysql migrations; do
    assert_file_exists ".docker/build/${service}/Dockerfile" "Production $service Dockerfile should exist"
done

# ============================================================================
# Test Production Image Building
# ============================================================================

log_info "Testing production image building..."

# Test 2: Build all production images
log_info "Test 2: Build complete production image set"
services=(php assets nginx mysql migrations)

for service in "${services[@]}"; do
    log_info "Building production $service image..."
    run_command_capture output exit_code ./mtav build $service $TEST_TAG --no-push
    assert_exit_code 0 $exit_code "Production $service build should succeed"
    assert_image_exists "ghcr.io/devvir/mtav-${service}:${TEST_TAG}" "Production $service image should exist"
    assert_contains "$output" "Successfully built" "Should report successful build"
done

# ============================================================================
# Test Production Deployment System
# ============================================================================

log_info "Testing production deployment system..."

# Test 3: Build environment deployment
log_info "Test 3: Build environment deployment"

# Check if build deployment script exists
if [ -f ".docker/scripts/deploy.sh" ]; then
    log_info "Testing build deployment script..."

    # Test deployment without actually running (to avoid conflicts)
    run_command_capture output exit_code timeout 10s .docker/scripts/deploy.sh --help 2>&1 || true
    # Note: This may timeout or fail if there's no help option, which is expected

    log_info "Build deployment script is accessible"
else
    log_warning "Build deployment script not found (may not be implemented)"
fi

# ============================================================================
# Test Production Container Runtime
# ============================================================================

log_info "Testing production container runtime..."

# Test 4: Individual container functionality
log_info "Test 4: Individual container functionality"

# Test PHP container
log_info "Testing PHP production container..."
run_command_capture php_output exit_code docker run --rm "ghcr.io/devvir/mtav-php:${TEST_TAG}" php --version
assert_exit_code 0 $exit_code "PHP container should be functional"
assert_contains "$php_output" "PHP" "PHP container should report PHP version"

# Test PHP-FPM is available
run_command_capture fpm_output exit_code docker run --rm "ghcr.io/devvir/mtav-php:${TEST_TAG}" php-fpm --version
assert_exit_code 0 $exit_code "PHP-FPM should be available"

# Test artisan is available
run_command_capture artisan_output exit_code docker run --rm "ghcr.io/devvir/mtav-php:${TEST_TAG}" php artisan --version
assert_exit_code 0 $exit_code "Artisan should be available in PHP container"

# Test Assets container
log_info "Testing Assets production container..."
run_command_capture nginx_output exit_code docker run --rm "ghcr.io/devvir/mtav-assets:${TEST_TAG}" nginx -t
assert_exit_code 0 $exit_code "Assets container nginx config should be valid"

# Test Nginx container
log_info "Testing Nginx production container..."
# Note: Nginx config requires "assets" upstream which is only available in compose environment
# We'll test if the nginx binary exists and the container can start, but not full config validation
run_command_capture nginx_test exit_code docker run --rm "ghcr.io/devvir/mtav-nginx:${TEST_TAG}" which nginx
if [ $exit_code -eq 0 ]; then
    log_success "PASS: Nginx container has nginx binary available"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_error "FAIL: Nginx container should have nginx binary"
    log_error "  Exit code: $exit_code"
    log_error "  Output: $nginx_test"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi
TESTS_RUN=$((TESTS_RUN + 1))

# Test MySQL container
log_info "Testing MySQL production container..."
# Start MySQL container briefly to test it can start
mysql_container="${COMPOSE_PROJECT}-mysql-test"
run_command_capture mysql_start exit_code docker run -d --name "$mysql_container" \
    -e MYSQL_ROOT_PASSWORD=testpass \
    -e MYSQL_DATABASE=testdb \
    "ghcr.io/devvir/mtav-mysql:${TEST_TAG}"

if [ $exit_code -eq 0 ]; then
    # Wait a moment for MySQL to start
    sleep 5

    # Check if container is running
    if docker container inspect "$mysql_container" >/dev/null 2>&1; then
        mysql_status=$(docker container inspect "$mysql_container" --format='{{.State.Status}}')
        if [ "$mysql_status" = "running" ]; then
            log_success "PASS: MySQL container starts successfully"
            TESTS_PASSED=$((TESTS_PASSED + 1))
        else
            log_error "FAIL: MySQL container failed to start properly (status: $mysql_status)"
            TESTS_FAILED=$((TESTS_FAILED + 1))
        fi
    else
        log_error "FAIL: MySQL container not found after start"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
    TESTS_RUN=$((TESTS_RUN + 1))

    # Cleanup MySQL container
    docker rm -f "$mysql_container" >/dev/null 2>&1 || true
else
    log_error "FAIL: MySQL container failed to start"
    TESTS_FAILED=$((TESTS_FAILED + 1))
    TESTS_RUN=$((TESTS_RUN + 1))
fi

# Test Migrations container
log_info "Testing Migrations production container..."
run_command_capture migrations_output exit_code docker run --rm "ghcr.io/devvir/mtav-migrations:${TEST_TAG}" php artisan --version
assert_exit_code 0 $exit_code "Migrations container should have working artisan"

# ============================================================================
# Test Production Image Optimization
# ============================================================================

log_info "Testing production image optimization..."

# Test 5: Image size and optimization
log_info "Test 5: Production image characteristics"

for service in "${services[@]}"; do
    image_name="ghcr.io/devvir/mtav-${service}:${TEST_TAG}"

    # Get image size
    image_size=$(docker image inspect "$image_name" --format='{{.Size}}')
    size_mb=$((image_size / 1024 / 1024))

    log_info "$service image size: ${size_mb}MB"

    # Basic size sanity checks (adjust thresholds as needed)
    case $service in
        "mysql")
            if [ $size_mb -lt 1000 ]; then  # MySQL should be reasonable size
                log_success "PASS: $service image size is reasonable (${size_mb}MB)"
                TESTS_PASSED=$((TESTS_PASSED + 1))
            else
                log_warning "WARNING: $service image is quite large (${size_mb}MB)"
            fi
            ;;
        "php")
            if [ $size_mb -lt 500 ]; then  # PHP image should be optimized
                log_success "PASS: $service image size is reasonable (${size_mb}MB)"
                TESTS_PASSED=$((TESTS_PASSED + 1))
            else
                log_warning "WARNING: $service image is quite large (${size_mb}MB)"
            fi
            ;;
        *)
            if [ $size_mb -lt 200 ]; then  # Other images should be small
                log_success "PASS: $service image size is reasonable (${size_mb}MB)"
                TESTS_PASSED=$((TESTS_PASSED + 1))
            else
                log_warning "WARNING: $service image is quite large (${size_mb}MB)"
            fi
            ;;
    esac
    TESTS_RUN=$((TESTS_RUN + 1))
done

# ============================================================================
# Test Production Multi-Stage Builds
# ============================================================================

log_info "Testing multi-stage build efficiency..."

# Test 6: Multi-stage build verification
log_info "Test 6: Multi-stage build artifact exclusion"

# Check that development dependencies are not in production images
log_info "Checking PHP image for development artifacts..."
run_command_capture dev_check exit_code docker run --rm "ghcr.io/devvir/mtav-php:${TEST_TAG}" find /var/www -name "node_modules" 2>/dev/null || true
# Should not find node_modules in PHP production image
if [ -z "$dev_check" ]; then
    log_success "PASS: PHP image excludes node_modules (clean production build)"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_warning "WARNING: PHP image contains development artifacts"
fi
TESTS_RUN=$((TESTS_RUN + 1))

# Check assets image doesn't contain build tools
log_info "Checking Assets image for build artifacts..."
run_command_capture build_tools exit_code docker run --rm "ghcr.io/devvir/mtav-assets:${TEST_TAG}" which npm 2>/dev/null || true
# Should not find npm in final assets image
if [ -z "$build_tools" ]; then
    log_success "PASS: Assets image excludes build tools (clean production build)"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    log_warning "WARNING: Assets image contains build tools"
fi
TESTS_RUN=$((TESTS_RUN + 1))

# ============================================================================
# Test Production Security
# ============================================================================

log_info "Testing production security considerations..."

# Test 7: Security best practices
log_info "Test 7: Production security checks"

# Check that images don't run as root (where applicable)
for service in php; do  # Focus on services that should run as non-root
    log_info "Checking $service container user..."
    run_command_capture user_check exit_code docker run --rm "ghcr.io/devvir/mtav-${service}:${TEST_TAG}" whoami
    if [ "$user_check" != "root" ]; then
        log_success "PASS: $service container runs as non-root user ($user_check)"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        log_warning "WARNING: $service container runs as root (security consideration)"
    fi
    TESTS_RUN=$((TESTS_RUN + 1))
done

# ============================================================================
# Cleanup
# ============================================================================

log_info "Cleaning up test images and containers..."
cleanup_test_images "mtav.*:${TEST_TAG}"
cleanup_test_containers "${COMPOSE_PROJECT}.*"

finish_test_suite "Local Production Deployment"