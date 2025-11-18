#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Help function
show_help() {
    echo -e "${BLUE}MTAV PHP Base Image Builder${NC}"
    echo
    echo "Usage: $0 <version>"
    echo
    echo "This script builds and pushes the MTAV PHP base image with the specified version."
    echo
    echo "Arguments:"
    echo "  version    Version tag for the image (e.g., 1.0.0, 1.1.0)"
    echo
    echo "Examples:"
    echo "  $0 1.2.0"
    echo "  $0 2.0.0-beta"
    echo
    echo "What this script does:"
    echo "  1. Builds the PHP base image from .docker/build/image/Dockerfile"
    echo "  2. Tags it as ghcr.io/devvir/mtav-php-base:<version>"
    echo "  3. Tags it as ghcr.io/devvir/mtav-php-base:latest"
    echo "  4. Pushes both tags to the GitHub Container Registry"
    echo
    echo "Prerequisites:"
    echo "  - Docker must be installed and running"
    echo "  - You must be logged in to ghcr.io (docker login ghcr.io)"
    echo "  - You must have push permissions to the devvir/mtav repository"
    echo
}

# Check if version parameter is provided
if [ $# -eq 0 ] || [ "$1" == "-h" ] || [ "$1" == "--help" ]; then
    show_help
    exit 1
fi

VERSION="$1"

# Validate version format (basic check)
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][a-zA-Z0-9]+)*$ ]]; then
    echo -e "${RED}Error: Invalid version format. Please use semantic versioning (e.g., 1.2.3, 1.0.0-beta)${NC}"
    exit 1
fi

echo -e "${BLUE}Building MTAV PHP Base Image v${VERSION}${NC}"
echo

# Check if we're in the correct directory
if [ ! -f ".docker/build/image/Dockerfile" ]; then
    echo -e "${RED}Error: .docker/build/image/Dockerfile not found. Please run this script from the MTAV root directory.${NC}"
    exit 1
fi

# Build the image
echo -e "${YELLOW}Step 1/4: Building Docker image...${NC}"
if docker build -t "ghcr.io/devvir/mtav-php-base:${VERSION}" -f .docker/build/image/Dockerfile .; then
    echo -e "${GREEN}✓ Image built successfully${NC}"
else
    echo -e "${RED}✗ Failed to build image${NC}"
    exit 1
fi

echo

# Tag as latest
echo -e "${YELLOW}Step 2/4: Tagging as latest...${NC}"
if docker tag "ghcr.io/devvir/mtav-php-base:${VERSION}" "ghcr.io/devvir/mtav-php-base:latest"; then
    echo -e "${GREEN}✓ Tagged as latest${NC}"
else
    echo -e "${RED}✗ Failed to tag as latest${NC}"
    exit 1
fi

echo

# Push versioned image
echo -e "${YELLOW}Step 3/4: Pushing versioned image to registry...${NC}"
if docker push "ghcr.io/devvir/mtav-php-base:${VERSION}"; then
    echo -e "${GREEN}✓ Pushed ghcr.io/devvir/mtav-php-base:${VERSION}${NC}"
else
    echo -e "${RED}✗ Failed to push versioned image${NC}"
    exit 1
fi

echo

# Push latest image
echo -e "${YELLOW}Step 4/4: Pushing latest image to registry...${NC}"
if docker push "ghcr.io/devvir/mtav-php-base:latest"; then
    echo -e "${GREEN}✓ Pushed ghcr.io/devvir/mtav-php-base:latest${NC}"
else
    echo -e "${RED}✗ Failed to push latest image${NC}"
    exit 1
fi

echo
echo -e "${GREEN}🎉 Successfully built and pushed MTAV PHP Base Image v${VERSION}!${NC}"
echo

# Automatically rebuild dependent containers
echo -e "${YELLOW}Step 5/5: Rebuilding dependent containers...${NC}"
echo "Rebuilding containers that depend on the PHP base image..."

# List of services that depend on the PHP base image
DEPENDENT_SERVICES=("php")

for service in "${DEPENDENT_SERVICES[@]}"; do
    echo -e "${BLUE}  → Rebuilding ${service} container...${NC}"
    if .docker/scripts/rebuild.sh "$service"; then
        echo -e "${GREEN}  ✓ ${service} rebuilt successfully${NC}"
    else
        echo -e "${YELLOW}  ⚠ Failed to rebuild ${service} - you may need to rebuild manually${NC}"
    fi
done

echo
echo -e "${GREEN}🚀 All done! PHP base image and dependent containers updated.${NC}"
echo
echo "Summary:"
echo "  ✓ Built and pushed ghcr.io/devvir/mtav-php-base:${VERSION}"
echo "  ✓ Updated latest tag"
echo "  ✓ Rebuilt dependent development containers"
echo
echo "Everything is ready to use with FFmpeg support!"
echo