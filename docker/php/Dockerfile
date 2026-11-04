# Image PHP-FPM de DÉVELOPPEMENT (utilisée par compose.yaml).
# La prod est construite par le Dockerfile à la racine (Coolify).
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        zip \
        unzip \
        curl \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        zip \
        gd \
    && pecl install apcu xdebug \
    && docker-php-ext-enable apcu xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

# Même UID que l'utilisateur de la machine hôte (Linux) pour éviter les soucis de droits sur var/.
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} www-data

ENV COMPOSER_HOME=/tmp/composer \
    XDEBUG_MODE=off

WORKDIR /var/www/html

USER www-data
