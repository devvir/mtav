#!/bin/bash

# Composer commands
source "$(dirname "$0")/compose.sh"

if [ $# -eq 0 ]; then
    echo "Usage: $0 <composer-command>"
    echo "Examples:"
    echo "  $0 install"
    echo "  $0 require vendor/package"
    echo "  $0 update"
    echo "  $0 dump-autoload"
    exit 1
fi

docker_exec php composer $*