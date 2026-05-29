FROM php:8.2-fpm

# Dépendances système
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    unzip \
    curl \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        opcache \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Xdebug (mode coverage uniquement, pas de surcoût en prod/dev)
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY docker/symfony/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration Nginx
COPY docker/symfony/nginx.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configuration Supervisord
COPY docker/symfony/supervisord.conf /etc/supervisor/conf.d/app.conf

# Script d'initialisation (supprime CRLF si créé sur Windows)
COPY docker/scripts/init.sh /usr/local/bin/init.sh
RUN sed -i 's/\r$//' /usr/local/bin/init.sh \
    && chmod +x /usr/local/bin/init.sh

# Code source (pour builds standalone sans volume)
COPY backend/ /var/www/html/

# Crée var/ dans l'image (gitignore → absent du COPY) et fixe les droits.
# Docker initialise le volume anonyme /var/www/html/var à partir du contenu
# IMAGE. Sans ce mkdir, le volume est créé vide en root:root.
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

# Pré-installe les dépendances (vendor baked dans l'image, volume anonyme initialisé)
RUN composer install --no-interaction --optimize-autoloader --no-progress

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/init.sh"]
