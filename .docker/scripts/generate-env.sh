#!/bin/bash

# Generate environment-specific .env files from .env.template
# Usage: ./generate-env.sh [dev|prod] [output_path]

set -e

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }

ENV_TYPE=${1:-dev}
OUTPUT_PATH=${2:-.env}

PROJECT_ROOT=$(dirname $(dirname $(dirname $(realpath $0))))
TEMPLATE_FILE="$PROJECT_ROOT/.env.template"

if [ ! -f "$TEMPLATE_FILE" ]; then
    print_error "Template file not found: $TEMPLATE_FILE"
    exit 1
fi

print_info "Generating $ENV_TYPE environment from template..."

# Copy template
cp "$TEMPLATE_FILE" "$OUTPUT_PATH"

# Apply environment-specific modifications
case "$ENV_TYPE" in
    "prod"|"production")
        print_info "Applying production settings..."
        sed -i 's/^APP_NAME=.*/APP_NAME="MTAV"/' "$OUTPUT_PATH"
        sed -i 's/^APP_ENV=.*/APP_ENV=production/' "$OUTPUT_PATH"
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' "$OUTPUT_PATH"
        sed -i 's/^LOG_LEVEL=.*/LOG_LEVEL=error/' "$OUTPUT_PATH"
        ;;
    "dev"|"development"|"local")
        print_info "Applying development settings..."
        sed -i 's/^APP_NAME=.*/APP_NAME="MTAV (dev)"/' "$OUTPUT_PATH"
        sed -i 's/^APP_ENV=.*/APP_ENV=local/' "$OUTPUT_PATH"
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=true/' "$OUTPUT_PATH"
        sed -i 's/^LOG_LEVEL=.*/LOG_LEVEL=debug/' "$OUTPUT_PATH"
        ;;
    *)
        print_warning "Unknown environment type: $ENV_TYPE. Using template as-is."
        ;;
esac

print_success "Environment file generated: $OUTPUT_PATH"