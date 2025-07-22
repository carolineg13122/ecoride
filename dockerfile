FROM php:8.2-apache

# Installe les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copie les fichiers dans le conteneur
COPY . /var/www/html/

# Donne les bons droits à Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
