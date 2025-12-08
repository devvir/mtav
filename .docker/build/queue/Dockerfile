FROM mtav-app

# Install supervisord
RUN apk add --no-cache supervisor

# Copy supervisord configuration
COPY .docker/build/queue/supervisord.conf /etc/supervisord.conf

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor && chown -R www-data:www-data /var/log/supervisor

# Start supervisord in foreground (required for Docker containers)
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
