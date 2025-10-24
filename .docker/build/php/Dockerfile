# PHP-FPM Only Container (No Assets)
FROM composer:latest AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Production PHP-FPM image
FROM php:8.4-fpm-alpine

LABEL org.opencontainers.image.source=https://github.com/devvir/mtav

# Health check using simple PHP test
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php -r "echo 'OK';" || exit 1

# Install system dependencies for PHP extensions
RUN apk add --no-cache \
        bash \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libxml2 \
        libzip-dev \
        oniguruma-dev \
        zip \
        zlib-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        exif \
        pcntl \
        bcmath \
        gd \
        zip

# Copy prod PHP configuration
COPY .docker/build/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Copy pre-generated production .env
COPY .docker/build/.env.prod .env

COPY artisan composer.json composer.lock ./
COPY public/index.php public/
COPY resources/views/app.blade.php resources/views/

COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY lang/ lang/
COPY packages/ packages/
COPY routes/ routes/

# Copy vendor dependencies from composer stage
COPY --from=dependencies /app/vendor ./vendor

# Copy only essential public files (no images - handled by assets service)
COPY public/robots.txt public/

# Create build directory and symlink to shared manifest volume
RUN mkdir -p public/build \
    && ln -sf /var/www/html/vite_manifest/manifest.json public/build/

# Create storage directories
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/views \
        storage/logs

# Set ownership of entire application to fpm user
RUN chown -R www-data:www-data .

# Run optimize on startup and then start php-fpm
CMD ["sh", "-c", "php artisan optimize && exec php-fpm"]

