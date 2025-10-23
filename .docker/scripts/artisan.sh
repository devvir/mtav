#!/bin/bash

# PHP Artisan commands
source "$(dirname "$0")/compose.sh"

if [ $# -eq 0 ]; then
    echo "Usage: $0 <artisan-command>"
    echo "Examples:"
    echo "  $0 migrate"
    echo "  $0 tinker"
    echo "  $0 make:controller UserController"
    exit 1
fi

docker_exec php php artisan "$@"