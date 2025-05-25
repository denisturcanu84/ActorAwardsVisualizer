FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libsqlite3-dev libxml2-dev libzip-dev unzip zip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_sqlite simplexml zip \
<<<<<<< HEAD
    && a2enmod rewrite
=======
    && a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
>>>>>>> 5f5134b2def65d5980cbb7ce39f7f8f3f6844c6c

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html

COPY createdockerenv.sh /createdockerenv.sh
RUN chmod +x /createdockerenv.sh

ENTRYPOINT ["/createdockerenv.sh"]

EXPOSE 80