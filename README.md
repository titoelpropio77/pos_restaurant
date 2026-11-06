# Mesatek — Laravel + PostgreSQL

Sistema de punto de venta multirestaurante, migrado del PHP/MySQL original
(`posgourmet/posgourmet/`) a **Laravel 13 + PostgreSQL 17**, todo dockerizado.

> La carpeta `posgourmet/` contiene el sistema original **sin modificaciones**, como referencia.
> No se publica por el servidor web y no forma parte de ninguna aplicación.

## Estructura del monolito

Dos aplicaciones Laravel, cada una con su login, sobre **la misma base de datos**:

| Carpeta           | Para quién                | Contenedores        | URL                    |
|-------------------|---------------------------|---------------------|------------------------|
| `restaurant_pos/` | Dueños y vendedores       | `app` + `web`       | http://localhost:8600  |
| `administrador/`  | Administradores de Mesatek| `admin` + `admin_web` | http://localhost:8601 |

`restaurant_pos` es el **único dueño del esquema**: las migraciones viven allí y se ejecutan solas
al iniciar `app`. `administrador` no tiene migraciones; sus modelos usan las mismas tablas.

## Puesta en marcha

Requisitos: Docker y Docker Compose.

```bash
docker compose up -d --build                  # levanta todo
docker compose exec app php artisan db:seed   # restaurante y datos demo (sólo la primera vez)
```

PostgreSQL queda en el puerto **5436** (BD `posgourmet`, usuario/clave `posgourmet`).

| Aplicación    | Usuario               | Contraseña  | Rol                |
|---------------|-----------------------|-------------|--------------------|
| POS           | `admin`               | `admin`     | Dueño              |
| POS           | `cajero`              | `cajero`    | Cajero (demo)      |
| Administrador | `admin@mesatek.local` | `admin1234` | Superadministrador |

La cuenta del administrador se crea sola al iniciar `admin` si no existe ninguna. Se configura con
`ADMIN_CORREO` y `ADMIN_PASSWORD` en `administrador/.env`; en producción `ADMIN_PASSWORD` es
obligatoria. **Cambie estas contraseñas fuera del entorno local.**

En el POS, usuario y contraseña no distinguen mayúsculas, igual que el sistema original.

Comandos útiles:

```bash
docker compose exec app php artisan test     # pruebas del POS (BD posgourmet_test)
docker compose exec admin php artisan test   # pruebas del administrador (BD posgourmet_admin_test)
docker compose exec app ./vendor/bin/pint    # formato (igual en admin)
docker compose exec app php artisan migrate:fresh --seed   # ¡borra TODOS los restaurantes!
```

En un volumen de PostgreSQL creado antes de esta estructura, crear una vez la base de pruebas:
`docker compose exec db psql -U posgourmet posgourmet -c "CREATE DATABASE posgourmet_admin_test"`.

## Multirestaurante

- **Restaurante = tabla `empresas`.** Tiene sucursales, y exactamente una es la casa matriz
  (`sucursales.es_matriz`, garantizado por un índice único). La matriz no se da de baja; primero se
  define otra.
- **Toda tabla de negocio tiene `empresa_id` obligatorio.** Son comunes a todos solamente `paises`,
  `ciudades`, `monedas`, `modulos` y `bancos`, y las de seguridad (`roles`, `permisos`...).
- **Aislamiento automático:** el trait `PerteneceAEmpresa` filtra las consultas y asigna el
  restaurante al crear. El restaurante sale del usuario autenticado (`EstablecerRestaurante`); en una
  petición web sin restaurante las consultas no devuelven nada. `tests/Feature/MultiRestauranteTest.php`
  falla si una tabla o un modelo nuevo queda fuera.
- **Al escribir código en `restaurant_pos`:** las reglas `exists`/`unique` y `DB::table` no pasan por
  el scope. Usar `Restaurante::existe('tabla')` y `Restaurante::unico('tabla', 'columna')`, y filtrar
  a mano las consultas sin modelo. Un modelo nuevo debe usar `PerteneceAEmpresa`.
- **El login del POS es único en todo el sistema:** dos restaurantes no pueden tener el mismo usuario.
- **Restaurantes y usuarios no se eliminan, se dan de baja:** guardan ventas y datos fiscales. La baja
  cierra en el acto las sesiones abiertas.
- **Dosificación:** un restaurante o sucursal nueva nace con la dosificación vencida. Puede ingresar
  y configurarse, pero no vender hasta que el dueño cargue la autorización real en Facturación SIN.

## Sistema administrador

Menú (cada sección según los permisos del rol): restaurantes, sucursales y casa matriz, usuarios de
restaurantes, cuentas del sistema, roles, permisos, auditoría, y catálogos de países, ciudades,
monedas y bancos. Al crear un restaurante se generan casa matriz, almacén, numeración, catálogos base
y usuario dueño.

Los roles tienen ámbito **Sistema** (cuentas del administrador) o **Restaurantes** (usuarios del POS,
comunes a todos los restaurantes). Superadministrador y Dueño tienen siempre todos los permisos de su
ámbito, y siempre queda al menos un superadministrador activo. Un permiso nuevo no restringe nada
hasta que una ruta lo exija con `middleware('permiso:clave')`.

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
de entorno (`restaurant_pos/config/posgourmet.php`) sólo son el valor por defecto de un restaurante sin opciones guardadas:

| Variable                  | Archivo legacy         | Uso                                               |
|---------------------------|------------------------|---------------------------------------------------|
| `POS_SALON`               | `salon.txt`            | Mesas identificadas por nombre de cliente         |
| `POS_MESEROS_SIN_CODIGO`  | `meserosc.txt`         | No pide código del mesero al abrir mesa           |
| `POS_SIN_VISTA_EMPLEADO`  | `sinvistaempleado.txt` | Abre mesa sin pedir datos del mesero              |
| `POS_CIERRE_DIARIO`       | `cierreDiario.txt`     | Botón de cierre diario de caja                    |
| `POS_BILLAR`              | `billar.txt`           | Productos cobrados por tiempo                     |
| `POS_IMPRESION_NUBE`      | `impresionnube.txt`    | Imprime vía el agente local `http://<ip>:8080`    |
| `POS_CAMBIO_FECHA`        | `cambiofecha.txt`      | Permite cambiar la fecha de venta                 |

La IP del agente de impresión de cada sucursal está en `sucursales.ip_impresion`.

## Panel del restaurante

http://localhost:8600/admin (o el botón ⚙ del POS), según los permisos del rol del usuario.
Productos y precios por sucursal, categorías, mesas, personal, usuarios, clientes, inventario
(stock, movimientos, kardex, insumos, almacenes, proveedores), facturación SIN y 11 reportes
exportables a Excel. Los assets (Tailwind + Alpine) se compilan solos al iniciar el contenedor;
para recompilar tras cambiar vistas: `docker compose exec app npm run build`.

## Arquitectura de `restaurant_pos`

```
app/
├── Admin/                    Definición de catálogos y reportes del panel
├── Http/Controllers/Admin/   Panel de administración
├── Http/Controllers/Pos/     Endpoints del POS (sesión, catálogo, ventas, mesas, caja, clientes...)
├── Http/Middleware/          EstablecerRestaurante, AsegurarSesionPos, RequierePermiso
├── Services/
│   ├── Facturacion/CodigoControl.php   Código de control SIN v7 (Verhoeff + AllegedRC4 + Base64)
│   ├── Ventas/                         RegistrarVenta, NumeracionFactura (correlativos)
│   ├── Mesas/PedidosMesa.php           Apertura, comandas, traspasos, cobro y anulación de mesas
│   └── Inventario/DescargoInsumos.php  Descuento de insumos por receta + kardex
├── Support/
│   ├── Restaurante.php       Restaurante de la petición (aislamiento entre restaurantes)
│   ├── PosContexto.php       Datos de trabajo del cajero en sesión (antes $_SESSION)
│   ├── RespuestaPos.php      Contrato JSON {error, result} con nombres de campo legacy
│   └── Legacy/               Importador MySQL -> PostgreSQL y su mapa de tablas
└── Models/                   59 modelos Eloquent con relaciones
database/migrations/          Esquema PostgreSQL completo (ver docs/MIGRACION.md)
resources/views/pos/          Pantalla del POS en Blade (antes index.php + modales)
public/js/pos/posgourmet.js   Frontend del POS (jQuery heredado, apuntando a rutas Laravel)
public/css/pos/pos.css        Diseño de la pantalla del POS (CSS Grid sobre Bootstrap 3)
public/abms1/                 CSS, JS e imágenes estáticos del sistema original
```

El frontend del POS se mantuvo (jQuery/Bootstrap 3) para no cambiar la operación de los cajeros;
el backend es nuevo. Las respuestas conservan los nombres de campo que espera ese JS.

Detalle completo de la ingeniería inversa, el mapeo de tablas/endpoints y lo pendiente:
**[docs/MIGRACION.md](docs/MIGRACION.md)**.
