#!/bin/bash

# MTAV Production Image Builder
# Usage: build.sh <service> [tag] [--push|--no-push]

set -e

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }

# Parse arguments
SERVICE=""
TAG=""
PUSH_MODE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --push)
            PUSH_MODE="push"
            shift
            ;;
        --no-push)
            PUSH_MODE="no-push"
            shift
            ;;
        *)
            if [ -z "$SERVICE" ]; then
                SERVICE="$1"
            elif [ -z "$TAG" ]; then
                TAG="$1"
            else
                print_error "Unknown argument: $1"
                exit 1
            fi
            shift
            ;;
    esac
done

# Validate service
if [[ ! "$SERVICE" =~ ^(nginx|mysql|php|assets|migrations)$ ]]; then
    print_error "Invalid service. Use: nginx, mysql, php, assets, or migrations"
    echo "Usage: $0 <service> <tag> [--push|--no-push]"
    echo ""
    echo "Examples:"
    echo "  $0 php 1.2.0              # Build PHP-FPM version 1.2.0 (asks confirmation whether to push)"
    echo "  $0 assets 1.2.0 --push    # Build and auto-push to registry"
    echo "  $0 mysql 1.2.0 --no-push  # Build locally only, don't push"
    exit 1
fi

# Validate tag is provided
if [[ -z "$TAG" ]]; then
    print_error "Version tag is required"
    echo "Usage: $0 <service> <tag>"
    echo ""
    echo "Examples:"
    echo "  $0 php 1.2.0     # Build PHP-FPM version 1.2.0"
    echo "  $0 assets 1.2.0  # Build static assets version 1.2.0"
    exit 1
fi

# Registry and image configuration
REGISTRY="ghcr.io/devvir"
IMAGE_NAME="mtav-${SERVICE}"
VERSION_IMAGE="${REGISTRY}/${IMAGE_NAME}:${TAG}"

# Build the production image for the given service
print_info "Building ${SERVICE} image: ${VERSION_IMAGE}..."
docker build -f ".docker/build/${SERVICE}/Dockerfile" -t "$VERSION_IMAGE" .
print_success "Successfully built: ${VERSION_IMAGE}"

# Update version.yml with new service version
update_version_file() {
    local version_file=".docker/deploy/version.yml"

    if [ -f "$version_file" ]; then
        print_info "Updating version.yml with ${SERVICE}:${TAG}..."

        # Use sed to update the specific service version in version.yml
        if grep -q "^${SERVICE}:" "$version_file"; then
            # Update existing service line
            sed -i "s/^${SERVICE}:.*/${SERVICE}: '${TAG}'/" "$version_file"
            print_success "Updated ${SERVICE} version to ${TAG} in version.yml"
        else
            # Add new service line (shouldn't happen in normal workflow, but good fallback)
            echo "${SERVICE}: '${TAG}'" >> "$version_file"
            print_success "Added ${SERVICE} version ${TAG} to version.yml"
        fi
    else
        print_error "Warning: version.yml not found at ${version_file}"
        print_info "Skipping version file update..."
    fi
}

# Update version.yml after successful build
update_version_file

# Handle push based on mode
echo ""
SHOULD_PUSH=""

# Determine if we should push based on flags or user input
if [ "$PUSH_MODE" = "push" ]; then
    print_info "Auto-pushing to registry (--push flag specified)..."
    SHOULD_PUSH=true
elif [ "$PUSH_MODE" = "no-push" ]; then
    print_info "Skipping push (--no-push flag specified)..."
    SHOULD_PUSH=false
else
    # Interactive mode - ask user
    read -p "Push to registry? (Y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        SHOULD_PUSH=false
    else
        SHOULD_PUSH=true
    fi
fi

# Execute push or show local-only message based on decision
if [ "$SHOULD_PUSH" = true ]; then
    print_info "Pushing version ${TAG}..."
    docker push "$VERSION_IMAGE"

    print_success "Pushed ${TAG} tag"
    print_info "Image available at:"
    print_info "  • ${VERSION_IMAGE}"
else
    print_info "Image built locally only:"
    print_info "  • ${VERSION_IMAGE}"
    print_info ""
    print_info "To publish later, run:"
    print_info "  docker push ${VERSION_IMAGE}"
fi