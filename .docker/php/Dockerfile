FROM ghcr.io/devvir/mtav-php-base

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# User/group mapping for host compatibility
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} hostuser \
    && adduser -D -u ${PUID} -G hostuser hostuser

USER hostuser