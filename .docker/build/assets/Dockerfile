# Static Assets Container
FROM node:22-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY lang/ lang/
COPY resources/ resources/
COPY vite.config.ts tsconfig.json ./
RUN npm run build

# Nginx serving only static assets
FROM nginx:1.26-alpine

LABEL org.opencontainers.image.source=https://github.com/devvir/mtav

EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Install curl for health checks
RUN apk add --no-cache curl

# Copy all static files from public directory
COPY public/ /usr/share/nginx/html/

# Create simple nginx config for static files only
COPY .docker/build/assets/nginx.conf /etc/nginx/conf.d/default.conf

# Copy built assets (this will overwrite any existing build directory)
COPY --from=builder /app/public/build /usr/share/nginx/html/build

# Store manifest in temporary location for runtime copying to volume
COPY --from=builder /app/public/build/manifest.json /tmp/manifest.json

# Copy manifest to volume and start nginx
CMD ["sh", "-c", "cp /tmp/manifest.json /vite_manifest/manifest.json && exec nginx -g 'daemon off;'"]