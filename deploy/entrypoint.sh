#!/bin/sh
# Every role starts here: Laravel's caches are built from this environment's settings
# (they cannot be built into the image, which serves every environment), then the role's
# command runs.
set -e

php artisan optimize --quiet

exec "$@"
