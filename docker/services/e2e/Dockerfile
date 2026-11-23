# E2E test runner — PHP, Node, Playwright and Chromium in one container.
#
# The official pest-plugin-browser serves the Laravel app in-process and spawns
# `node_modules/.bin/playwright run-server` on localhost, so the PHP test process, node,
# playwright and its browser must share a host.
#
# Everything comes from official images pinned by digest and from Debian's own archive: no
# third-party package source, so the suite does not stop when one is down. Chromium is the
# version the `playwright` devDependency in package.json drives (tests/Arch/PinnedBuildTest).

FROM node:26.12.1-bookworm-slim@sha256:88e480776c6149ff1d098cd304f44bf18057fb5d284a3329b78162d29821e28c AS node

FROM php:8.5.12-cli-bookworm@sha256:050713ce008e2360ea9dc96e1f9a075690132388d917fc3552806f7a7be6f2a0

# Node, with npm, from the official image
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# PHP's extensions, as the release image has them (deploy/Dockerfile); the real GLPK solver
# (lottery execution) and ffmpeg (media handling). A failed fetch is tried again
COPY --from=mlocati/php-extension-installer:2.10.12@sha256:62634540890db292b6c4d6cc123b39aa24033306610ff5b4670d8ed34e847ec4 /usr/bin/install-php-extensions /usr/local/bin/
RUN printf 'Acquire::Retries "5";\n' > /etc/apt/apt.conf.d/80-retries \
    && install-php-extensions bcmath exif gd intl pcntl pdo_mysql sockets zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends glpk-utils ffmpeg git unzip \
    && rm -rf /var/lib/apt/lists/*

# Chromium, with the system libraries it needs, where every user of the image finds it
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
RUN npx --yes playwright@1.64.1 install --with-deps chromium \
    && rm -rf /root/.npm /var/lib/apt/lists/*

COPY --from=composer:2.10.2@sha256:d020706319701a44468968321dccd0fce6620190159a7a9ec195d78e6e971c71 /usr/bin/composer /usr/bin/composer

# Standalone pnpm binary — same pinning discipline as the other images
ARG PNPM_VERSION=12.15.0
RUN arch=$(case "$(uname -m)" in x86_64) echo x64;; aarch64) echo arm64;; esac) \
    && mkdir -p /opt/pnpm \
    && curl -fsSL --retry 5 "https://github.com/pnpm/pnpm/releases/download/v${PNPM_VERSION}/pnpm-linux-${arch}.tar.gz" | tar xz -C /opt/pnpm \
    && ln -s /opt/pnpm/pnpm /usr/local/bin/pnpm

# User/group mapping for host compatibility (bind-mounted repo writes)
ARG PUID=1000
ARG PGID=1000
RUN (getent group ${PGID} || groupadd -g ${PGID} hostuser) \
    && (getent passwd ${PUID} || useradd -m -u ${PUID} -g ${PGID} hostuser)

USER ${PUID}:${PGID}

WORKDIR /var/www/html
