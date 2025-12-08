FROM mtav-app

# Create build directory and symlink to shared manifest volume
RUN mkdir -p public/build \
    && ln -sf /var/www/html/vite_manifest/manifest.json public/build/

# Run optimize on startup and then start php-fpm
CMD ["sh", "-c", "php artisan optimize && exec php-fpm"]

