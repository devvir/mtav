# Production Nginx for MTAV
FROM nginx:1.26-alpine

LABEL org.opencontainers.image.source=https://github.com/devvir/mtav

# Health check - verify nginx is ready to serve requests
RUN apk add --no-cache curl
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl http://localhost -o /dev/null || exit 1

# Remove default files
RUN rm -rf /usr/share/nginx/html/*

# Copy nginx configuration
COPY .docker/build/nginx/nginx.conf /etc/nginx/conf.d/default.conf