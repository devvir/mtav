# shellcheck shell=bash
# The release image, built as the pipeline builds it (documentation/technical/delivery.md).

# mtav release [docker build args]
release() {
    info "🏗️  Building the release image (mtav-release:local)..."
    docker build -f deploy/Dockerfile -t mtav-release:local "$@" .
}
