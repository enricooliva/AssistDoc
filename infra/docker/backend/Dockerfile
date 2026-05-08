FROM php:8.2-cli

WORKDIR /app/backend

RUN apt-get update \
    && apt-get install -y git unzip libzip-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite

COPY backend /app/backend

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
