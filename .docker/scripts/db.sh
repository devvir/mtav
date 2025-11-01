#!/bin/bash

# Database access
DOCKER_DIR="$(dirname "$0")/.."

echo "🗄️  Connecting to MySQL database..."
echo "Database: mtav, User: mtav, Password: secret"
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env -p dev exec mysql mysql -u mtav -p mtav