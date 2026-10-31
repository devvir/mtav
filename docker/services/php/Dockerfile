# MTAV PHP — multi-stage image
#
# Stages:
#   base   — php-fpm + extensions + composer (shared foundation)
#   dev    — base + xdebug + node/pnpm + host user mapping (code bind-mounted)
#
# Releases are built apart, by deploy/Dockerfile.
#
# Build context is the repository root.

# ============================================================================
FROM php:8.5.12-fpm-alpine AS base

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r "echo 'OK';" || exit 1

# System dependencies for PHP extensions
RUN apk add --no-cache \
        bash \
        ffmpeg \
        freetype-dev \
        git \
        glpk glpk-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libxml2 \
        libzip-dev \
        oniguruma-dev \
        zip \
        zlib-dev

# PHP extensions (linux-headers needed only for sockets compilation)
RUN apk add --no-cache --virtual .build-deps linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        sockets \
    && apk del .build-deps

COPY --from=composer:2.10.2 /usr/bin/composer /usr/bin/composer

# ============================================================================
FROM base AS dev

# Xdebug (coverage, debugging) + Node runtime for artisan tooling
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && apk add --no-cache nodejs \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Standalone pnpm binary — version-pinned, no npm involved
ARG PNPM_VERSION=12.15.0
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && mkdir -p /opt/pnpm \
    && wget -qO- "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linux-${arch}-musl.tar.gz" | tar xz -C /opt/pnpm \
    && ln -s /opt/pnpm/pnpm /usr/local/bin/pnpm

# The Claude command line: for the Assistant on a developer's machine (config/assistant.php)
ARG CLAUDE_CODE_VERSION=2.1.316
RUN apk add --no-cache libgcc libstdc++ ripgrep npm \
    && npm install -g "@anthropic-ai/claude-code@${CLAUDE_CODE_VERSION}" \
    && apk del npm

# User/group mapping for host compatibility
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} hostuser \
    && adduser -D -u ${PUID} -G hostuser hostuser

USER hostuser
