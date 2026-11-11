# shellcheck shell=bash
# The warden: a page watching every environment from above (dev/warden/README.md).

warden() {
    local node_bin
    # This machine's Node (under nvm it is a shell function, unknown to this script), or any
    node_bin=$(command -v node 2>/dev/null || find "$HOME/.nvm/versions/node" -mindepth 3 -maxdepth 3 -path '*/bin/node' 2>/dev/null | sort -V | tail -1)
    if [ -z "$node_bin" ]; then
        fail "❌ The warden needs Node on this machine"
        exit 1
    fi
    # Its page, built from dev/warden/web with the app's own design system (a few seconds)
    PATH="$(dirname "$node_bin"):$PATH" "$ROOT/node_modules/.bin/vite" build --logLevel warn \
        --config "$ROOT/dev/warden/vite.config.mjs" || exit 1
    # The repository's .env may set its port and AWS profile (MTAV_WARDEN_PORT, MTAV_AWS_PROFILE)
    exec "$node_bin" --env-file-if-exists="$ROOT/.env" "$ROOT/dev/warden/server.mjs"
}
