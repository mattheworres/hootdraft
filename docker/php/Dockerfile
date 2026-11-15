FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Run FPM as the host user's uid so var/cache and var/log are writable in the bind mount (Linux hosts)
ARG APP_UID=1000
RUN usermod -u "$APP_UID" www-data && groupmod -g "$APP_UID" www-data

WORKDIR /var/www/html
