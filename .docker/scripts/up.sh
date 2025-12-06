#!/bin/bash

# Start Docker containers with the appropriate profile
# Usage: up.sh [dev|testing] [additional docker compose args]

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
if [[ ! "$PROFILE" =~ ^(dev|testing)$ ]]; then
    echo -e "${RED}❌ Invalid profile: $PROFILE${NC}"
    echo "Usage: up.sh [dev|testing] [additional docker compose args]"
    echo ""
    echo "Profiles:"
    echo "  dev     - Development environment (default)"
    echo "  testing - Testing environment for Pest/Vitest/Playwright"
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
esac

# Start containers with the specified profile
docker_compose --profile "$PROFILE" up "$@"

# Wait for database to be healthy (only for dev and testing profiles)
if [[ "$PROFILE" == "dev" ]] || [[ "$PROFILE" == "testing" ]]; then
    DB_CONTAINER="${COMPOSE_PROJECT_NAME}-mysql-1"
    if [[ "$PROFILE" == "testing" ]]; then
        DB_CONTAINER="${COMPOSE_PROJECT_NAME}-mysql_test-1"
    fi

    echo -e "${BLUE}⏳ Waiting for database to be ready...${NC}"
    timeout 60 sh -c "until [ \"\$(docker inspect --format='{{.State.Health.Status}}' $DB_CONTAINER 2>/dev/null)\" = 'healthy' ]; do sleep 1; done" && \
        echo -e "${GREEN}✅ Database is ready!${NC}" || \
        echo -e "${YELLOW}⚠️  Database health check timed out (containers may still be starting)${NC}"
fi

            # Ensure PHP container has correct git safe.directory and writable vendor directory
            echo -e "${BLUE}🔧 Fixing repository permissions inside php container (as root)...${NC}"
            # Run as root to avoid permission errors when creating/chowning mounted dirs
            docker_compose exec -T --user root php bash -lc "git config --global --add safe.directory /var/www/html || true"
            docker_compose exec -T --user root php bash -lc "mkdir -p /var/www/html/vendor && chmod -R a+rwx /var/www/html/vendor || true"
