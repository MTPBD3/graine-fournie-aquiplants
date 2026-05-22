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

WORKDIR /var/www/html

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/init.sh"]
