# shellcheck shell=bash
# The environments' lifecycle: up, stop, down, fresh, update, rebuild; what runs, and its logs.

# Written once dev has had its first-run setup
FIRST_RUN_WITNESS="$DOCKER_DIR/.first-run"

# Dependencies, schema and demo data of a first dev run
first_time_setup() {
    info "📦 First-time setup: dependencies and database..."

    echo "Installing Composer dependencies..."
    cexec dev php composer install --no-interaction

    echo "Running database migrations..."
    cexec dev php php artisan migrate --no-interaction

    echo "Seeding database with sample data..."
    cexec dev php php artisan db:seed --no-interaction

    echo "Creating storage symlink..."
    cexec dev php php artisan storage:link --relative --no-interaction --force

    ok "✅ First-time setup completed successfully!"
}

# mtav status [env]
status() {
    local env="${1:-dev}"
    local vite_port mailpit_port mysql_port

    echo "📊 Container Status ($env):"
    compose "$env" ps
    echo ""
    echo "🌐 Application URLs:"
    echo "  • Main App:     http://localhost:$(env_value "$env" NGINX_PORT)"

    vite_port=$(env_value "$env" VITE_PORT)
    mailpit_port=$(env_value "$env" MAILPIT_HTTP_PORT)
    mysql_port=$(env_value "$env" MYSQL_PORT)
    [ -n "$vite_port" ] && echo "  • Vite Dev:     http://localhost:$vite_port"
    [ -n "$mailpit_port" ] && echo "  • Mailpit:      http://localhost:$mailpit_port"
    [ -n "$mysql_port" ] && echo "  • Database:     localhost:$mysql_port (mtav/secret)"
    return 0
}

# mtav up [env] [compose up args]
up() {
    local env
    env=$(env_or_dev "$@")
    [ "${1:-}" = "$env" ] && shift

    env_setup

    if [ "$env" = "dev" ] && [ ! -f "$FIRST_RUN_WITNESS" ]; then
        ok "🚀 First time setup: building everything..."
        compose dev up --build --detach --wait --wait-timeout 120 "$@"
        first_time_setup
        touch "$FIRST_RUN_WITNESS"
    else
        info "🚀 Starting $env environment..."
        compose "$env" up --detach --wait --wait-timeout 120 "$@"
    fi

    # The schema kept current on every up: plain migrate, never fresh (that is `mtav fresh`)
    if [ "$env" = "dev" ]; then
        info "🗄️  Running database migrations..."
        cexec dev php php artisan migrate --no-interaction
    fi

    status "$env"
}

# mtav down [env] [compose down args]
down() {
    local env
    env=$(env_or_dev "$@")
    [ "${1:-}" = "$env" ] && shift
    warn "🛑 Stopping and removing $env containers..."
    compose "$env" down "$@"
}

# mtav stop [env] [compose stop args]
stop() {
    local env
    env=$(env_or_dev "$@")
    [ "${1:-}" = "$env" ] && shift
    warn "🛑 Stopping $env containers..."
    compose "$env" stop "$@"
}

restart() {
    info "🔄 Restarting: down then up..."
    down dev && up dev
}

# mtav logs [env] [service...]
logs() {
    local env
    env=$(env_or_dev "$@")
    [ "${1:-}" = "$env" ] && shift
    info "📋 Showing $env container logs..."
    compose "$env" logs -f "$@"
}

# Dev with its database started over: volumes removed, everything built, demo data seeded
fresh() {
    info "🆕 Fresh start: cleaning and rebuilding everything..."
    compose dev down --volumes --remove-orphans
    rm -f "$FIRST_RUN_WITNESS"
    up dev
}

update() {
    info "🔄 Updating containers and dependencies..."
    env_setup
    compose dev up --build --detach --wait --wait-timeout 120

    echo "Installing Composer dependencies..."
    cexec dev php composer install --no-interaction

    echo "Installing Node dependencies..."
    cexec dev assets pnpm install

    echo "Running database migrations..."
    cexec dev php php artisan migrate --no-interaction

    status dev
}

# mtav rebuild [service]
rebuild() {
    local service="${1:-}"
    if [ -z "$service" ]; then
        info "🔨 Rebuilding all container images..."
        compose dev build --no-cache
        ok "✅ All images rebuilt — run 'mtav up' to restart"
    else
        info "🔨 Rebuilding $service image..."
        compose dev build --no-cache "$service"
        warn "Restarting $service container..."
        compose dev up --detach "$service"
        ok "✅ $service rebuilt and restarted"
    fi
}

# mtav shell [service] [-c command]
shell() {
    local service="${1:-php}"
    shift 2>/dev/null || true
    if [ "${1:-}" = "-c" ]; then
        cexec dev "$service" /bin/sh -c "$2"
    else
        echo "🐚 Opening shell in $service container..."
        cexec dev "$service" /bin/sh
    fi
}
