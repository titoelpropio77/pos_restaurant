# Mesatek (Laravel + PostgreSQL)

Monolito con dos apps Laravel 13 sobre la misma BD PostgreSQL 17, en Docker: `restaurant_pos/` (POS y panel de
cada restaurante, puerto 8600, servicios `app`/`web`) y `admin/` (sistema administrador, puerto 8601, servicios
`admin`/`admin_web`). El PHP/MySQL original está en `posgourmet/`, sólo referencia: **no modificar**.
Ver `README.md` y `docs/MIGRACION.md`.

- Idioma: tablas, columnas, clases, variables y rutas en inglés. Textos de pantalla y comentarios en español.
- `restaurant_pos` es el único dueño del esquema: toda migración nueva va allí, también las de `admin`.
- Multirestaurante: `restaurants` es el restaurante. Toda tabla de negocio lleva `restaurant_id` NOT NULL y su modelo
  usa `BelongsToRestaurant`. Reglas `exists`/`unique` y `DB::table` no pasan por el scope: usar `Tenant::exists()`,
  `Tenant::unique()` y filtrar a mano. `tests/Feature/MultiRestaurantTest.php` protege esto.
- Usuarios y restaurantes son muchos a muchos (`restaurant_user`): `users` no tiene `restaurant_id` y el restaurante
  activo está en la sesión (`Tenant::SESSION_KEY`, validado por `SetTenant`).
- En `admin` no hay scope: ve todos los restaurantes, y debe fijar `restaurant_id` al crear.
- Autenticación con Fortify y permisos con spatie/laravel-permission. Roles: `superadmin` (guard `admin`), `partner` y
  `cashier` (guard `web`). Un permiso por acción: se agrega en `RolesAndPermissionsSeeder` de la app y se exige con
  `middleware('permission:clave')`; las pruebas fallan si una ruta queda sin permiso sembrado.
- Ejecutar todo dentro del contenedor: `docker compose exec app php artisan ...` (o `admin`).
- Pruebas: `docker compose exec app php artisan test` (BD `mesatek_test`) y `docker compose exec admin php artisan test`
  (BD `mesatek_admin_test`).
- Formato: `docker compose exec app ./vendor/bin/pint` (pint.json excluye `public/legacy/`).
- Marca Mesatek: azul noche `#14213D`, ámbar `#E8961C`, crema `#FAF7F2`, gris pizarra `#4A5468`; Space Grotesk
  (títulos) e Inter (textos), servidas desde `public/assets/fonts/`. Tokens en `resources/css/admin.css` y
  `public/css/pos/pos.css`; logos e íconos en `public/assets/brand/`. Sobre ámbar el texto va en azul noche, nunca blanco.

Lo siguiente aplica a `restaurant_pos` (rutas relativas a esa carpeta):

- El frontend del POS es el jQuery heredado (`public/js/pos/pos.js`): los endpoints de `/pos/*` deben responder
  `{"error": "...", "result": ...}` con HTTP 200 (usar `App\Support\PosResponse`); los valores nulos se devuelven como "".
- El agente de impresión externo (`:8080`) sigue usando nombres heredados: `public/js/pos/print-agent.js` los traduce.
- Errores de negocio: lanzar `App\Exceptions\PosException`; el handler los convierte al contrato JSON.
- Datos de sesión del cajero (sucursal, almacén, tipo de cambio...): `App\Support\PosContext`.
- Panel `/admin` (middleware `admin` y `permission:`): Blade + Tailwind 4 + Alpine (`resources/css/admin.css`,
  `npm run build` dentro del contenedor). Catálogos simples se agregan en `App\Admin\Catalogs`;
  reportes nuevos como clase en `App\Admin\Reports` registrada en `Registry::REPORTS`.
