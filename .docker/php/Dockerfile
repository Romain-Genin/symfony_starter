FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl rsync \
    libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libgd-dev \
    zip unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY .docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

RUN groupmod -o -g 1000 www-data && usermod -o -u 1000 www-data

USER www-data
