FROM php:8.2-apache

# Activation de mod_rewrite
RUN a2enmod rewrite

# Installation des extensions nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copie des fichiers du projet
COPY . /var/www/html/

# Droits
RUN chown -R www-data:www-data /var/www/html

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
