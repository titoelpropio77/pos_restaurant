# POS Gourmet — Laravel + PostgreSQL

Sistema de punto de venta para restaurantes, migrado del PHP/MySQL original
(`posgourmet/posgourmet/`) a **Laravel 13 + PostgreSQL 17**, todo dockerizado.

> La carpeta `posgourmet/` contiene el sistema original **sin modificaciones**, como referencia.
> No se publica por el servidor web (Nginx sirve sólo `public/`).

## Puesta en marcha

Requisitos: Docker y Docker Compose.

```bash
docker compose up -d --build              # PHP-FPM 8.4, Nginx y PostgreSQL 17
docker compose exec app php artisan db:seed   # datos base + demo (sólo la primera vez)
```

| Servicio   | Puerto host | Detalle                                           |
|------------|-------------|---------------------------------------------------|
| Web (POS)  | **8600**    | http://localhost:8600                             |
| PostgreSQL | **5436**    | BD `posgourmet`, usuario/clave `posgourmet`       |

Las migraciones se ejecutan solas al iniciar el contenedor `app` (`AUTO_MIGRATE=true`).

Usuarios de prueba (usuario y contraseña **no** distinguen mayúsculas, igual que el sistema original):

| Usuario  | Contraseña | Nivel          |
|----------|------------|----------------|
| `admin`  | `admin`    | Administrador  |
| `cajero` | `cajero`   | Cajero (demo)  |

Comandos útiles:

```bash
docker compose exec app php artisan test        # 62 pruebas (BD posgourmet_test)
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app ./vendor/bin/pint       # formato de código (excluye el legacy)
```

## Migrar los datos del sistema original (MySQL → PostgreSQL)

1. Copiar el respaldo `.sql` de la base MySQL original a `docker/legacy/`.
2. Levantar MySQL temporal con ese respaldo:
   `docker compose --profile legacy up -d legacy-db`
3. Importar (dentro de una transacción; si algo falla no se guarda nada):
   `docker compose exec app php artisan legacy:importar --vaciar`

El importador conserva los ID originales, convierte fechas `0000-00-00` a `NULL`, encripta las
contraseñas (antes en texto plano), continúa la numeración de facturas y omite —informando— los
registros huérfanos (p. ej. detalles de ventas inexistentes). Se puede limitar a tablas concretas:
`--tabla=productos --tabla=producto_sucursal`.

## Configuración del POS

Las opciones que antes se activaban creando archivos `.txt` vacíos en la raíz (`salon.txt`,
`billar.txt`, `impresionnube.txt`...) ahora son variables de entorno (`config/posgourmet.php`):

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

## Panel de administración

http://localhost:8600/admin (o el botón ⚙ del POS), sólo para usuarios de nivel administrador.
Productos y precios por sucursal, categorías, mesas, personal, usuarios, clientes, inventario
(stock, movimientos, kardex, insumos, almacenes, proveedores), facturación SIN y 11 reportes
exportables a Excel. Los assets (Tailwind + Alpine) se compilan solos al iniciar el contenedor;
para recompilar tras cambiar vistas: `docker compose exec app npm run build`.

## Arquitectura

```
app/
├── Admin/                    Definición de catálogos y reportes del panel
├── Http/Controllers/Admin/   Panel de administración
├── Http/Controllers/Pos/     Endpoints del POS (sesión, catálogo, ventas, mesas, caja, clientes...)
├── Http/Middleware/          AsegurarSesionPos (sesión vencida => {"error":"Error Session"})
├── Services/
│   ├── Facturacion/CodigoControl.php   Código de control SIN v7 (Verhoeff + AllegedRC4 + Base64)
│   ├── Ventas/                         RegistrarVenta, NumeracionFactura (correlativos)
│   ├── Mesas/PedidosMesa.php           Apertura, comandas, traspasos, cobro y anulación de mesas
│   └── Inventario/DescargoInsumos.php  Descuento de insumos por receta + kardex
├── Support/
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
