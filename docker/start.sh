#!/bin/sh
set -eu

cd /var/www/html

echo "Starting Project INAY..."

# Make sure APP_KEY exists
: "${APP_KEY:?APP_KEY is required. Set APP_KEY in Railway Variables.}"

# Railway port
PORT="${PORT:-8080}"

case "$PORT" in
    ''|*[!0-9]*)
        echo "PORT must be a number." >&2
        exit 1
        ;;
esac

if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo "PORT must be between 1 and 65535." >&2
    exit 1
fi

echo "Using PORT: ${PORT}"


# ==============================
# Force correct Apache MPM
# ==============================
a2dismod mpm_event >/dev/null 2>&1 || true
a2dismod mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
a2enmod rewrite >/dev/null 2>&1 || true


# ==============================
# Railway Apache port
# ==============================
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

sed -i \
    "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf


# ==============================
# Laravel directories
# ==============================
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache


chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache


# ==============================
# Laravel caches
# ==============================
php artisan optimize:clear
php artisan config:cache
php artisan view:cache


# ==============================
# Storage symlink
# ==============================
if [ ! -e public/storage ]; then
    php artisan storage:link
else
    echo "public/storage already exists - skipping storage:link."
fi


chown -R www-data:www-data storage bootstrap/cache


# ==============================
# Verify Apache configuration
# ==============================
echo "Checking Apache configuration..."

apache2ctl configtest

echo "Apache configuration OK."
echo "Starting Apache on port ${PORT}..."


# Start official PHP Apache entrypoint
exec docker-php-entrypoint "$@"