# E2E test runner — Playwright browsers + PHP in one container.
#
# The official pest-plugin-browser serves the Laravel app in-process and
# spawns `node_modules/.bin/playwright run-server` on localhost, so the PHP
# test process, node, playwright, and its browsers must share a host.
#
# The image tag MUST match the `playwright` devDependency in package.json —
# the npm client only drives browsers of its own version (the browsers are
# preinstalled in this image at /ms-playwright).

FROM mcr.microsoft.com/playwright:v1.61.1-noble

# PHP 8.4 (ondrej PPA — noble ships 8.3) + extensions matching the app image,
# plus the real GLPK solver (lottery execution) and ffmpeg (media handling)
RUN apt-get update \
    && apt-get install -y --no-install-recommends software-properties-common ca-certificates \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.4-cli \
        php8.4-bcmath \
        php8.4-curl \
        php8.4-gd \
        php8.4-intl \
        php8.4-mbstring \
        php8.4-mysql \
        php8.4-xml \
        php8.4-zip \
        glpk-utils \
        ffmpeg \
        git \
        unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Standalone pnpm binary — same pinning discipline as the other images
ARG PNPM_VERSION=10.34.5
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && wget -qO /usr/local/bin/pnpm "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linuxstatic-${arch}" \
    && chmod +x /usr/local/bin/pnpm

# User/group mapping for host compatibility (bind-mounted repo writes)
ARG PUID=1000
ARG PGID=1000
RUN (getent group ${PGID} || groupadd -g ${PGID} hostuser) \
    && (getent passwd ${PUID} || useradd -m -u ${PUID} -g ${PGID} hostuser)

USER ${PUID}:${PGID}

WORKDIR /var/www/html
