#!/bin/sh
set -e

# Create storage directories if not exist
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/evidence_originals
mkdir -p /var/www/html/storage/app/evidence_derivatives
mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions (777 for development)
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache

# If command is artisan or php, run it directly
if [ "$1" = "php" ] || [ "$1" = "artisan" ] || [ "$1" = "php-fpm" ]; then
    exec "$@"
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    if ! grep -q "^APP_KEY=" /var/www/html/.env || grep -q "^APP_KEY=$" /var/www/html/.env; then
        echo "Generating APP_KEY..."
        cd /var/www/html && php artisan key:generate --quiet 2>/dev/null || true
    fi
fi

# Default: start PHP-FPM
exec php-fpm
