#!/bin/bash

# Start Docker containers with the appropriate profile
# Usage: up.sh [dev|testing|browser] [additional docker compose args]

DOCKER_DIR="$(dirname "$0")/.."
source "$(dirname "$0")/compose.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse profile argument
PROFILE="${1:-dev}"

# Validate profile
if [[ ! "$PROFILE" =~ ^(dev|testing|browser)$ ]]; then
    echo -e "${RED}❌ Invalid profile: $PROFILE${NC}"
    echo "Usage: up.sh [dev|testing|browser] [additional docker compose args]"
    echo ""
    echo "Profiles:"
    echo "  dev      - Development environment (default)"
    echo "  testing  - Testing environment for Pest/Vitest"
    echo "  browser  - Browser testing environment for Playwright"
    exit 1
fi

# Remove profile from arguments if present
shift 2>/dev/null || true

# Set environment variables based on profile
case $PROFILE in
    dev)
        export COMPOSE_PROJECT_NAME=dev
        export APP_ENV=local
        export DB_HOST=mysql
        export DOCKER_NGINX_PORT=8000
        export DOCKER_VITE_PORT=5173
        export DOCKER_MYSQL_PORT=3307
        export DOCKER_MAILHOG_SMTP_PORT=1025
        export DOCKER_MAILHOG_WEB_PORT=8025
        echo -e "${BLUE}🚀 Starting development environment...${NC}"
        ;;

    testing)
        export COMPOSE_PROJECT_NAME=testing
        export APP_ENV=testing
        export DB_HOST=mysql_test
        export DOCKER_NGINX_PORT=8001
        export DOCKER_VITE_PORT=5174
        export DOCKER_MYSQL_TEST_PORT=3308
        echo -e "${BLUE}🧪 Starting testing environment...${NC}"
        ;;

    browser)
        export COMPOSE_PROJECT_NAME=browser
        export APP_ENV=testing
        export DB_HOST=mysql_test
        export DOCKER_NGINX_PORT=8002
        export DOCKER_VITE_PORT=5175
        export DOCKER_MYSQL_TEST_PORT=3309
        echo -e "${BLUE}🌐 Starting browser testing environment...${NC}"
        ;;
esac

# Start containers with the specified profile
docker_compose --profile "$PROFILE" up "$@"
