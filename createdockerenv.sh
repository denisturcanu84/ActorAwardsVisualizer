#!/bin/sh
set -e
# creeaza automat .env din docker-compose
echo "TMDB_API_KEY=${TMDB_API_KEY}" > /var/www/html/.env

exec apache2-foreground