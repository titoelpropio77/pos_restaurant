#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

# Assets del panel (Tailwind + Alpine con Vite)
if [ ! -f public/build/manifest.json ]; then
    (npm install --no-audit --no-fund && npm run build) || echo "AVISO: no se pudieron compilar los assets del panel"
fi

echo "Esperando a PostgreSQL en ${DB_HOST}:${DB_PORT}..."
until pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" >/dev/null 2>&1; do
    sleep 1
done

if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    php artisan migrate --force
fi

# Administrador: las migraciones las ejecuta restaurant_pos; se espera su esquema antes de crear la primera cuenta
if [ "${AUTO_SEMBRAR:-false}" = "true" ]; then
    until PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -tAc "select 1 from administradores limit 1" >/dev/null 2>&1; do
        echo "Esperando las migraciones de restaurant_pos..."
        sleep 2
    done
    php artisan db:seed --force
fi

php artisan storage:link >/dev/null 2>&1 || true

exec docker-php-entrypoint "$@"
