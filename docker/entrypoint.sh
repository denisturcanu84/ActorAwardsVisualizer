#!/bin/sh
set -e

if [ -d "/var/www/html/database" ]; then
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
fi

exec "$@"
