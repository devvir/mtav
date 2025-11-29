# PHP container with Node.js for E2E testing
FROM dev-php

# Install Node.js and npm
USER root
RUN apk add --no-cache nodejs npm

# Copy entrypoint script
COPY --chown=hostuser:hostuser .docker/php-e2e/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER hostuser

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["tail", "-f", "/dev/null"]
