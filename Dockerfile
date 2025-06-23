FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libsqlite3-dev libxml2-dev libzip-dev unzip zip \
    libwebp-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_sqlite simplexml zip gd \
    && a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ARG TMDB_API_KEY
ARG TMDB_API_BASE_URL
ARG DATABASE_PATH
ARG CSV_PATH
ARG SMTP_HOST
ARG SMTP_PORT
ARG SMTP_USERNAME
ARG SMTP_PASSWORD
ARG SMTP_ENCRYPTION
ARG SMTP_FROM_EMAIL
ARG SMTP_FROM_NAME
ARG APP_NAME
ARG APP_URL

COPY . .
RUN composer install --no-dev --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/database \
    && chmod -R 777 /var/www/html/database \
    && { \
        echo "TMDB_API_KEY=${TMDB_API_KEY}"; \
        echo "TMDB_API_BASE_URL=${TMDB_API_BASE_URL}"; \
        echo "DATABASE_PATH=${DATABASE_PATH}"; \
        echo "CSV_PATH=${CSV_PATH}"; \
        echo "SMTP_HOST=${SMTP_HOST}"; \
        echo "SMTP_PORT=${SMTP_PORT}"; \
        echo "SMTP_USERNAME=${SMTP_USERNAME}"; \
        echo "SMTP_PASSWORD=${SMTP_PASSWORD}"; \
        echo "SMTP_ENCRYPTION=${SMTP_ENCRYPTION}"; \
        echo "SMTP_FROM_EMAIL=${SMTP_FROM_EMAIL}"; \
        if echo "${SMTP_FROM_NAME}" | grep -q " " && ! echo "${SMTP_FROM_NAME}" | grep -q "^\".*\"$"; then \
            echo "SMTP_FROM_NAME=\"${SMTP_FROM_NAME}\""; \
        else \
            echo "SMTP_FROM_NAME=${SMTP_FROM_NAME}"; \
        fi; \
        echo "APP_NAME=${APP_NAME}"; \
        echo "APP_URL=${APP_URL}"; \
    } > /var/www/html/.env \
    && cat /var/www/html/.env

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

ENTRYPOINT ["apache2-foreground"]

EXPOSE 80