#!/usr/bin/env bash
set -e

# Start Laravel backend
cd backend

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan migrate --force || true

php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
