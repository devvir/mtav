FROM ghcr.io/devvir/mtav-php-base

# Install PHP dependencies
COPY composer.json composer.lock ./
COPY packages/ packages/
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

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