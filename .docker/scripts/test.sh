#!/bin/bash

# Run all tests: NPM (frontend) and PHP (backend)
source "$(dirname "$0")/compose.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse arguments
RUN_ONCE=false
RUN_VITEST=true
RUN_PEST=true
PEST_ARGS=()

while [[ $# -gt 0 ]]; do
    case $1 in
        --once)
            RUN_ONCE=true
            shift
            ;;
        --vitest)
            RUN_PEST=false
            shift
            ;;
        --pest)
            RUN_VITEST=false
            shift
            ;;
        *)
            # Collect remaining args for Pest
            PEST_ARGS+=("$1")
            shift
            ;;
    esac
done

echo -e "${BLUE}🧪 Running test suite...${NC}"
if [ "$RUN_ONCE" = true ]; then
    echo -e "${YELLOW}(Running once - tests will exit after completion)${NC}"
else
    echo -e "${YELLOW}(Running in watch mode - tests will continue watching for changes)${NC}"
fi
echo ""

# Step 1: Ensure containers are up
echo -e "${YELLOW}📋 Step 1: Ensuring containers are running...${NC}"
docker_compose up --detach --quiet-pull
# Also start the test database (uses profile 'test')
docker_compose --profile test up mysql_test --detach --quiet-pull 2>/dev/null

# Wait for test database to be healthy
echo -e "${YELLOW}⏳ Waiting for test database to be ready...${NC}"
timeout 30 bash -c 'until docker inspect dev-mysql_test-1 --format="{{.State.Health.Status}}" 2>/dev/null | grep -q "healthy"; do sleep 0.5; done' || {
    echo -e "${RED}❌ Test database failed to become healthy${NC}"
    exit 1
}
echo -e "${GREEN}✅ Containers are ready${NC}"
echo ""

# Step 2: Run NPM tests (frontend)
if [ "$RUN_VITEST" = true ]; then
    echo -e "${YELLOW}📋 Step 2: Running NPM tests (frontend)...${NC}"

    if [ "$RUN_ONCE" = true ]; then
        # Run once mode: run once and exit
        if docker_exec assets npm run test -- --run; then
            echo -e "${GREEN}✅ NPM tests completed${NC}"
            NPM_SUCCESS=true
        else
            echo -e "${RED}❌ NPM tests failed${NC}"
            NPM_SUCCESS=false
        fi
    else
        # Interactive mode: run in watch mode
        echo -e "${BLUE}ℹ️  NPM tests will run in watch mode. Press Ctrl+C to continue to PHP tests.${NC}"
        if docker_exec assets npm run test; then
            echo -e "${GREEN}✅ NPM tests completed${NC}"
            NPM_SUCCESS=true
        else
            echo -e "${RED}❌ NPM tests failed${NC}"
            NPM_SUCCESS=false
        fi
    fi
    echo ""
else
    NPM_SUCCESS=true  # Skip NPM tests
fi

# Step 3: Run PHP tests (backend)
if [ "$RUN_PEST" = true ]; then
    echo -e "${YELLOW}📋 Step 3: Running PHP tests (backend)...${NC}"
    if docker_exec php php artisan test "${PEST_ARGS[@]}"; then
        echo -e "${GREEN}✅ PHP tests completed${NC}"
        PHP_SUCCESS=true
    else
        echo -e "${RED}❌ PHP tests failed${NC}"
        PHP_SUCCESS=false
    fi
    echo ""
else
    PHP_SUCCESS=true  # Skip PHP tests
fi

# Summary
echo -e "${BLUE}📊 Test Summary:${NC}"
if [ "$NPM_SUCCESS" = true ]; then
    echo -e "  ${GREEN}✅ Frontend (NPM): PASSED${NC}"
else
    echo -e "  ${RED}❌ Frontend (NPM): FAILED${NC}"
fi

if [ "$PHP_SUCCESS" = true ]; then
    echo -e "  ${GREEN}✅ Backend (PHP): PASSED${NC}"
else
    echo -e "  ${RED}❌ Backend (PHP): FAILED${NC}"
fi

echo ""

# Exit with appropriate code (only in run-once mode)
if [ "$RUN_ONCE" = true ]; then
    if [ "$NPM_SUCCESS" = true ] && [ "$PHP_SUCCESS" = true ]; then
        echo -e "${GREEN}🎉 All tests passed!${NC}"
        exit 0
    else
        echo -e "${RED}💥 Some tests failed${NC}"
        exit 1
    fi
else
    echo -e "${BLUE}ℹ️  Test summary complete. Both test suites are running in watch mode.${NC}"
    echo -e "${YELLOW}Use Ctrl+C to exit when ready.${NC}"
fi