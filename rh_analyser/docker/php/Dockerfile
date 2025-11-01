FROM php:8.2-fpm

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Installer les dépendances Symfony avec APP_ENV=prod pour éviter de charger les bundles dev
RUN APP_ENV=prod composer install --no-interaction --optimize-autoloader --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
