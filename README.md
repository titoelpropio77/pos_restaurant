# Mesatek — Laravel + PostgreSQL

Sistema de punto de venta multirestaurante, migrado del PHP/MySQL original
(`posgourmet/posgourmet/`) a **Laravel 13 + PostgreSQL 17**, todo dockerizado.

> La carpeta `posgourmet/` contiene el sistema original **sin modificaciones**, como referencia.
> No se publica por el servidor web y no forma parte de ninguna aplicación.

## Estructura del monolito

Dos aplicaciones Laravel, cada una con su login, sobre **la misma base de datos**:

| Carpeta           | Para quién                 | Contenedores          | URL                   |
|-------------------|----------------------------|-----------------------|-----------------------|
| `restaurant_pos/` | Dueños y vendedores        | `app` + `web`         | http://localhost:8600 |
| `admin/`          | Administradores de Mesatek | `admin` + `admin_web` | http://localhost:8601 |

`restaurant_pos` es el **único dueño del esquema**: las migraciones viven allí y se ejecutan solas
al iniciar `app`. `admin` no tiene migraciones; sus modelos usan las mismas tablas.

El código, las tablas y las columnas están en inglés. **Los textos que ve el usuario siguen en español**,
igual que los comentarios.

## Puesta en marcha

Requisitos: Docker y Docker Compose.

```bash
docker compose up -d --build                  # levanta todo
docker compose exec app php artisan db:seed   # restaurante y datos demo (sólo la primera vez)
```

PostgreSQL queda en el puerto **5436** (BD `mesatek`, usuario/clave `mesatek`).

| Aplicación | Usuario               | Contraseña  | Rol                |
|------------|-----------------------|-------------|--------------------|
| POS        | `admin@mesatek.local` | `admin`     | Partner            |
| POS        | `partner@mesatek.local` | `partner` | Partner (demo)     |
| POS        | `cajero@mesatek.local` | `cajero`   | Cajero (demo)      |
| Admin      | `admin@mesatek.local` | `admin1234` | Superadmin         |

La cuenta del admin se crea sola al iniciar el contenedor si no existe ninguna. Se configura con
`ADMIN_CORREO` y `ADMIN_PASSWORD` en `admin/.env`; en producción `ADMIN_PASSWORD` es obligatoria.
**Cambie estas contraseñas fuera del entorno local.**

En el POS se ingresa con el **correo** (no distingue mayúsculas; la contraseña sí). `users.username` es sólo un
código corto que queda en ventas y gastos: no sirve para ingresar.

Comandos útiles:

```bash
docker compose exec app php artisan test     # pruebas del POS (BD mesatek_test)
docker compose exec admin php artisan test   # pruebas del admin (BD mesatek_admin_test)
docker compose exec app ./vendor/bin/pint    # formato (igual en admin)
docker compose exec app php artisan migrate:fresh --seed   # ¡borra TODOS los restaurantes!
```

## Autenticación y permisos

- **Laravel Fortify** en las dos apps. Son headless: las pantallas de ingreso son propias.
  El POS agrega `App\Actions\Fortify\OpenPosSession` al pipeline de ingreso (tipo de cambio, punto de
  venta, restaurante activo).
- **spatie/laravel-permission** en las dos apps, con tres roles:

  | Rol          | Guard   | Quién                    | Acceso                                                     |
  |--------------|---------|--------------------------|------------------------------------------------------------|
  | `superadmin` | `admin` | Dueño del sistema        | Todo el sistema administrador (`Gate::before`)             |
  | `partner`    | `web`   | Dueño de cada restaurante| POS y panel de su restaurante; crea cajeros                |
  | `cashier`    | `web`   | Cajero                   | Sólo el POS (`pos.*`, salvo autorizar el tipo de cambio)   |

- Hay **un permiso por acción** (`products.create`, `pos.sales.void`, `catalogs.units.update`...), creados por
  los seeders `RolesAndPermissionsSeeder` de cada app: `web` en `restaurant_pos` y `admin` en `admin`. Corren
  solos en cada arranque y no pisan lo editado desde el CRUD de roles. Para agregar uno, súmelo al seeder y exíjalo
  con `middleware('permission:clave')`: hay pruebas que fallan si una ruta queda sin permiso sembrado.
- Los partner se crean desde la app admin; desde su panel sólo pueden crear cajeros y no pueden editar a otro partner.
- Los permisos no se cachean (store `array`): un cambio hecho en admin se aplica en el POS en la siguiente
  petición. Cada app tiene su propio caché, así que uno persistente lo retrasaría hasta 24 horas.
- Los usuarios son la tabla `users` de Laravel; las cuentas del sistema, `admins`. Son independientes.

## Multirestaurante

- **Usuarios y restaurantes son muchos a muchos** (`restaurant_user`): un cajero pertenece a uno y un partner a
  varios, con un punto de venta por restaurante. El restaurante activo vive en la sesión, `SetTenant` lo valida
  en cada petición y un selector en el POS y el panel permite cambiarlo. Los partner de cada restaurante se
  asignan con el multiselect de Propietarios en la app admin.
- **Restaurante = tabla `restaurants`.** Tiene sucursales (`branches`), y exactamente una es la casa matriz
  (`is_main`, garantizado por un índice único). La matriz no se da de baja; primero se define otra.
- **Toda tabla de negocio tiene `restaurant_id` obligatorio.** Son comunes a todos solamente `countries`,
  `cities` y las de seguridad.
- **Aislamiento automático:** el trait `BelongsToRestaurant` filtra las consultas y asigna el restaurante
  al crear. Sale del usuario autenticado (`SetTenant`); en una petición web sin restaurante las consultas
  no devuelven nada. `tests/Feature/MultiRestaurantTest.php` falla si una tabla o un modelo queda fuera.
- **Al escribir código en `restaurant_pos`:** las reglas `exists`/`unique` y `DB::table` no pasan por el
  scope. Usar `Tenant::exists('table')` y `Tenant::unique('table', 'column')`, y filtrar a mano las
  consultas sin modelo. Un modelo nuevo debe usar `BelongsToRestaurant`.
- **El correo es único en todo el sistema**, porque identifica al restaurante al ingresar. El código
  (`username`) también es único.
- **Restaurantes y usuarios no se eliminan, se dan de baja:** guardan ventas y datos fiscales. La baja
  cierra en el acto las sesiones abiertas.
- **Dosificación:** un restaurante o sucursal nueva nace con `billing_authorizations` vencida. Puede ingresar
  y configurarse, pero no vender hasta que el dueño cargue la autorización real en Facturación SIN.

## Impresión

El agente local `http://<print_ip>:8080/reportes/*.php` es un programa externo que sigue leyendo los nombres
del sistema original. `public/js/pos/print-agent.js` traduce en un solo punto lo que sale hacia él: el resto
del POS usa los nombres actuales. Los valores de `proceso` (`imprimeAbrirMesa`...) son constantes del agente.

## Migrar los datos del sistema original (MySQL → PostgreSQL)

1. Copiar el respaldo `.sql` de la base MySQL original a `docker/legacy/`.
2. Levantar MySQL temporal con ese respaldo:
   `docker compose --profile legacy up -d legacy-db`
3. Importar a un sistema vacío, dentro de una transacción (si algo falla no se guarda nada).
   Los datos quedan en el primer restaurante y `--vaciar` borra los de **todos** los restaurantes:
   `docker compose exec app php artisan legacy:importar --vaciar`

El importador conserva los ID originales, convierte fechas `0000-00-00` a `NULL`, encripta las
contraseñas (antes en texto plano), continúa la numeración de facturas y omite —informando— los
registros huérfanos (p. ej. detalles de ventas inexistentes). Se puede limitar a tablas concretas:
`--tabla=productos --tabla=producto_sucursal`.

## Configuración del POS

Las opciones que antes se activaban creando archivos `.txt` vacíos en la raíz (`salon.txt`,
`billar.txt`, `impresionnube.txt`...) ahora son **opciones de cada restaurante**, que se editan en el sistema administrador. Las variables
de entorno (`restaurant_pos/config/pos.php`) sólo son el valor por defecto de un restaurante sin opciones guardadas:

| Variable                  | Archivo legacy         | Uso                                               |
|---------------------------|------------------------|---------------------------------------------------|
| `POS_SALON`               | `salon.txt`            | Mesas identificadas por nombre de cliente         |
| `POS_MESEROS_SIN_CODIGO`  | `meserosc.txt`         | No pide código del mesero al abrir mesa           |
| `POS_SIN_VISTA_EMPLEADO`  | `sinvistaempleado.txt` | Abre mesa sin pedir datos del mesero              |
| `POS_CIERRE_DIARIO`       | `cierreDiario.txt`     | Exige apertura y cierre de caja por punto de venta |
| `POS_BILLAR`              | `billar.txt`           | Productos cobrados por tiempo                     |
| `POS_IMPRESION_NUBE`      | `impresionnube.txt`    | Imprime vía el agente local `http://<ip>:8080`    |
| `POS_CAMBIO_FECHA`        | `cambiofecha.txt`      | Permite cambiar la fecha de venta                 |

La IP del agente de impresión de cada sucursal está en `branches.print_ip`.

## Panel del restaurante

http://localhost:8600/admin (o el botón ⚙ del POS), según los permisos del rol del usuario.
Productos y precios por sucursal, categorías, mesas, personal, usuarios, clientes, inventario
(stock, movimientos, kardex, insumos, almacenes, proveedores), facturación SIN y 11 reportes
exportables a Excel. Los assets (Tailwind + Alpine) se compilan solos al iniciar el contenedor;
para recompilar tras cambiar vistas: `docker compose exec app npm run build`.

## Arquitectura de `restaurant_pos`

```
app/
├── Actions/Fortify/          Paso propio del POS en el ingreso
├── Admin/                    Definición de catálogos y reportes del panel
├── Http/Controllers/Admin/   Panel de administración
├── Http/Controllers/Pos/     Endpoints del POS (sesión, catálogo, ventas, mesas, caja, clientes...)
├── Http/Middleware/          SetTenant, EnsurePosSession, RequirePanelAccess
├── Services/
│   ├── Billing/ControlCode.php         Código de control SIN v7 (Verhoeff + AllegedRC4 + Base64)
│   ├── Sales/                          RegisterSale, InvoiceNumbering (correlativos)
│   ├── Tables/TableOrders.php          Apertura, comandas, traspasos, cobro y anulación de mesas
│   └── StockMovement/SupplyConsumption.php  Descuento de insumos por receta + kardex
├── Support/
│   ├── Tenant.php            Restaurante de la petición (aislamiento entre restaurantes)
│   ├── PosContext.php        Datos de trabajo del cajero en sesión (antes $_SESSION)
│   ├── PosResponse.php       Contrato JSON {error, result} del frontend del POS
│   └── Legacy/               Importador MySQL -> PostgreSQL y su mapa de tablas
└── Models/                   Modelos Eloquent con relaciones
database/migrations/          Esquema PostgreSQL completo (ver docs/MIGRACION.md)
resources/views/pos/          Pantalla del POS en Blade (antes index.php + modales)
public/js/pos/pos.js          Frontend del POS (jQuery heredado, apuntando a rutas Laravel)
public/css/pos/pos.css        Diseño de la pantalla del POS (CSS Grid sobre Bootstrap 3)
public/legacy/                CSS, JS e imágenes estáticos del sistema original
```

El frontend del POS se mantuvo (jQuery/Bootstrap 3) para no cambiar la operación de los cajeros;
el backend es nuevo. Las respuestas usan los nombres de las columnas.

Detalle completo de la ingeniería inversa, el mapeo de tablas/endpoints y lo pendiente:
**[docs/MIGRACION.md](docs/MIGRACION.md)**.
