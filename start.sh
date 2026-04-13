#!/usr/bin/env bash
set -e

# Start Laravel backend
cd backend

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run migrations
php artisan migrate --force

# Run seeders to populate initial data
php artisan db:seed --force

php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
