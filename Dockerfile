FROM php:8.3-fpm-alpine

# Install required dependencies and PHP extensions
RUN apk update \
    && apk add --no-cache zlib zlib-dev libpng-dev libzip-dev icu-dev linux-headers \
    && docker-php-ext-install gd zip intl pdo pdo_mysql \
    # Install Xdebug
    && apk add --no-cache --virtual .build-deps gcc g++ make autoconf libc-dev \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # Clean up build dependencies
    && apk del .build-deps

WORKDIR /var/www/html
