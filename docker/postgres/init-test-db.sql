-- Base de datos usada por la suite de pruebas (php artisan test)
SELECT 'CREATE DATABASE posgourmet_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'posgourmet_test')\gexec
