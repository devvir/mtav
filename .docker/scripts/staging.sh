#!/bin/bash

# Staging environment setup: Clean slate with built assets

set -e

DOCKER_DIR="$(dirname "$0")/.."
PROJECT_ROOT="$DOCKER_DIR/.."
SCRIPTS_DIR="$DOCKER_DIR/scripts"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🚀 Setting up staging environment...${NC}"

# 1. Pull latest code
echo -e "${BLUE}📥 Pulling latest code from repository...${NC}"
cd "$PROJECT_ROOT"
git pull origin main

# 2. Setup environment
echo -e "${BLUE}📝 Setting up environment...${NC}"
"$SCRIPTS_DIR/env-setup.sh"

# 3. Start dev services (without assets container)
echo -e "${BLUE}🐳 Starting containers...${NC}"
export COMPOSE_PROJECT_NAME=dev
"$SCRIPTS_DIR/compose.sh" up php nginx mysql mailhog --build -d

# 4. Wait for services to be healthy
echo -e "${BLUE}⏳ Waiting for database...${NC}"
timeout 60 bash -c 'until docker exec dev-mysql-1 healthcheck.sh --connect --innodb_initialized 2>/dev/null; do sleep 1; done' || {
    echo -e "${YELLOW}⚠️  Database health check timeout, proceeding anyway...${NC}"
}

# 5. Build assets with command override
echo -e "${BLUE}📦 Building frontend assets...${NC}"
"$SCRIPTS_DIR/compose.sh" run --rm assets sh -c "npm install && npm run build"

# 6. Check if build succeeded
if [ -d "$PROJECT_ROOT/public/build" ] && [ -f "$PROJECT_ROOT/public/build/manifest.json" ]; then
    echo -e "${GREEN}✅ Assets built successfully${NC}"
else
    echo -e "${YELLOW}⚠️  Warning: Asset build may have failed${NC}"
fi

# 7. Install PHP dependencies
echo -e "${BLUE}📦 Installing PHP dependencies...${NC}"
"$SCRIPTS_DIR/composer.sh" install --no-interaction --no-dev --optimize-autoloader

# 8. Run migrations and seed
echo -e "${BLUE}🗄️  Running database migrations...${NC}"
"$SCRIPTS_DIR/artisan.sh" migrate --force
echo -e "${BLUE}🌱 Seeding database...${NC}"
"$SCRIPTS_DIR/artisan.sh" db:seed --force

# 9. Create storage symlink
echo -e "${BLUE}🔗 Creating storage symlink...${NC}"
"$SCRIPTS_DIR/artisan.sh" storage:link --relative --no-interaction --force

# 10. Optimize for staging
echo -e "${BLUE}⚡ Optimizing application...${NC}"
"$SCRIPTS_DIR/artisan.sh" config:cache
"$SCRIPTS_DIR/artisan.sh" route:cache
"$SCRIPTS_DIR/artisan.sh" view:cache

echo ""
echo -e "${GREEN}✅ Staging environment ready!${NC}"
echo ""
"$SCRIPTS_DIR/status.sh"
