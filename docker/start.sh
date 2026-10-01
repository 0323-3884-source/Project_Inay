#!/bin/sh
set -eu

cd /var/www/html

: "${APP_KEY:?Set a permanent APP_KEY in your hosting environment before starting the service.}"
PORT="${PORT:-8080}"
case "$PORT" in
    ''|*[!0-9]*) echo 'PORT must be a number.' >&2; exit 1 ;;
esac
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo 'PORT must be between 1 and 65535.' >&2
    exit 1
fi

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Mount persistent storage/app, leaving framework caches inside the container.
mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Build configuration from this deployment's environment, never a local .env.
php artisan config:cache
php artisan view:cache
php artisan storage:link
chown -R www-data:www-data storage/framework bootstrap/cache

# Run database migrations on deployment by default (can be disabled with RUN_MIGRATIONS=false)
if [ "${RUN_MIGRATIONS:-true}" = 'true' ]; then
    echo "Running database migrations..."
    n=0
    until [ "$n" -ge 5 ]
    do
        if php artisan migrate --force; then
            echo "Database migrations completed successfully."
            break
        fi
        n=$((n+1))
        echo "Database migration failed or not ready yet. Retrying in 2 seconds (attempt $n/5)..."
        sleep 2
    done
fi

exec docker-php-entrypoint "$@"
