FROM node:22-alpine AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY postcss.config.js tailwind.config.js vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM composer:2.9.7 AS vendor

WORKDIR /build

COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

FROM php:8.3-fpm-bookworm

ARG APP_ENV=local
ENV APP_ENV=${APP_ENV}

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libonig-dev \
        libsqlite3-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/pepiniere-entrypoint

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /build/vendor ./vendor
COPY --from=frontend /build/public/build ./public/build

RUN cp .env.example .env \
    && composer dump-autoload --no-interaction --optimize \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data bootstrap/cache storage

RUN chmod +x /usr/local/bin/pepiniere-entrypoint

ENTRYPOINT ["pepiniere-entrypoint"]

EXPOSE 9000

CMD ["php-fpm"]
