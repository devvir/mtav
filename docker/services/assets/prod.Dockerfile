# Static Assets Container — builds the frontend and serves it via nginx.
FROM node:22-alpine AS builder

# Standalone pnpm binary — version-pinned, no npm involved
ARG PNPM_VERSION=10.34.5
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && wget -qO /usr/local/bin/pnpm "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linuxstatic-${arch}" \
    && chmod +x /usr/local/bin/pnpm

WORKDIR /app

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY lang/ lang/
COPY resources/ resources/
COPY vite.config.ts tsconfig.json ./
RUN pnpm run build

# ============================================================================
# Nginx serving only static assets
FROM nginx:1.26-alpine

EXPOSE 80

RUN apk add --no-cache curl
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# All public static files (images, robots.txt, ...)
COPY public/ /usr/share/nginx/html/

COPY docker/services/assets/prod.conf /etc/nginx/conf.d/default.conf

# Built assets (overwrites any existing build directory)
COPY --from=builder /app/public/build /usr/share/nginx/html/build

# Manifest staged for runtime copy into the shared vite-manifest volume,
# where the php service reads it (public/build/manifest.json symlink)
COPY --from=builder /app/public/build/manifest.json /tmp/manifest.json

CMD ["sh", "-c", "cp /tmp/manifest.json /vite_manifest/manifest.json && exec nginx -g 'daemon off;'"]
