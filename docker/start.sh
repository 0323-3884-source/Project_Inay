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

# Optional fallback for platforms without a pre-deploy command. Never seed or reset data.
if [ "${RUN_MIGRATIONS:-false}" = 'true' ]; then
    php artisan migrate --force
fi

exec docker-php-entrypoint "$@"
