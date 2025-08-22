#!/bin/bash
set -e

# Only run key:generate if APP_KEY is empty
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

php artisan migrate --force
php artisan l5-swagger:generate

exec "$@"
