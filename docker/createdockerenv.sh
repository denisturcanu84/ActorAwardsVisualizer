#!/bin/sh
set -e

# Create .env file from docker-compose environment variables
cat > /var/www/html/.env << EOL
TMDB_API_KEY=${TMDB_API_KEY}
TMDB_API_BASE_URL=${TMDB_API_BASE_URL}
DATABASE_PATH=${DATABASE_PATH}
CSV_PATH=${CSV_PATH}
SMTP_HOST=${SMTP_HOST}
SMTP_PORT=${SMTP_PORT}
SMTP_USERNAME=${SMTP_USERNAME}
SMTP_PASSWORD="${SMTP_PASSWORD}"
SMTP_ENCRYPTION=${SMTP_ENCRYPTION}
SMTP_FROM_EMAIL=${SMTP_FROM_EMAIL}
SMTP_FROM_NAME="${SMTP_FROM_NAME}"
APP_NAME="${APP_NAME}"
APP_URL=${APP_URL}
EOL

# Ensure database directory exists and has correct permissions
mkdir -p /var/www/html/database
chown -R www-data:www-data /var/www/html/database
chmod -R 755 /var/www/html/database

# Start Apache
exec apache2-foreground
