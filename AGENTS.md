# POS Gourmet (Laravel + PostgreSQL)

Migración del POS para restaurantes PHP/MySQL original (en `posgourmet/`, sólo referencia: **no modificar**)
a Laravel 13 + PostgreSQL 17 en Docker. Ver `README.md` y `docs/MIGRACION.md`.

- Ejecutar todo dentro del contenedor: `docker compose exec app php artisan ...`
- Pruebas: `docker compose exec app php artisan test` (BD `posgourmet_test`).
- Formato: `docker compose exec app ./vendor/bin/pint` (pint.json excluye `posgourmet/` y `public/abms1/`).
- El frontend del POS es el jQuery heredado (`public/js/pos/posgourmet.js`): los endpoints de
  `/pos/*` deben responder `{"error": "...", "result": ...}` con HTTP 200 y los nombres de campo
  legacy (usar `App\Support\RespuestaPos`); los valores nulos se devuelven como "".
- Errores de negocio: lanzar `App\Exceptions\ErrorPos`; el handler los convierte al contrato JSON.
- Datos de sesión del cajero (sucursal, almacén, tipo de cambio...): `App\Support\PosContexto`.
- Panel `/admin` (middleware `admin`): Blade + Tailwind 4 + Alpine (`resources/css/admin.css`,
  `npm run build` dentro del contenedor). Catálogos simples se agregan en `App\Admin\Catalogos`;
  reportes nuevos como clase en `App\Admin\Reportes` registrada en `Registro::REPORTES`.
