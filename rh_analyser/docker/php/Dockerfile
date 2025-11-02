FROM php:8.2-fpm

# Install system dependencies & PostgreSQL support
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-scripts
RUN composer run-script auto-scripts || true

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]