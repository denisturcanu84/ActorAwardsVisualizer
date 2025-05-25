FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libsqlite3-dev libxml2-dev libzip-dev unzip zip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_sqlite simplexml zip \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html

COPY createdockerenv.sh /createdockerenv.sh
RUN chmod +x /createdockerenv.sh

ENTRYPOINT ["/createdockerenv.sh"]

EXPOSE 80