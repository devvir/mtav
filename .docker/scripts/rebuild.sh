#!/bin/bash

# Rebuild container images
source "$(dirname "$0")/compose.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

SERVICE=${1:-}

if [ -z "$SERVICE" ]; then
    echo -e "${BLUE}🔨 Rebuilding all container images...${NC}"
    docker_compose build --no-cache "$@"
    echo ""
    echo -e "${GREEN}✅ All images rebuilt successfully!${NC}"
    echo -e "${YELLOW}Run 'mtav up' to restart with the new images${NC}"
else
    echo -e "${BLUE}🔨 Rebuilding $SERVICE container image...${NC}"
    docker_compose build --no-cache "$@"
    echo ""
    echo -e "${GREEN}✅ $SERVICE image rebuilt successfully!${NC}"
    echo -e "${YELLOW}Restarting $SERVICE container...${NC}"
    "$(dirname "$0")/up.sh" dev -d "$SERVICE"
    echo -e "${GREEN}✅ $SERVICE container restarted${NC}"
fi
