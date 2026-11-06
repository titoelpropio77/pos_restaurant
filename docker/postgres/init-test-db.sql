-- Base de datos usada por la suite de pruebas (php artisan test)
SELECT 'CREATE DATABASE mesatek_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'mesatek_test')\gexec

-- Base de pruebas del administrador (separada para no chocar con las pruebas del POS)
SELECT 'CREATE DATABASE mesatek_admin_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'mesatek_admin_test')\gexec
