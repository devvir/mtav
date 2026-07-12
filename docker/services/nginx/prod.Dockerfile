# Production Nginx for MTAV — pure proxy (php-fpm + assets service)
FROM nginx:1.26-alpine

# Health check - verify nginx is ready to serve requests
RUN apk add --no-cache curl
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl http://localhost -o /dev/null || exit 1

RUN rm -rf /usr/share/nginx/html/*

COPY docker/services/nginx/prod.conf /etc/nginx/conf.d/default.conf
