#!/bin/bash

# First-time setup: install dependencies, migrate, and seed
DOCKER_DIR="$(dirname "$0")/.."

# Source compose utilities
source "$(dirname "$0")/compose.sh"

echo "📦 First-time setup: installing dependencies and setting up database..."

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

echo "Seeding database with sample data..."
docker_exec php php artisan db:seed --no-interaction

echo "Creating storage symlink..."
docker_exec php php artisan storage:link --relative --no-interaction --force

echo "✅ First-time setup completed successfully!"