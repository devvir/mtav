#!/bin/bash

# Regular setup: install dependencies and migrate (no seeding)
DOCKER_DIR="$(dirname "$0")/.."

echo "📦 Installing dependencies..."

echo "Installing Composer dependencies..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php composer install --no-interaction

echo "Waiting for vendor volume to sync..."
sleep 2

echo "Installing NPM dependencies..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec vite npm install --force

echo "Generating Laravel app key..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php php artisan key:generate --no-interaction

echo "Running database migrations..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php php artisan migrate --no-interaction

echo "✅ Dependencies installed successfully!"