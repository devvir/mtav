#!/bin/bash

# Run tests: PHP (Pest), Vue (Vitest), or E2E (Playwright)
export COMPOSE_PROJECT_NAME=testing

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
    echo "  e2e  - Run E2E tests only (Playwright)"
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

# Run PHP-based tests (Pest)
run_php_tests() {
    local test_args=("$@")

    echo -e "${BLUE}Running tests...${NC}"
    echo ""

    if docker_compose exec $DOCKER_TTY_FLAG -e DB_HOST=mysql_test -e DB_USERNAME=root -e DB_PASSWORD=root php php artisan test "${test_args[@]}"; then
        return 0
    else
        return 1
    fi
}

cleanup_environment() {
    echo ""
    echo -e "${BLUE}Stopping testing environment...${NC}"
    docker_compose --profile testing down || true
}

cleanup_built_assets() {
    echo ""
    # Remove the built assets so the host and containers are not polluted
    echo -e "${BLUE}Removing built assets (public/build) from php container...${NC}"
    docker_exec php bash -lc 'rm -rf /var/www/html/public/build || true'
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

# Step 2: Start testing environment
echo ""
echo -e "${BLUE}Starting testing environment...${NC}"
"$(dirname "$0")/up.sh" testing --detach --quiet-pull

echo ""
echo -e "${BLUE}Installing PHP dependencies...${NC}"
if ! docker_exec php composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader; then
    echo ""
    echo -e "${RED}❌ composer install failed${NC}";
    cleanup_environment
    exit 1
fi

# Step 3: Run PHP tests (if requested)
if [ "$RUN_PHP" = true ]; then
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}📦 PHP Tests (Pest)${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if run_php_tests "${TEST_ARGS[@]}"; then
        echo ""
        echo -e "${GREEN}✅ PHP tests passed${NC}"
        PHP_SUCCESS=true
    else
        echo ""
        echo -e "${RED}❌ PHP tests failed${NC}"
        PHP_SUCCESS=false
    fi
fi

# Step 4: Run Vue tests (if requested)
if [ "$RUN_VUE" = true ]; then
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}🎨 Vue Tests (Vitest)${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if docker_exec assets npm run test -- --run "${TEST_ARGS[@]}"; then
        echo ""
        echo -e "${GREEN}✅ Vue tests passed${NC}"
        VUE_SUCCESS=true
    else
        echo ""
        echo -e "${RED}❌ Vue tests failed${NC}"
        VUE_SUCCESS=false
    fi
fi

# Step 5: Run Pest-E2E tests (if requested)
if [ "$RUN_E2E" = true ]; then
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}🌐 E2E Tests (Playwright)${NC}"
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    echo ""
    echo -e "${BLUE}Installing Node dependencies...${NC}"
    if ! docker_exec assets npm install --no-save; then
        echo ""
        echo -e "${RED}❌ npm install failed${NC}";
        cleanup_built_assets
        cleanup_environment
        exit 1
    fi

    echo ""
    echo -e "${BLUE}Building assets...${NC}"
    if ! docker_exec assets npm run build; then
        echo ""
        echo -e "${RED}❌ npm run build failed${NC}";
        cleanup_built_assets
        cleanup_environment
        exit 1
    fi

    echo ""
    echo -e "${BLUE}Removing public/hot to prevent Vite dev client injection...${NC}"
    docker_exec assets sh -lc 'rm -f /var/www/html/public/hot || true'

    if run_php_tests --testsuite Browser "${TEST_ARGS[@]}"; then
        echo ""
        echo -e "${GREEN}✅ E2E tests passed${NC}"
        E2E_SUCCESS=true
    else
        echo ""
        echo -e "${RED}❌ E2E tests failed${NC}"
        E2E_SUCCESS=false
    fi

    cleanup_built_assets
fi

cleanup_environment

# Step 6: Summary of results
if [ "$PHP_SUCCESS" = true ] && [ "$VUE_SUCCESS" = true ] && [ "$E2E_SUCCESS" = true ]; then
    echo ""
    echo -e "${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}💥 Some tests failed${NC}"
    exit 1
fi