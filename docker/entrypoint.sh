#!/bin/sh
set -e

cd /var/www/html

if [ ! -d vendor ]; then
    echo ">> Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f config/jwt/private.pem ]; then
    echo ">> Génération des clés JWT..."
    php bin/console lexik:jwt:generate-keypair --no-interaction
fi

echo ">> Attente de la base de données..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    sleep 1
done

echo ">> Création de la base (si besoin) et migrations..."
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction

echo ">> Démarrage du serveur PHP sur le port 8000"
exec php -S 0.0.0.0:8000 -t public
