FROM php:8.2-fpm-alpine

# Install necessary system packages and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    curl \
    postgresql-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    zip \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/var

# Create cache directory
RUN mkdir -p /var/www/html/var/cache && \
    chmod -R 777 /var/www/html/var/cache

EXPOSE 9000

CMD ["php-fpm"]
