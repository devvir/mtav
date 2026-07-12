# MTAV PHP — multi-stage image
#
# Stages:
#   base   — php-fpm + extensions + composer (shared foundation)
#   dev    — base + xdebug + node/pnpm + host user mapping (code bind-mounted)
#   prod   — base + vendored app code, optimized (self-contained image)
#   queue  — prod + supervisord running queue workers
#
# Build context is the repository root.

# ============================================================================
FROM php:8.4-fpm-alpine AS base

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ============================================================================
FROM base AS dev

# Xdebug (coverage, debugging) + Node runtime for artisan tooling
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && apk add --no-cache nodejs \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Standalone pnpm binary — version-pinned, no npm involved
ARG PNPM_VERSION=10.34.5
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && wget -qO /usr/local/bin/pnpm "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linuxstatic-${arch}" \
    && chmod +x /usr/local/bin/pnpm

# User/group mapping for host compatibility
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} hostuser \
    && adduser -D -u ${PUID} -G hostuser hostuser

USER hostuser

# ============================================================================
FROM base AS prod

COPY docker/services/php/prod.ini /usr/local/etc/php/conf.d/99-custom.ini

# Install PHP dependencies first (better layer caching)
COPY composer.json composer.lock artisan ./
COPY packages/ packages/
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY lang/ lang/
COPY resources/views/ resources/views/
COPY routes/ routes/
COPY public/index.php public/robots.txt public/

# Storage skeleton + vite manifest symlink (manifest arrives via shared volume,
# published by the assets container at startup)
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        public/build \
    && ln -sf /var/www/html/vite_manifest/manifest.json public/build/ \
    && chown -R www-data:www-data .

CMD ["sh", "-c", "php artisan optimize && exec php-fpm"]

# ============================================================================
FROM prod AS queue

RUN apk add --no-cache supervisor \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor

COPY docker/services/queue/supervisord.conf /etc/supervisord.conf

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
