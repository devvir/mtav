#!/bin/bash

# Common test utilities and functions

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Logging functions
log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

# Test assertion functions
assert_equals() {
    local expected="$1"
    local actual="$2"
    local message="$3"

    TESTS_RUN=$((TESTS_RUN + 1))

    if [ "$expected" = "$actual" ]; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  Expected: '$expected'"
        log_error "  Actual:   '$actual'"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

assert_contains() {
    local haystack="$1"
    local needle="$2"
    local message="$3"

    TESTS_RUN=$((TESTS_RUN + 1))

    if [[ "$haystack" == *"$needle"* ]]; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  Expected to contain: '$needle'"
        log_error "  In: '$haystack'"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

assert_not_contains() {
    local haystack="$1"
    local needle="$2"
    local message="$3"

    TESTS_RUN=$((TESTS_RUN + 1))

    if [[ "$haystack" != *"$needle"* ]]; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  Expected NOT to contain: '$needle'"
        log_error "  In: '$haystack'"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

assert_file_exists() {
    local file="$1"
    local message="$2"

    TESTS_RUN=$((TESTS_RUN + 1))

    if [ -f "$file" ]; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  File does not exist: '$file'"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

assert_exit_code() {
    local expected_code="$1"
    local actual_code="$2"
    local message="$3"

    TESTS_RUN=$((TESTS_RUN + 1))

    if [ "$expected_code" -eq "$actual_code" ]; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  Expected exit code: $expected_code"
        log_error "  Actual exit code:   $actual_code"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

assert_image_exists() {
    local image="$1"
    local message="$2"

    TESTS_RUN=$((TESTS_RUN + 1))

    if docker image inspect "$image" >/dev/null 2>&1; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  Image does not exist: '$image'"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

assert_container_running() {
    local container="$1"
    local message="$2"

    TESTS_RUN=$((TESTS_RUN + 1))

    if docker container inspect "$container" >/dev/null 2>&1 && \
       [ "$(docker container inspect "$container" --format='{{.State.Status}}')" = "running" ]; then
        log_success "PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
        return 0
    else
        log_error "FAIL: $message"
        log_error "  Container not running: '$container'"
        TESTS_FAILED=$((TESTS_FAILED + 1))
        return 1
    fi
}

# Test suite functions
start_test_suite() {
    local suite_name="$1"
    echo ""
    log_info "🧪 Starting test suite: $suite_name"
    echo "=================================================="
}

finish_test_suite() {
    local suite_name="$1"
    echo ""
    echo "=================================================="
    log_info "📊 Test suite '$suite_name' completed"
    log_info "Tests run: $TESTS_RUN"
    log_success "Passed: $TESTS_PASSED"
    if [ $TESTS_FAILED -gt 0 ]; then
        log_error "Failed: $TESTS_FAILED"
        echo ""
        log_error "💥 Test suite FAILED"
        return 1
    else
        echo ""
        log_success "🎉 All tests PASSED"
        return 0
    fi
}

# Cleanup functions
cleanup_test_images() {
    local pattern="$1"
    log_info "Cleaning up test images matching: $pattern"
    docker images --format "table {{.Repository}}:{{.Tag}}" | grep -E "$pattern" | xargs -r docker rmi -f >/dev/null 2>&1 || true
}

cleanup_test_containers() {
    local pattern="$1"
    log_info "Cleaning up test containers matching: $pattern"
    docker ps -a --format "table {{.Names}}" | grep -E "$pattern" | xargs -r docker rm -f >/dev/null 2>&1 || true
}

# Utility functions
run_command_capture() {
    local output_var="$1"
    local exit_code_var="$2"
    shift 2

    local temp_output
    set +e  # Temporarily disable exit on error
    temp_output=$("$@" 2>&1)
    local temp_exit_code=$?
    set -e  # Re-enable exit on error

    eval "$output_var=\"\$temp_output\""
    eval "$exit_code_var=\$temp_exit_code"
}

wait_for_condition() {
    local condition_cmd="$1"
    local timeout="${2:-30}"
    local interval="${3:-2}"

    local elapsed=0
    while [ $elapsed -lt $timeout ]; do
        if eval "$condition_cmd" >/dev/null 2>&1; then
            return 0
        fi
        sleep $interval
        elapsed=$((elapsed + interval))
    done
    return 1
}