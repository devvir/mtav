#!/bin/bash

# Run tests: PHP (Pest), Vue (Vitest), or E2E (Pest Browser testing w/ Playwright)

DOCKER_DIR="$(dirname "$0")/.."
source "$(dirname "$0")/compose.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse test type argument
TEST_TYPE="${1:-all}"

# Validate test type
if [[ ! "$TEST_TYPE" =~ ^(all|php|vue|e2e)$ ]]; then
    echo -e "${RED}❌ Invalid test type: $TEST_TYPE${NC}"
    echo "Usage: test.sh [all|php|vue|e2e] [additional test args]"
    echo ""
    echo "Test types:"
    echo "  all  - Run all tests (PHP + Vue + E2E)"
    echo "  php  - Run PHP tests only (Pest)"
    echo "  vue  - Run Vue tests only (Vitest)"
    echo "  e2e  - Run E2E browser tests (Pest + Playwright)"
    exit 1
fi

# Remove test type from arguments
shift 2>/dev/null || true

# Step 1: Determine what to run
RUN_PHP=false
RUN_VUE=false
RUN_E2E=false

case $TEST_TYPE in
    all)
        RUN_PHP=true
        RUN_VUE=true
        RUN_E2E=true
        ;;
    php)
        RUN_PHP=true
        ;;
    vue)
        RUN_VUE=true
        ;;
    e2e)
        RUN_E2E=true
        ;;
esac

# Collect remaining args
TEST_ARGS=("$@")

# Track results
PHP_SUCCESS=true
VUE_SUCCESS=true
E2E_SUCCESS=true

# Wait for php-e2e container to be healthy (browser tests only)
wait_for_php_e2e() {
    local project_name=$1
    echo -e "${BLUE}Waiting for php-e2e container...${NC}"
    timeout 60 bash -c "until docker inspect ${project_name}-php-e2e-1 --format='{{.State.Health.Status}}' 2>/dev/null | grep -q 'healthy'; do sleep 0.5; done" || {
        echo -e "${RED}❌ php-e2e container failed to become healthy${NC}"
        return 1
    }
    return 0
}

# Run PHP-based tests (Pest)
run_php_tests() {
    local project_name=$1
    local container=$2
    shift 2
    local test_args=("$@")

    echo -e "${BLUE}Running tests...${NC}"
    echo ""

    export COMPOSE_PROJECT_NAME=$project_name
    if docker_compose exec $DOCKER_TTY_FLAG -e DB_HOST=mysql_test -e DB_USERNAME=root -e DB_PASSWORD=root $container php artisan test "${test_args[@]}"; then
        return 0
    else
        return 1
    fi
}

# Cleanup test environment
cleanup_environment() {
    local project_name=$1
    local profile=$2

    # echo ""
    # echo -e "${BLUE}Stopping ${profile} environment...${NC}"
    # export COMPOSE_PROJECT_NAME=$project_name
    # docker_compose --profile $profile down 2>/dev/null
    # echo ""
}

echo -e "${BLUE}🧪 Test Suite${NC}"
echo -e "${BLUE}════════════${NC}"
if [ "$RUN_PHP" = true ]; then
    echo -e "  • PHP (Pest)"
fi
if [ "$RUN_VUE" = true ]; then
    echo -e "  • Vue (Vitest)"
fi
if [ "$RUN_E2E" = true ]; then
    echo -e "  • E2E (Playwright)"
fi
echo ""

# Step 2: Run PHP tests (if requested)
if [ "$RUN_PHP" = true ]; then
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}📦 PHP Tests (Pest)${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    echo -e "${BLUE}Starting testing environment...${NC}"
    "$(dirname "$0")/up.sh" testing --detach --quiet-pull

      if run_php_tests testing php "${TEST_ARGS[@]}"; then
          echo ""
          echo -e "${GREEN}✅ PHP tests passed${NC}"
          PHP_SUCCESS=true
      else
          echo ""
          echo -e "${RED}❌ PHP tests failed${NC}"
          PHP_SUCCESS=false
      fi

    cleanup_environment testing testing
fi

# Step 3: Run Vue tests (if requested)
if [ "$RUN_VUE" = true ]; then
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}🎨 Vue Tests (Vitest)${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    echo -e "${BLUE}Starting testing environment...${NC}"
    "$(dirname "$0")/up.sh" testing --detach --quiet-pull

    echo -e "${BLUE}Running tests...${NC}"
    echo ""

    if docker_exec assets npm run test -- --run "${TEST_ARGS[@]}"; then
        echo ""
        echo -e "${GREEN}✅ Vue tests passed${NC}"
        VUE_SUCCESS=true
    else
        echo ""
        echo -e "${RED}❌ Vue tests failed${NC}"
        VUE_SUCCESS=false
    fi

    echo ""
    echo -e "${BLUE}Stopping testing environment...${NC}"
    export COMPOSE_PROJECT_NAME=testing
    docker_compose --profile testing down 2>/dev/null
    echo ""
fi

# Step 4: Run E2E tests (if requested)
if [ "$RUN_E2E" = true ]; then
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}🌐 E2E Tests (Playwright)${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    echo -e "${BLUE}Starting browser environment...${NC}"
    "$(dirname "$0")/up.sh" browser --detach --quiet-pull

    if ! wait_for_php_e2e browser; then
        E2E_SUCCESS=false
    fi

    if [ "$E2E_SUCCESS" = true ]; then
        if run_php_tests browser php-e2e --testsuite=Browser "${TEST_ARGS[@]}"; then
            echo ""
            echo -e "${GREEN}✅ E2E tests passed${NC}"
            E2E_SUCCESS=true
        else
            echo ""
            echo -e "${RED}❌ E2E tests failed${NC}"
            E2E_SUCCESS=false
        fi
    fi

    cleanup_environment browser browser
fi

# Final Summary
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}📊 Test Summary${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ "$RUN_PHP" = true ]; then
    if [ "$PHP_SUCCESS" = true ]; then
        echo -e "  ${GREEN}✅ PHP (Pest): PASSED${NC}"
    else
        echo -e "  ${RED}❌ PHP (Pest): FAILED${NC}"
    fi
fi

if [ "$RUN_VUE" = true ]; then
    if [ "$VUE_SUCCESS" = true ]; then
        echo -e "  ${GREEN}✅ Vue (Vitest): PASSED${NC}"
    else
        echo -e "  ${RED}❌ Vue (Vitest): FAILED${NC}"
    fi
fi

if [ "$RUN_E2E" = true ]; then
    if [ "$E2E_SUCCESS" = true ]; then
        echo -e "  ${GREEN}✅ E2E (Playwright): PASSED${NC}"
    else
        echo -e "  ${RED}❌ E2E (Playwright): FAILED${NC}"
    fi
fi

echo ""

# Exit with appropriate code
if [ "$PHP_SUCCESS" = true ] && [ "$VUE_SUCCESS" = true ] && [ "$E2E_SUCCESS" = true ]; then
    echo -e "${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}💥 Some tests failed${NC}"
    exit 1
fi