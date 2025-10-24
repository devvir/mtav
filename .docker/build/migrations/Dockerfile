# Laravel Migrations Service
FROM php:8.4-cli-alpine

# Install essential PHP extensions for Laravel
RUN apk add --no-cache libzip-dev \
    && docker-php-ext-install pdo_mysql zip bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies (intentionally including dev deps)
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install --no-interaction --no-scripts

# Copy pre-generated production .env
COPY .docker/build/.env.prod .env

COPY artisan ./

COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY routes/ routes/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Switch to www-data user
USER www-data

# Run migrations with force flag (no interaction needed)
CMD ["php", "artisan", "migrate", "--force"]