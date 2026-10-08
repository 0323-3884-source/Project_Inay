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
# Clear only local compiled files. optimize:clear also touches the database
# cache, which does not exist yet on a new installation.
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Bring the schema up to date before database sessions/cache are used.
case "${RUN_MIGRATIONS:-true}" in
    true|1)
        migration_attempt=1
        until php artisan migrate --force --no-interaction; do
            if [ "$migration_attempt" -ge 5 ]; then
                echo "Database migrations failed after 5 attempts; refusing to start." >&2
                exit 1
            fi
            migration_attempt=$((migration_attempt + 1))
            echo "Retrying migrations (attempt ${migration_attempt}/5)..." >&2
            sleep 2
        done
        ;;
    false|0)
        echo "Startup migrations disabled; database schema must already be current."
        ;;
    *)
        echo "RUN_MIGRATIONS must be true, false, 1, or 0." >&2
        exit 1
        ;;
esac

php artisan config:cache
php artisan view:cache


# ==============================
# Storage symlink
# ==============================
if [ ! -e public/storage ] || [ -L public/storage ]; then
    php artisan storage:link --force
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
