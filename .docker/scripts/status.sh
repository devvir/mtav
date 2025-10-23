#!/bin/bash

# Show container status and URLs
DOCKER_DIR="$(dirname "$0")/.."

echo "📊 Container Status:"
docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env ps
echo ""
echo "🌐 Application URLs:"
echo "  • Main App:     http://localhost:8000"
echo "  • Vite Dev:     http://localhost:5173"
echo "  • MailHog:      http://localhost:8025"
echo "  • Database:     localhost:3307 (mtav/secret)"