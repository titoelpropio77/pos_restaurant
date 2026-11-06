-- Base de datos usada por la suite de pruebas (php artisan test)
SELECT 'CREATE DATABASE posgourmet_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'posgourmet_test')\gexec

-- Base de pruebas del administrador (separada para no chocar con las pruebas del POS)
SELECT 'CREATE DATABASE posgourmet_admin_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'posgourmet_admin_test')\gexec
