#!/bin/sh
set -e
# creeaza automat .env din docker-compose
echo "TMDB_API_KEY=${TMDB_API_KEY}" > /var/www/html/.env
echo "TMDB_API_BASE_URL=https://api.themoviedb.org/3" >> /var/www/html/.env
echo "DATABASE_PATH=database/app.db" >> /var/www/html/.env
echo "CSV_PATH=csv/screen_actor_guild_awards_updated.csv" >> /var/www/html/.env

exec apache2-foreground