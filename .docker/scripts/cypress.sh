#!/bin/bash

# Copilot - Pending review

# Run Cypress E2E tests in isolated test environment

DOCKER_DIR="$(dirname "$0")/.."
source "$(dirname "$0")/compose.sh"

# Parse mode (open or run)
MODE="${1:-open}"

if [[ "$MODE" != "open" && "$MODE" != "run" ]]; then
    echo -e "${RED}Invalid mode: $MODE${NC}"
    echo "Usage: mtav cypress [open|run]"
    exit 1
fi

# Use separate Docker Compose project for test environment
TEST_PROJECT="mtav-test"

echo -e "${BLUE}🧪 Starting Cypress E2E test environment...${NC}"
echo ""

# Start test environment (separate from dev)
echo -e "${YELLOW}Starting test containers...${NC}"
DB_HOST=mysql_test DB_DATABASE=mtav_test DB_USERNAME=mtav DB_PASSWORD=password APP_ENV=testing DOCKER_NGINX_PORT=8001 \
    docker compose -f "$DOCKER_DIR/compose.yml" --project-name "$TEST_PROJECT" \
    --profile test \
    up php nginx mysql_test --detach --quiet-pull

# Wait for mysql_test to be healthy
echo -e "${YELLOW}Waiting for test database...${NC}"
timeout 30 bash -c "until docker inspect ${TEST_PROJECT}-mysql_test-1 --format='{{.State.Health.Status}}' 2>/dev/null | grep -q 'healthy'; do sleep 0.5; done" || {
    echo -e "${RED}❌ Test database failed to become healthy${NC}"
    docker compose --project-name "$TEST_PROJECT" down
    exit 1
}

echo -e "${GREEN}✅ Test environment ready${NC}"
echo ""

# Run migrations
echo -e "${YELLOW}Running migrations...${NC}"
docker compose -f "$DOCKER_DIR/compose.yml" --project-name "$TEST_PROJECT" \
    exec -T php php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to run migrations${NC}"
    docker compose --project-name "$TEST_PROJECT" down
    exit 1
fi

echo -e "${GREEN}✅ Migrations completed${NC}"
echo ""

# Load test fixture
echo -e "${YELLOW}Loading test fixture...${NC}"
docker exec -i "${TEST_PROJECT}-mysql_test-1" mariadb -u root -proot mtav_test < "$DOCKER_DIR/../tests/Fixtures/universe.sql"

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to load test fixture${NC}"
    docker compose --project-name "$TEST_PROJECT" down
    exit 1
fi

echo -e "${GREEN}✅ Test fixture loaded${NC}"
echo ""

# Ensure Cypress directories exist with proper permissions
echo -e "${YELLOW}Setting up Cypress directories...${NC}"
docker compose -f "$DOCKER_DIR/compose.yml" --project-name "$TEST_PROJECT" \
    exec -T php bash -c "mkdir -p tests/e2e/{screenshots,videos,downloads} && chmod -R 777 tests/e2e/{screenshots,videos,downloads}"

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to setup Cypress directories${NC}"
    docker compose --project-name "$TEST_PROJECT" down
    exit 1
fi

echo -e "${GREEN}✅ Cypress directories ready${NC}"
echo ""

# Run Cypress (using e2e container)
if [ "$MODE" = "open" ]; then
    echo -e "${BLUE}Opening Cypress GUI...${NC}"
    DB_HOST=mysql_test DB_DATABASE=mtav_test DB_USERNAME=mtav DB_PASSWORD=password APP_ENV=testing DOCKER_NGINX_PORT=8001 \
        docker compose -f "$DOCKER_DIR/compose.yml" --project-name "$TEST_PROJECT" \
        --profile test \
        run --rm e2e npx cypress open
    EXIT_CODE=$?
else
    echo -e "${BLUE}Running Cypress tests in parallel (4 workers)...${NC}"

    # Get all spec files
    SPEC_FILES=($(cd "$DOCKER_DIR/.." && find tests/e2e/Views -name "*.cy.ts" | sort))
    TOTAL_SPECS=${#SPEC_FILES[@]}

    # Number of parallel workers (adjust based on your CPU cores)
    WORKERS=4

    echo -e "${YELLOW}Found ${TOTAL_SPECS} spec files, splitting across ${WORKERS} workers${NC}"

    # Create temp directory for worker logs
    LOG_DIR=$(mktemp -d)

    # Run specs in parallel
    pids=()
    EXIT_CODE=0

    for ((i=0; i<WORKERS; i++)); do
        # Get specs for this worker (round-robin distribution)
        worker_specs=()
        for ((j=i; j<TOTAL_SPECS; j+=WORKERS)); do
            worker_specs+=("${SPEC_FILES[$j]}")
        done

        if [ ${#worker_specs[@]} -gt 0 ]; then
            spec_list=$(IFS=,; echo "${worker_specs[*]}")
            echo -e "${YELLOW}Worker $((i+1)): ${#worker_specs[@]} specs${NC}"

            # Run worker in background, output to log file
            (
                DB_HOST=mysql_test DB_DATABASE=mtav_test DB_USERNAME=mtav DB_PASSWORD=password APP_ENV=testing DOCKER_NGINX_PORT=8001 \
                    docker compose -f "$DOCKER_DIR/compose.yml" --project-name "$TEST_PROJECT" \
                    --profile test \
                    run --rm e2e npx cypress run --spec "$spec_list" 2>&1 | tee "$LOG_DIR/worker-$((i+1)).log"
            ) &
            pids+=($!)
        fi
    done

    # Wait for all workers to complete
    echo -e "${YELLOW}Waiting for all workers to complete...${NC}"
    for i in "${!pids[@]}"; do
        wait ${pids[$i]}
        worker_exit=$?
        if [ $worker_exit -ne 0 ]; then
            EXIT_CODE=$worker_exit
            echo -e "${RED}Worker $((i+1)) failed with exit code $worker_exit${NC}"
        else
            echo -e "${GREEN}Worker $((i+1)) completed successfully${NC}"
        fi
    done

    echo ""
    echo -e "${BLUE}Worker logs saved to: $LOG_DIR${NC}"
fi

echo ""

# Cleanup test environment
echo -e "${YELLOW}Cleaning up test environment...${NC}"
docker compose --project-name "$TEST_PROJECT" down

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}🎉 Cypress tests completed!${NC}"
else
    echo -e "${RED}❌ Cypress tests failed${NC}"
fi

exit $EXIT_CODE
