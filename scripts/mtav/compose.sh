# shellcheck shell=bash
# Docker Compose against one environment, and the .env every environment layers on.
#
#   docker/<env>/compose.yml    composition (project name, services, network)
#   docker/<env>/.env           committed per-environment settings (ports, APP_ENV)
#   docker/<env>/.env.local     optional personal overrides (not versioned)
#   docker/services/<name>/     the services the compositions are built from
#
# Settings layer in order: root .env, docker/<env>/.env, docker/<env>/.env.local.

DOCKER_DIR="docker"

# compose <env> <docker compose args...>
compose() {
    local env="$1"
    shift
    local args=(-f "$DOCKER_DIR/$env/compose.yml" --env-file .env --env-file "$DOCKER_DIR/$env/.env")
    [ -f "$DOCKER_DIR/$env/.env.local" ] && args+=(--env-file "$DOCKER_DIR/$env/.env.local")
    docker compose "${args[@]}" "$@"
}

# cexec <env> <service> <cmd...>: a command in a running service, with a TTY when there is one
cexec() {
    local env="$1" service="$2"
    shift 2
    local tty_flag=()
    [ -t 0 ] || tty_flag=(-T)
    compose "$env" exec "${tty_flag[@]}" "$service" "$@"
}

# Whether a service of an environment is running
is_running() {
    [ -n "$(compose "$1" ps --quiet --status running "$2" 2>/dev/null)" ]
}

# env_value <env> <VAR>: a setting as the layered env files resolve it
env_value() {
    local env="$1" var="$2"
    (
        set -a
        # shellcheck disable=SC1091
        source .env 2>/dev/null || true
        # shellcheck disable=SC1090
        source "$DOCKER_DIR/$env/.env" 2>/dev/null || true
        # shellcheck disable=SC1090
        [ -f "$DOCKER_DIR/$env/.env.local" ] && source "$DOCKER_DIR/$env/.env.local"
        printf '%s' "${!var}"
    )
}

# Writes .env from the template on a first run; later, adds the keys the template gained
env_setup() {
    local host_uid host_gid
    host_uid=$(id -u)
    host_gid=$(id -g)

    if [ ! -f .env ]; then
        info "📝 Creating .env file from template..."
        cp .env.template .env

        info "🔑 Generating unique APP_KEY for development..."
        local app_key
        app_key="base64:$(openssl rand -base64 32)"
        sed -i "s|^APP_KEY=.*|APP_KEY=$app_key|" .env

        set_env PUID "$host_uid"
        set_env PGID "$host_gid"

        ok "✅ .env created (APP_KEY set, UID:$host_uid GID:$host_gid)"
        echo "   Please review and adjust other settings as needed."
        return
    fi

    local template_keys env_keys missing_keys key value
    template_keys=$(grep -E '^[A-Z_]+=' .env.template | cut -d'=' -f1 | sort)
    env_keys=$(grep -E '^[A-Z_]+=' .env | cut -d'=' -f1 | sort)
    missing_keys=$(comm -23 <(echo "$template_keys") <(echo "$env_keys"))

    if [ -n "$missing_keys" ]; then
        warn "⚠️  .env is missing keys from .env.template — adding them:"
        for key in $missing_keys; do
            value=$(grep "^$key=" .env.template | head -1 | cut -d'=' -f2-)
            printf '\n%s=%s\n' "$key" "$value" >> .env
            echo "   Added: $key"
        done
    fi

    # Containers write to the bind mount as the host user
    set_env PUID "$host_uid"
    set_env PGID "$host_gid"
}

# Sets KEY=value in .env, adding the line when it is missing (or only commented out)
set_env() {
    if grep -q "^$1=" .env; then
        sed -i "s|^$1=.*|$1=$2|" .env
    else
        [ -n "$(tail -c 1 .env)" ] && echo >> .env
        echo "$1=$2" >> .env
    fi
}

# mtav compose <env> <args...>
cmd_compose() {
    local env="${1:-}"
    shift 2>/dev/null || true
    if ! is_environment "$env"; then
        fail "❌ Unknown environment: '$env' (expected: ${ENVIRONMENTS[*]})"
        exit 1
    fi
    compose "$env" "$@"
}
