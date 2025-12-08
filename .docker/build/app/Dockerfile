FROM ghcr.io/devvir/mtav-php-base

# Install PHP dependencies
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy prod PHP configuration
COPY .docker/build/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Copy entire Laravel application
COPY artisan composer.json composer.lock ./
COPY public/index.php public/

COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY lang/ lang/
COPY packages/ packages/
COPY resources/views/ resources/views/
COPY routes/ routes/

# Copy only essential public files (no images - handled by assets service)
COPY public/robots.txt public/

# Create storage directories
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/views \
        storage/logs

# Set ownership of entire application to www-data
RUN chown -R www-data:www-data .
