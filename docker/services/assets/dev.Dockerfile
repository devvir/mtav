# Development container for Node.js and Vite (pnpm)
FROM node:22-alpine

# Standalone pnpm binary — version-pinned, no npm involved
ARG PNPM_VERSION=10.34.5
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && wget -qO /usr/local/bin/pnpm "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linuxstatic-${arch}" \
    && chmod +x /usr/local/bin/pnpm

USER node

WORKDIR /var/www/html

# Expose Vite dev server port
EXPOSE 5173

# Install dependencies and start Vite dev server with host binding for Docker
CMD ["sh", "-c", "pnpm install && pnpm run dev -- --host 0.0.0.0"]
