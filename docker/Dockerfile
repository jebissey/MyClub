FROM php:8.4-apache

# System dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        libsqlite3-dev \
        unzip \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Copy MyClub
COPY WebSite/ .

# Install production dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Apache configuration
COPY docker/apache/myclub.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite headers \
    && mkdir -p data backup var/tracy/log var/latte/temp \
    && chown -R www-data:www-data data backup var

EXPOSE 80
