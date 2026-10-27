# Development container for Node.js and Vite (pnpm)
FROM node:26-alpine

# Standalone pnpm binary — version-pinned, no npm involved
ARG PNPM_VERSION=12.15.0
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && mkdir -p /opt/pnpm \
    && wget -qO- "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linux-${arch}-musl.tar.gz" | tar xz -C /opt/pnpm \
    && ln -s /opt/pnpm/pnpm /usr/local/bin/pnpm

USER node

WORKDIR /var/www/html

# Expose Vite dev server port
EXPOSE 5173

# Install dependencies and start Vite dev server with host binding for Docker
CMD ["sh", "-c", "pnpm install && pnpm run dev -- --host 0.0.0.0"]
