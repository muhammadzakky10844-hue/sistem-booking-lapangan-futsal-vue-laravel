#!/usr/bin/env bash
set -e

cd backend

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan migrate --force || true
if [ "${AUTO_SEED:-false}" = "true" ]; then
	php artisan db:seed --force || true
fi

php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
