#!/bin/sh
set -e

if [ "$1" = "php-fpm" ]; then
    echo "[entrypoint] Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    echo "[entrypoint] Warming up cache..."
    php bin/console cache:clear --no-warmup
    php bin/console cache:warmup

    # Les commandes ci-dessus tournent en root, mais PHP-FPM écrit en www-data
    # (caches applicatifs, fichiers envoyés depuis l'admin dans var/storage).
    mkdir -p var/storage
    chown -R www-data:www-data var
fi

exec "$@"
