#!/bin/bash

# Regular setup: install dependencies and migrate (no seeding)
DOCKER_DIR="$(dirname "$0")/.."

# Source compose utilities
source "$(dirname "$0")/compose.sh"

echo "📦 Installing dependencies..."

echo "Installing Composer dependencies..."
docker_exec php composer install --no-interaction

echo "Waiting for vendor volume to sync..."
sleep 2

echo "Installing NPM dependencies..."
docker_exec assets npm install --force

echo "Generating Laravel app key..."
docker_exec php php artisan key:generate --no-interaction

echo "Running database migrations..."
docker_exec php php artisan migrate --no-interaction

echo "Creating storage symlink..."
docker_exec php php artisan storage:link --no-interaction

echo "✅ Dependencies installed successfully!"