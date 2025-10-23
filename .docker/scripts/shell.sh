#!/bin/bash

# Container shell access
DOCKER_DIR="$(dirname "$0")/.."

CONTAINER=${1:-php}
shift # Remove container name from arguments

# Check if -c flag is provided for command execution
if [[ "$1" == "-c" ]]; then
    # Command execution mode - just run the command and return output
    COMMAND="$2"
    docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec "$CONTAINER" /bin/sh -c "$COMMAND"
else
    # Interactive shell mode
    echo "🐚 Opening shell in $CONTAINER container..."
    docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env exec "$CONTAINER" /bin/sh
fi