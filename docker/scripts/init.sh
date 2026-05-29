#!/bin/bash
set -e

cd /var/www/html

echo "--- [1/6] Attente de MySQL ---"
until php -r "
try {
    new PDO('mysql:host=mysql_db;port=3306;dbname=aquiplants_db', 'aquiplants', 'aquiplants', [PDO::ATTR_TIMEOUT => 3]);
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    echo "  MySQL pas encore prêt, nouvel essai dans 3s..."
    sleep 3
done
echo "  MySQL OK"

echo "--- [2/6] Installation Composer ---"
composer install --no-interaction --optimize-autoloader --no-progress

echo "--- [3/6] Génération des clés JWT ---"
if [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    openssl genpkey -algorithm RSA -out config/jwt/private.pem \
        -aes256 -pass pass:"${JWT_PASSPHRASE}"
    openssl pkey -in config/jwt/private.pem \
        -passin pass:"${JWT_PASSPHRASE}" \
        -out config/jwt/public.pem -pubout
    chown -R www-data:www-data config/jwt
    chmod 640 config/jwt/private.pem
    chmod 644 config/jwt/public.pem
    echo "  Clés JWT générées"
else
    echo "  Clés JWT déjà présentes"
fi

echo "--- [4/6] Migrations Doctrine ---"
if [ -n "${DATABASE_URL_MIGRATIONS}" ]; then
    DATABASE_URL="${DATABASE_URL_MIGRATIONS}" php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

echo "--- [5/6] Fixtures ---"
USER_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as c FROM utilisateur" --no-interaction 2>/dev/null | grep -oE '[0-9]+' | head -1 || echo "0")
if [ "${USER_COUNT}" = "0" ]; then
    php bin/console doctrine:fixtures:load --no-interaction
    echo "  Fixtures chargées"
else
    echo "  Fixtures déjà présentes (${USER_COUNT} utilisateurs)"
fi

echo "--- [6/6] Démarrage PHP-FPM + Nginx ---"
mkdir -p /var/www/html/var/cache /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var
chmod -R ug+rwX /var/www/html/var
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf
