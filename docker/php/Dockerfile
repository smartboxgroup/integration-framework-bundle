FROM composer:2 AS composer

FROM php:8.0-fpm

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -qq \
    libxml2-dev \
    librabbitmq-dev \
    git \
    unzip

RUN docker-php-ext-install \
    soap \
    pcntl \
    sockets

RUN pecl install apcu-5.1.19 \
    mongodb \
    xdebug-3.1.5

RUN docker-php-ext-enable \
    apcu \
    mongodb \
    xdebug

COPY php.ini /usr/local/etc/php/php.ini