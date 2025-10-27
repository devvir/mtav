FROM ghcr.io/devvir/mtav-php-base

# User/group mapping for host compatibility
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} hostuser \
    && adduser -D -u ${PUID} -G hostuser hostuser

USER hostuser