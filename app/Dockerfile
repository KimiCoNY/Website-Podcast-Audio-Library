FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads
