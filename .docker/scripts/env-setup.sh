#!/bin/bash

# Environment setup: create .env if it doesn't exist, or merge missing keys
DOCKER_DIR="$(dirname "$0")/.."
PROJECT_ROOT="$DOCKER_DIR/.."

# Set the host user's UID/GID for proper file permissions
HOST_UID=$(id -u)
HOST_GID=$(id -g)

if [ ! -f "$PROJECT_ROOT/.env" ]; then
    echo "📝 Creating .env file from Docker template..."
    cp "$DOCKER_DIR/.env.docker" "$PROJECT_ROOT/.env"

    # Update the .env file with correct user IDs
    sed -i "s/PUID=1000/PUID=$HOST_UID/" "$PROJECT_ROOT/.env"
    sed -i "s/PGID=1000/PGID=$HOST_GID/" "$PROJECT_ROOT/.env"

    echo "✅ .env created with host user permissions (UID:$HOST_UID, GID:$HOST_GID)"
    echo "   Please review and adjust other settings as needed."
else
    echo "📝 Checking .env file for missing configuration..."

    # Extract keys from both files (ignoring comments and empty lines)
    template_keys=$(grep -E '^[A-Z_]+=.*' "$DOCKER_DIR/.env.docker" | cut -d'=' -f1 | sort)
    env_keys=$(grep -E '^[A-Z_]+=.*' "$PROJECT_ROOT/.env" | cut -d'=' -f1 | sort)

    # Find missing keys
    missing_keys=$(comm -23 <(echo "$template_keys") <(echo "$env_keys"))

    if [ -n "$missing_keys" ]; then
        echo "⚠️  WARNING: Your .env file is missing some required configuration keys:"
        echo "$missing_keys" | sed 's/^/   - /'
        echo ""
        echo "🤔 This might cause issues with the application."
        echo "   Missing keys will be added from .env.docker template."
        echo ""
        read -p "   Do you want to proceed and add the missing keys? [Y/n] " -n 1 -r
        echo

        if [[ $REPLY =~ ^[Nn]$ ]]; then
            echo "❌ Setup cancelled. Please update your .env file manually."
            exit 1
        fi

        echo "🔧 Adding missing configuration keys..."

        # Add missing keys from template
        for key in $missing_keys; do
            value=$(grep "^$key=" "$DOCKER_DIR/.env.docker" | cut -d'=' -f2-)
            echo "" >> "$PROJECT_ROOT/.env"
            echo "$key=$value" >> "$PROJECT_ROOT/.env"
            echo "   Added: $key"
        done

        # Update PUID/PGID with current host values
        sed -i "s/PUID=1000/PUID=$HOST_UID/" "$PROJECT_ROOT/.env" 2>/dev/null || true
        sed -i "s/PGID=1000/PGID=$HOST_GID/" "$PROJECT_ROOT/.env" 2>/dev/null || true

        echo "✅ .env updated with missing keys and correct user permissions"
    else
        echo "✅ .env file is up to date with all required configuration"

        # Still update PUID/PGID to match current host
        sed -i "s/PUID=[0-9]*/PUID=$HOST_UID/" "$PROJECT_ROOT/.env" 2>/dev/null || true
        sed -i "s/PGID=[0-9]*/PGID=$HOST_GID/" "$PROJECT_ROOT/.env" 2>/dev/null || true
    fi
fi