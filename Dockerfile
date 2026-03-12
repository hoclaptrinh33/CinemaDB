# ─── Stage 1: PHP vendor dependencies ────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --ignore-platform-reqs \
        --optimize-autoloader


# ─── Stage 2: Build frontend assets ──────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --legacy-peer-deps

COPY . .
# Ziggy reads vendor/tightenco/ziggy at build time — copy from composer stage
COPY --from=vendor /app/vendor ./vendor
RUN npm run build


# ─── Stage 3: Production PHP image ────────────────────────────────────────────
FROM php:8.2-fpm-bookworm

# Install nginx, supervisor, system libs + PHP extensions via install-php-extensions
# (much faster than docker-php-ext-install — uses pre-built binaries)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions \
    && apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        bcmath \
        gd \
        opcache \
        pcntl \
        sockets \
        redis

# Pull Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Copy vendor from composer stage ───────────────────────────────────────────
COPY --from=vendor /app/vendor ./vendor

# ── Copy application source ────────────────────────────────────────────────────
COPY . .

# ── Copy built frontend assets from Stage 2 ───────────────────────────────────
COPY --from=assets /app/public/build ./public/build

# ── Finalise Composer autoloader and run package discovery ────────────────────
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# ── PHP / OPcache configuration ───────────────────────────────────────────────
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# ── Nginx configuration ───────────────────────────────────────────────────────
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# ── Supervisor configuration ──────────────────────────────────────────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Startup script ────────────────────────────────────────────────────────────
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# ── File permissions ──────────────────────────────────────────────────────────
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
