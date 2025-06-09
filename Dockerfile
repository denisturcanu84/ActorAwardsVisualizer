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

COPY . .
RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/database \
    && chmod -R 777 /var/www/html/database

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/createdockerenv.sh /createdockerenv.sh
RUN chmod +x /createdockerenv.sh

ENTRYPOINT ["/createdockerenv.sh"]

EXPOSE 80