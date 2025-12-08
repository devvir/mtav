#!/bin/bash

# MTAV Production Image Builder
# Usage: build.sh <service> [tag] [--push|--no-push|--all]

set -e

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }

# Parse arguments - collect positional args separately
SERVICE=""
TAG=""
PUSH_MODE=""
BUILD_ALL=false
POSITIONAL_ARGS=()

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
        --all)
            BUILD_ALL=true
            shift
            ;;
        -*)
            print_error "Unknown option: $1"
            exit 1
            ;;
        *)
            POSITIONAL_ARGS+=("$1")
            shift
            ;;
    esac
done

# Process positional arguments based on BUILD_ALL flag
if [ "$BUILD_ALL" = true ]; then
    # For --all, expect only the tag
    if [ ${#POSITIONAL_ARGS[@]} -gt 0 ]; then
        TAG="${POSITIONAL_ARGS[0]}"
    fi
    if [ ${#POSITIONAL_ARGS[@]} -gt 1 ]; then
        print_error "Too many arguments. When using --all, only provide the version tag."
        exit 1
    fi
else
    # For single service, expect service name and tag
    if [ ${#POSITIONAL_ARGS[@]} -gt 0 ]; then
        SERVICE="${POSITIONAL_ARGS[0]}"
    fi
    if [ ${#POSITIONAL_ARGS[@]} -gt 1 ]; then
        TAG="${POSITIONAL_ARGS[1]}"
    fi
    if [ ${#POSITIONAL_ARGS[@]} -gt 2 ]; then
        print_error "Too many arguments"
        exit 1
    fi
fi

# If --all flag is set, build all services
if [ "$BUILD_ALL" = true ]; then
    # Validate tag is provided
    if [[ -z "$TAG" ]]; then
        print_error "Version tag is required when building all services"
        echo "Usage: $0 --all <tag> [--push|--no-push]"
        echo ""
        echo "Examples:"
        echo "  $0 --all 1.2.0              # Build all services with version 1.2.0"
        echo "  $0 --all 1.2.0 --push       # Build and push all services"
        echo "  $0 --all 1.2.0 --no-push    # Build all services locally only"
        exit 1
    fi

    # Build app image first (local base image for php and queue)
    print_info "Building app base image (required by php and queue)..."
    docker build -f ".docker/build/app/Dockerfile" -t "mtav-app" .
    print_success "Successfully built: mtav-app"
    echo ""

    # Define all production services
    ALL_SERVICES=("php" "assets" "nginx" "mysql" "migrations" "queue")

    print_info "Building all production services with tag: ${TAG}"
    print_warning "This will build: ${ALL_SERVICES[*]}"
    echo ""

    # Track failures
    FAILED_SERVICES=()
    SUCCESS_COUNT=0

    # Build each service
    for svc in "${ALL_SERVICES[@]}"; do
        echo ""
        print_info "========================================"
        print_info "Building service: ${svc}"
        print_info "========================================"

        # Call this script recursively for each service
        if "$0" "$svc" "$TAG" ${PUSH_MODE:+--$PUSH_MODE}; then
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        else
            FAILED_SERVICES+=("$svc")
            print_error "Failed to build ${svc}"
        fi
    done

    # Summary
    echo ""
    print_info "========================================"
    print_info "Build Summary"
    print_info "========================================"
    print_success "Successfully built: ${SUCCESS_COUNT}/${#ALL_SERVICES[@]} services"

    if [ ${#FAILED_SERVICES[@]} -gt 0 ]; then
        print_error "Failed services: ${FAILED_SERVICES[*]}"
        exit 1
    fi

    print_success "All services built successfully!"
    exit 0
fi

# Validate service for single-service build
if [[ ! "$SERVICE" =~ ^(nginx|mysql|php|assets|migrations|queue)$ ]]; then
    print_error "Invalid service. Use: nginx, mysql, php, assets, migrations, or queue"
    echo "Usage: $0 <service> <tag> [--push|--no-push]"
    echo "   or: $0 --all <tag> [--push|--no-push]"
    echo ""
    echo "Examples:"
    echo "  $0 php 1.2.0              # Build PHP-FPM version 1.2.0 (asks confirmation whether to push)"
    echo "  $0 assets 1.2.0 --push    # Build and auto-push to registry"
    echo "  $0 mysql 1.2.0 --no-push  # Build locally only, don't push"
    echo "  $0 --all 1.2.0            # Build all services with version 1.2.0"
    echo "  $0 --all 1.2.0 --push     # Build and push all services"
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

# Build app base image first if building php or queue (they depend on it)
if [[ "$SERVICE" =~ ^(php|queue)$ ]]; then
    print_info "Building app base image (required by ${SERVICE})..."
    docker build -f ".docker/build/app/Dockerfile" -t "mtav-app" .
    print_success "Successfully built: mtav-app"
    echo ""
fi

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