FROM ghcr.io/devvir/mtav-php-base

# Install Xdebug for development (coverage, debugging)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# User/group mapping for host compatibility
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} hostuser \
    && adduser -D -u ${PUID} -G hostuser hostuser

USER hostuser