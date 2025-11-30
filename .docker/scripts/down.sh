#!/bin/bash

# Copilot - Pending review

# Stop and remove Docker containers for the dev profile
# Usage: down.sh [additional docker compose args]

DOCKER_DIR="$(dirname "$0")/.."
source "$(dirname "$0")/compose.sh"

# Set environment for dev profile
export COMPOSE_PROJECT_NAME=dev

# Stop and remove containers with the dev profile
docker_compose --profile dev down "$@"
