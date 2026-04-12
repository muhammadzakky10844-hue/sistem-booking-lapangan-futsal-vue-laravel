FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:clear || true; php artisan route:clear || true; php artisan view:clear || true; php artisan migrate --force || true; php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
