# Mesatek (Laravel + PostgreSQL)

Monolito con dos apps Laravel 13 sobre la misma BD PostgreSQL 17, en Docker: `restaurant_pos/` (POS y panel de
cada restaurante, puerto 8600, servicios `app`/`web`) y `administrador/` (sistema administrador, puerto 8601,
servicios `admin`/`admin_web`). El PHP/MySQL original está en `posgourmet/`, sólo referencia: **no modificar**.
Ver `README.md` y `docs/MIGRACION.md`.

- `restaurant_pos` es el único dueño del esquema: toda migración nueva va allí, también las del administrador.
- Multirestaurante: `empresas` es el restaurante. Toda tabla de negocio lleva `empresa_id` NOT NULL y su modelo usa
  `PerteneceAEmpresa`. Reglas `exists`/`unique` y `DB::table` no pasan por el scope: usar `Restaurante::existe()`,
  `Restaurante::unico()` y filtrar a mano. `tests/Feature/MultiRestauranteTest.php` protege esto.
- En `administrador` no hay scope: ve todos los restaurantes, y debe fijar `empresa_id` al crear. Contraseñas de
  `usuarios` en mayúsculas antes del hash (el POS compara así; lo hace `App\Models\Usuario`).

Lo siguiente aplica a `restaurant_pos` (rutas relativas a esa carpeta):

- Ejecutar todo dentro del contenedor: `docker compose exec app php artisan ...`
- Pruebas: `docker compose exec app php artisan test` (BD `posgourmet_test`); administrador: `docker compose exec admin php artisan test`.
- Formato: `docker compose exec app ./vendor/bin/pint` (pint.json excluye `public/abms1/`).
- El frontend del POS es el jQuery heredado (`public/js/pos/posgourmet.js`): los endpoints de
  `/pos/*` deben responder `{"error": "...", "result": ...}` con HTTP 200 y los nombres de campo
  legacy (usar `App\Support\RespuestaPos`); los valores nulos se devuelven como "".
- Errores de negocio: lanzar `App\Exceptions\ErrorPos`; el handler los convierte al contrato JSON.
- Datos de sesión del cajero (sucursal, almacén, tipo de cambio...): `App\Support\PosContexto`.
- Panel del restaurante `/admin` (middleware `admin` y `permiso:`): Blade + Tailwind 4 + Alpine (`resources/css/admin.css`,
  `npm run build` dentro del contenedor). Catálogos simples se agregan en `App\Admin\Catalogos`;
  reportes nuevos como clase en `App\Admin\Reportes` registrada en `Registro::REPORTES`.
- Marca Mesatek: azul noche `#14213D`, ámbar `#E8961C`, crema `#FAF7F2`, gris pizarra `#4A5468`; Space Grotesk
  (títulos) e Inter (textos), servidas desde `public/assets/fuentes/`. Tokens en `resources/css/admin.css` y
  `public/css/pos/pos.css`; logos e íconos en `public/assets/marca/`. Sobre ámbar el texto va en azul noche, nunca blanco.
- `posgourmet` sobrevive sólo como identificador técnico (BD, contenedores, `config/posgourmet.php`, `posgourmet.js`);
  lo visible para el usuario es Mesatek.
