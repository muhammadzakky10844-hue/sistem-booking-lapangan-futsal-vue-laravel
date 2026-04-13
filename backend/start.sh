#!/usr/bin/env bash
set -e

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan migrate --force || true
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force || true

php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
