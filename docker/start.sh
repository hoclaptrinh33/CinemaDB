#!/bin/bash
set -e

# ── Create public/storage symlink ─────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Cache configuration, routes, and views for production ─────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Start supervised processes (nginx + php-fpm) ──────────────────────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
