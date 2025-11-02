FROM ghcr.io/devvir/mtav-php-base

# Install PHP dependencies
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

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

# Create storage symlink for public access
RUN php artisan storage:link --force

# Set ownership of entire application to fpm user
RUN chown -R www-data:www-data .

# Run optimize on startup and then start php-fpm
CMD ["sh", "-c", "php artisan optimize && exec php-fpm"]

