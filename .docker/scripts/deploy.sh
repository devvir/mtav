#!/bin/bash

# MTAV Build/Test Deployment Script
# Deploys locally using build compose (extends mtav-deploy + adds seeder)

set -e

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }

# Configuration
COMPOSE_FILE=".docker/build/compose.yml"
ENV_FILE=".docker/deploy/.env"
VERSION_FILE=".docker/deploy/version.yml"

# Parse arguments
CLEAR_MODE=false
DOCKER_ARGS=()

while [[ $# -gt 0 ]]; do
    case $1 in
        --clear)
            CLEAR_MODE=true
            shift
            ;;
        *)
            DOCKER_ARGS+=("$1")
            shift
            ;;
    esac
done

# Handle clear mode
if [ "$CLEAR_MODE" = true ]; then
    print_info "Tearing down test deployment..."
    docker compose --project-name test -f ".docker/deploy/compose.yml" -f "$COMPOSE_FILE" --env-file "$ENV_FILE" down --volumes 2>/dev/null || true

    # Remove any leftover test- containers
    print_info "Removing any leftover test- containers..."
    for container in $(docker ps -aq --filter "name=test-" 2>/dev/null); do
        docker stop "$container" >/dev/null 2>&1 || true
        docker rm "$container" >/dev/null 2>&1 || true
    done

    print_success "Test deployment cleared!"
    exit 0
fi

print_info "MTAV Build/Test Deployment"
print_info "Using production environment (+ dev seeder)"
echo ""

# Check files exist
if [ ! -f "$COMPOSE_FILE" ]; then
    echo "❌ Build compose file not found: $COMPOSE_FILE"
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Environment file not found: $ENV_FILE"
    exit 1
fi

if [ ! -f "$VERSION_FILE" ]; then
    echo "❌ Version file not found: $VERSION_FILE"
    exit 1
fi

# Read service versions from version.yml
print_info "Reading target versions from $VERSION_FILE..."
PHP_TAG=$(grep "^php:" "$VERSION_FILE" | sed "s/php: *['\"]//g" | sed "s/['\"] *$//g")
ASSETS_TAG=$(grep "^assets:" "$VERSION_FILE" | sed "s/assets: *['\"]//g" | sed "s/['\"] *$//g")
NGINX_TAG=$(grep "^nginx:" "$VERSION_FILE" | sed "s/nginx: *['\"]//g" | sed "s/['\"] *$//g")
MYSQL_TAG=$(grep "^mysql:" "$VERSION_FILE" | sed "s/mysql: *['\"]//g" | sed "s/['\"] *$//g")
MIGRATIONS_TAG=$(grep "^migrations:" "$VERSION_FILE" | sed "s/migrations: *['\"]//g" | sed "s/['\"] *$//g")

echo "  PHP: $PHP_TAG"
echo "  Assets: $ASSETS_TAG"
echo "  Nginx: $NGINX_TAG"
echo "  MySQL: $MYSQL_TAG"
echo "  Migrations: $MIGRATIONS_TAG"
echo ""

# Generate temporary APP_KEY for testing
APP_KEY="base64:Bi7UiKN04MDl9NbMwum45i10kNcxmRrFOyiY5qKWyRo="
print_info "Using dummy APP_KEY for testing (no sensitive data will be encrypted)"
echo ""

# Stop any existing containers
print_info "Stopping any existing containers..."
docker compose --project-name test -f ".docker/deploy/compose.yml" -f "$COMPOSE_FILE" --env-file "$ENV_FILE" down --volumes 2>/dev/null || true
echo ""

# Deploy with specific tags
print_info "Starting containers with production images..."
PHP_TAG="$PHP_TAG" \
ASSETS_TAG="$ASSETS_TAG" \
NGINX_TAG="$NGINX_TAG" \
MYSQL_TAG="$MYSQL_TAG" \
MIGRATIONS_TAG="$MIGRATIONS_TAG" \
APP_KEY="$APP_KEY" \
docker compose --project-name test -f ".docker/deploy/compose.yml" -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up "${DOCKER_ARGS[@]}"

print_success "Build/test deployment ready!"

# Only show access URL if not running in detached mode
if [[ ! " ${DOCKER_ARGS[*]} " =~ " -d " ]] && [[ ! " ${DOCKER_ARGS[*]} " =~ " --detach " ]]; then
    print_info "Access the application at: http://localhost:8090"
fi
