FROM php:8.2-apache

# Installe les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copie tes fichiers dans le conteneur
COPY . /var/www/html/

# Donne les bons droits à Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
