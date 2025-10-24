#!/bin/bash

# NPM/Vite commands
source "$(dirname "$0")/compose.sh"

if [ $# -eq 0 ]; then
    echo "Usage: $0 <npm-command>"
    echo "Examples:"
    echo "  $0 install"
    echo "  $0 run dev"
    echo "  $0 run build"
    echo "  $0 add vue@latest"
    exit 1
fi

docker_exec assets npm "$@"