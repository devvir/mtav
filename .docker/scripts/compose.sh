#!/bin/bash

# Docker Compose utilities and direct command execution
# Can be used in two ways:
# 1. Source this script: source "$(dirname "$0")/compose.sh"
# 2. Execute directly: ./compose.sh ps

# Detect Docker directory relative to the calling script
if [ -z "$DOCKER_DIR" ]; then
    DOCKER_DIR="$(dirname "${BASH_SOURCE[0]}")/.."
fi

# Detect if TTY is available for Docker exec
if [ -t 0 ]; then
    DOCKER_TTY_FLAG=""
else
    DOCKER_TTY_FLAG="-T"
fi

# Common Docker Compose command with standard options
docker_compose() {
    local project_name="${COMPOSE_PROJECT_NAME:-dev}"
    docker compose -f "$DOCKER_DIR/compose.yml" --env-file .env -p "$project_name" "$@"
}

# Docker Compose exec with TTY detection
docker_exec() {
    local container="$1"
    shift
    docker_compose exec $DOCKER_TTY_FLAG "$container" "$@"
}

# If script is executed directly (not sourced), run docker compose command
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    docker_compose "$@"
fi