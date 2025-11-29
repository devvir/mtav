#!/bin/sh

set -e

echo "Building frontend assets..."
npm ci && npm run build

# Execute the CMD
exec "$@"
