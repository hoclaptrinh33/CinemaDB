# ─── Stage 1: Build frontend assets ──────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile --legacy-peer-deps

COPY . .
RUN npm run build


# ─── Stage 2: Production PHP image ────────────────────────────────────────────
FROM php:8.2-fpm-bookworm

# Install nginx, supervisor, and required system libraries in a single layer
# (apt-get update + install in same RUN avoids stale cache issues)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by this Laravel app
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
    pdo pdo_mysql \
    mbstring \
    xml \
    zip \
    bcmath \
    gd \
    opcache \
    pcntl \
    sockets

# Install Redis PHP extension from PECL
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Pull Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Install PHP dependencies (separate layer for Docker cache) ─────────────────
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --optimize-autoloader

# ── Copy application source ────────────────────────────────────────────────────
# (vendor/ is excluded via .dockerignore so the container-built vendor/ is kept)
COPY . .

# ── Copy built frontend assets from Stage 1 ───────────────────────────────────
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
