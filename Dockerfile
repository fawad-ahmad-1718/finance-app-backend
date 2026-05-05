FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN touch database/database.sqlite
RUN cp .env.example .env
RUN php artisan key:generate --force
RUN php artisan migrate --seed --force

EXPOSE 8000
CMD ["/bin/sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]