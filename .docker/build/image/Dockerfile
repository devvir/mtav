FROM php:8.4-fpm-alpine

LABEL org.opencontainers.image.source=https://github.com/devvir/mtav-deploy

# Health check using simple PHP test
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r "echo 'OK';" || exit 1

# Install system dependencies for PHP extensions
RUN apk add --no-cache \
        bash \
        ffmpeg \
        freetype-dev \
        git \
        glpk glpk-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libxml2 \
        libzip-dev \
        oniguruma-dev \
        zip \
        zlib-dev

# Install PHP extensions (linux-headers needed only for sockets compilation)
RUN apk add --no-cache --virtual .build-deps linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        sockets \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer