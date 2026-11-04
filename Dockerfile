# syntax=docker/dockerfile:1
# Image de PRODUCTION (Coolify) — 3 cibles : builder → php / nginx
# Le dev local utilise docker/php/Dockerfile via compose.yaml.

# ---------------------------------------------------------------------------
# Base : PHP-FPM 8.4 + extensions (partagée par builder et runtime)
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache libpq icu-libs libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        libpq-dev icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql intl opcache zip gd \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Builder : dépendances de prod + compilation des assets
# ---------------------------------------------------------------------------
FROM base AS builder

RUN apk add --no-cache git unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Le secret factice permet de démarrer la console pendant le build ; le vrai est injecté au runtime.
ENV APP_ENV=prod \
    APP_DEBUG=0 \
    APP_SECRET=build-placeholder-replaced-at-runtime

# Couche de dépendances mise en cache tant que composer.* ne change pas
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

RUN composer dump-autoload --no-dev --classmap-authoritative \
 && bin/console assets:install public --no-interaction \
 && bin/console importmap:install \
 && bin/console tailwind:build --minify \
 && bin/console asset-map:compile \
 && rm -rf var/cache var/log

# ---------------------------------------------------------------------------
# PHP-FPM runtime
# ---------------------------------------------------------------------------
FROM base AS php

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY docker/php/prod.ini /usr/local/etc/php/conf.d/zz-prod.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY --from=builder /var/www/html /var/www/html

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Nginx — fichiers statiques embarqués, PHP proxifié vers le service php
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=builder /var/www/html/public /var/www/html/public

EXPOSE 80
