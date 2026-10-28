# MTAV's release image: the whole app in one image, the same in every environment.
#
# One image runs every role, chosen by its command (deploy/compose.yml): the web server
# (FrankenPHP: Caddy and PHP in one process, serving pages and built assets), the
# websocket server (Reverb) and the queue worker. Everything that differs between
# environments comes from the environment at run time, never from the build.
#
# Build context: the repository root.

# ---------------------------------------------------------------------------------------
# The frontend, built once
FROM node:26-alpine AS assets

ARG PNPM_VERSION=12.15.0
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && mkdir -p /opt/pnpm \
    && wget -qO- "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linux-${arch}-musl.tar.gz" | tar xz -C /opt/pnpm \
    && ln -s /opt/pnpm/pnpm /usr/local/bin/pnpm

WORKDIR /app
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile --ignore-scripts

COPY vite.config.ts tsconfig.json ./
COPY resources/ resources/
COPY lang/ lang/
RUN pnpm run build

# ---------------------------------------------------------------------------------------
# The app
FROM dunglas/frankenphp:1.13.4-php8.5.12-alpine AS app

# What the app calls besides PHP: glpsol solves the lottery, ffmpeg makes video thumbnails
RUN apk add --no-cache ffmpeg glpk \
    && install-php-extensions bcmath exif gd intl opcache pcntl pdo_mysql sockets zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY deploy/php.ini $PHP_INI_DIR/conf.d/99-mtav.ini

WORKDIR /app

# Dependencies first, so that a code change rebuilds only what follows
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY artisan ./
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY lang/ lang/
COPY public/ public/
COPY resources/views/ resources/views/
COPY routes/ routes/
COPY --from=assets /app/public/build public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && mkdir -p storage/app/public storage/app/media storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage \
    && rm /usr/bin/composer

COPY deploy/entrypoint.sh /usr/local/bin/mtav-entrypoint
# How a release deploys itself (taken out of the image by the host's gate)
COPY deploy/compose.yml deploy/slot.yml deploy/
COPY deploy/switch/ deploy/switch/
COPY deploy/host/deploy deploy/host/

# Unprivileged: the web server listens on 8080, behind the environment's switch
RUN adduser -D -u 1000 mtav \
    && chown -R mtav:mtav storage bootstrap/cache /data/caddy /config/caddy
USER mtav

ENV SERVER_NAME=:8080 \
    SERVER_ROOT=/app/public \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 8080
ENTRYPOINT ["mtav-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
