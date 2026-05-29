# ── Stage base (commun prod + dev) ─────────────────────────────────────────
FROM php:8.2.31-fpm AS base

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

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration Nginx
COPY docker/symfony/nginx.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configuration Supervisord
COPY docker/symfony/supervisord.conf /etc/supervisor/conf.d/app.conf

# Désactive l'exposition de la version PHP
COPY docker/symfony/php.ini /usr/local/etc/php/conf.d/security.ini

# Script d'initialisation (supprime CRLF si créé sur Windows)
COPY docker/scripts/init.sh /usr/local/bin/init.sh
RUN sed -i 's/\r$//' /usr/local/bin/init.sh \
    && chmod +x /usr/local/bin/init.sh

# Code source (pour builds standalone sans volume)
COPY backend/ /var/www/html/

# Crée var/ dans l'image (gitignore → absent du COPY) et fixe les droits.
# Indispensable : Docker initialise le volume anonyme /var/www/html/var à partir
# du contenu IMAGE de ce chemin. Sans ce mkdir, le volume est créé vide en root.
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 8000

# ── Stage prod (sans Xdebug, sans dev-dependencies) ────────────────────────
FROM base AS prod
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress
ENTRYPOINT ["/usr/local/bin/init.sh"]

# ── Stage dev (Xdebug activé, dev-dependencies incluses) ───────────────────
FROM base AS dev
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY docker/symfony/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
RUN composer install --no-interaction --optimize-autoloader --no-progress
ENTRYPOINT ["/usr/local/bin/init.sh"]
