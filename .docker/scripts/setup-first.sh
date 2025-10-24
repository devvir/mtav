#!/bin/bash

# First-time setup: install dependencies, migrate, and seed
DOCKER_DIR="$(dirname "$0")/.."

echo "📦 First-time setup: installing dependencies and setting up database..."

echo "Installing Composer dependencies..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php composer install --no-interaction

echo "Waiting for vendor volume to sync..."
sleep 2

echo "Installing NPM dependencies..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec assets npm install --force

echo "Generating Laravel app key..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php php artisan key:generate --no-interaction

echo "Running database migrations..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php php artisan migrate --no-interaction

echo "Seeding database with sample data..."
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec php php artisan db:seed --no-interaction

echo "✅ First-time setup completed successfully!"