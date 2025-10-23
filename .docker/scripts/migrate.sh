#!/bin/bash

# Quick migration command
source "$(dirname "$0")/compose.sh"

echo "🔄 Running database migrations..."
docker_exec php php artisan migrate "$@"