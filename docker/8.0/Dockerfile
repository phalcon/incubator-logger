FROM composer:latest as composer
FROM php:8.0-fpm

ENV PHALCON_VERSION='5.2.2' \
    PHP_VERSION='8.0'

RUN apt update -y && \
    apt install -y \
    apt-utils \
    gettext \
    git \
    libpq-dev \
    libzip-dev \
    nano \
    sudo \
    wget \
    zip

# Installing PHP Extensions
RUN pecl install phalcon-${PHALCON_VERSION}

# Enabling PHP Extensions
RUN docker-php-ext-enable \
    opcache \
    phalcon

# Cleanup
RUN apt autoremove -y \
    && apt autoclean -y \
    && apt clean -y

# Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

CMD ["php-fpm"]