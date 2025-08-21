#!/bin/bash
# wait-for-mysql.sh

set -e

host="$1"
shift
cmd="$@"

echo "Waiting for MySQL at $host:3306..."

until mysql -h"$host" -uroot -p"$MYSQL_ROOT_PASSWORD" -e 'SELECT 1' >/dev/null 2>&1; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

>&2 echo "MySQL is up - executing command"

# Run migrations
php artisan migrate --force

exec $cmd