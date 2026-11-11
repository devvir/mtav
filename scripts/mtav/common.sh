# shellcheck shell=bash
# What every part of ./mtav uses: the environments, and how it talks to the person.

ENVIRONMENTS=(dev testing e2e)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info() { echo -e "${BLUE}$*${NC}"; }
ok() { echo -e "${GREEN}$*${NC}"; }
warn() { echo -e "${YELLOW}$*${NC}"; }
fail() { echo -e "${RED}$*${NC}" >&2; }

# A step of a long run, after a blank line
step() {
    echo ""
    info "$@"
}

# The title of one part of a test run
banner() {
    echo ""
    warn "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    warn "$1"
    warn "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

is_environment() {
    local env
    for env in "${ENVIRONMENTS[@]}"; do
        [ "$env" = "$1" ] && return 0
    done
    return 1
}

# The environment a command names first, or dev: `env=$(env_or_dev "$@")`, then
# `[ "${1:-}" = "$env" ] && shift` drops it from the arguments
env_or_dev() {
    if [ $# -gt 0 ] && is_environment "$1"; then
        echo "$1"
    else
        echo dev
    fi
}
