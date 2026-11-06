# Migración de POS Gourmet: PHP/MySQL → Laravel/PostgreSQL

> **Nota:** este documento describe la migración original y usa los nombres en español de entonces
> (`ventas`, `sucursales`, `/pos/ventas`...). El esquema actual está en inglés: ver `README.md` y las migraciones
> de `restaurant_pos/database/migrations/`.

## 1. Ingeniería inversa del sistema original

**Punto de entrada:** `index.php` (pantalla del POS) incluye `modalLogin.php`, `modalGourmet.php`
y `verificador.php`; toda la lógica de pantalla está en `posgourmet.js` (3.700 líneas, jQuery).

**Arquitectura legacy:**

- `abms1/class/*.class.php` — capa de datos: una clase por tabla con SQL concatenado (sin
  parámetros, vulnerable a inyección SQL) sobre `mysqli`.
- `abms1/CONTROLADORES/*Controller.php` — un archivo por módulo; reciben `POST proceso=...`
  y devuelven `{"error": "...", "result": ...}`.
- `abms1/*.php`, `abms1/frmGeneral/**` — panel de administración (ABM, reportes, contabilidad).
- `abms1/reportes/*.php` — impresión ESC/POS (Mike42) en impresoras térmicas compartidas.
- `verificadores/*.txt` — banderas de configuración por existencia de archivo.
- Conexión: `abms1/class/config.php` con credenciales en código.

**Esquema real:** `estructura.sql` es un volcado de `posgourmet2` (41 tablas), pero el código
apunta a `posgourmet3`, que tiene ~30 tablas y columnas más (multi-sucursal: `productosucursal`,
`puntoventa`, `insumoalmacen`, `idSucursal` en ventas/gastos/mesas...). El esquema nuevo se
reconstruyó cruzando el volcado con los `INSERT`/`UPDATE` de todas las clases y controladores.

**Tablas descartadas** (sin uso en el código): `det_prov_mysql`, `prov_gral_mysql`, `diario_mysql`,
`empleados1_mysql`, `improductos_mysql`, `kardexprod_mysql`, `medprod_mysql`, `productos_mysqlok`,
`productos_tmp_mysql`, `proveedores_mysql`, `producto` (diseño nuevo que nunca se usó), `transaccion`.

## 2. Criterios del nuevo esquema PostgreSQL

- Tablas en español, plural, sin sufijo `_mysql`; PK `id` bigint; `created_at/updated_at`.
- Columnas en `snake_case` minúscula conservando el vocabulario de negocio (`nom_prod`, `pre_venta`,
  `f_venta`, `cam_of`...). Las referencias `idX` pasan a `x_id` con **claves foráneas reales**.
- `double` → `decimal` (montos exactos). Fechas `'0000-00-00'` → `NULL`.
- Banderas legacy de 1 carácter (`ANULADO='S'`, `CERRADO`, `USO`...) se conservan para no alterar la
  lógica de negocio; candidatas a pasar a `boolean` cuando se reescriba el frontend.
- La vista `vistarelprogrudet` (MySQL agrupaba con columnas no agregadas) se rehízo como
  `vista_grupo_producto_ventas`, agrupando también por cajero, turno y sucursal.

### Mapeo de tablas

| Legacy (MySQL)          | Nuevo (PostgreSQL)           | Notas                                         |
|-------------------------|------------------------------|-----------------------------------------------|
| pais / ciudad / moneda  | paises / ciudades / monedas  |                                               |
| empresa                 | empresas                     |                                               |
| sucursal                | sucursales                   | `ipImpresion` → `ip_impresion`                |
| empresa_mysql           | dosificaciones               | Datos fiscales y dosificación SIN por sucursal |
| correlativos            | correlativos                 | `tabla='ven_gral_mysql'` → `'ventas'`         |
| usuarios_mysql          | usuarios                     | `CLAVES` (texto plano) → `password` (bcrypt)  |
| inguser_mysql           | ingresos_usuario             | + `usuario_id`                                |
| empleados_mysql         | personal                     | Meseros / personal operativo                  |
| empleado / cargo / turno| empleados / cargos / turnos  |                                               |
| puntoventa              | puntos_venta                 | `idEmpleado` → `usuario_id`                   |
| puntoventacaja/cuenta   | punto_venta_cajas/_cuentas   |                                               |
| menugrupo_mysql         | categorias                   | Sin columnas BLOB de imágenes                 |
| productos_mysql         | productos                    | `grupo` → `categoria_id`                      |
| productosucursal        | producto_sucursal            |                                               |
| opproductos_mysql       | producto_opciones            | `COD_PROD` → `producto_id`                    |
| grupos_mysql            | grupos                       | Grupos de reporte                             |
| relprogru_mysql         | grupo_producto               | `COD_GRUPO` (código) → `grupo_id`             |
| relproins_mysql         | insumo_producto              | Recetas                                       |
| insumos_mysql           | insumos                      |                                               |
| insumoalmacen           | insumo_almacen               |                                               |
| inventario              | inventarios                  |                                               |
| kardexins_mysql         | kardex_insumos               | `REGISTRO`/`FACTURA`/`COD_INS` → FKs          |
| almacen / motivomovimiento / unidadmedida | almacenes / motivos_movimiento / unidades_medida | |
| proveedores             | proveedores                  |                                               |
| clientes_mysql          | clientes                     | Sin `FOTO` BLOB                               |
| ven_gral_mysql          | ventas                       | `COD_CLI` → `cliente_id`                      |
| detalles_mysql          | venta_detalles               | `FACTURA` → `venta_id`                        |
| dettarjeta_mysql        | tarjeta_detalles             | Consumos con tarjeta prepago                  |
| gastos_mysql            | gastos                       | Ingresos (1) y egresos (0) de caja            |
| mesas_mysql             | mesas                        |                                               |
| productos_mesas_mysql   | mesa_pedidos                 | `NRO_MESA` (era el id) → `mesa_id`            |
| cuentacontable, asiento, det_asiento_cuenta, gestion, centro_costo, cuenta_grupo, cuenta_impuesto, cuentaautomatica, impuestos, banco, cuentabancaria, cajacuenta, tipoingreso, tipoegreso, tipogastocompra, tipocliente, listaprecio | cuentas_contables, asientos, asiento_detalles, gestiones, centros_costo, cuenta_grupos, cuenta_impuestos, cuentas_automaticas, impuestos, bancos, cuentas_bancarias, cajas, tipos_ingreso, tipos_egreso, tipos_gasto_compra, tipos_cliente, listas_precio | Módulo contable |

El mapeo ejecutable está en `app/Support/Legacy/MapaImportacion.php`.

## 3. Endpoints: legacy → Laravel

Todas bajo el middleware `pos` (sesión + sucursal de trabajo). Las respuestas mantienen el
contrato `{"error", "result"}` y los nombres de campo legacy (mayúsculas en ventas/mesas/gastos).

| Legacy (`CONTROLADORES/…php`, `proceso`)           | Laravel                              |
|----------------------------------------------------|--------------------------------------|
| `index.php` (`btnLogin`) / `loginController`       | `POST /login`, `POST /logout`        |
| `tipoCambioController` cambiarMoneda               | `POST /pos/tipo-cambio/autorizar`    |
| `menuGrupoController` mostrarTodo                  | `GET /pos/categorias`                |
| `productoController` mostrarProductoMenu / buscarProductoKeyUp / buscarProductoCodBarra | `GET /pos/productos`, `/pos/productos/buscar`, `/pos/productos/codigo-barra` |
| `productoController` desactivarProducto / moverProductoMenu | `POST /pos/productos/desactivar`, `/pos/productos/mover` |
| `opProductoController` listar / `insumoProductoController` listarInsumoDadoProducto | `GET /pos/productos/opciones`, `/pos/productos/insumos` |
| `facturarController` guardar                       | `POST /pos/ventas`                   |
| `REPORTEPHP/prueba5.php`                           | `POST /pos/ventas/impresion`         |
| `venGralController` listarHistorico / anularVenta  | `GET /pos/ventas/historico`, `POST /pos/ventas/anular` |
| `detalleVentaController` listarDadaVenta           | `GET /pos/ventas/detalle`            |
| `venGralController` listarTOTALCAJACIERRE / archivarTotales | `POST /pos/cash_register/closing` (cierre por sesión de caja) |
| Sin equivalente legacy (apertura de caja)          | `POST /pos/cash_register/open`       |
| `gastosController` listarEntregadoGAsto / listaroGastos / guardarGasto | `GET /pos/gastos/entregados`, `GET /pos/gastos`, `POST /pos/gastos` |
| `mesasController` listarTodo / mesaProcesando / hablilitarMesaProcesando | `GET /pos/mesas`, `POST /pos/mesas/procesando`, `/pos/mesas/liberar-procesando` |
| `productoMesaController` guardar / aumentarProductoMesa / listarDetalleMesa | `POST /pos/mesas/abrir`, `/pos/mesas/agregar`, `GET /pos/mesas/detalle` |
| `productoMesaController` habilitarActualizarMesa / traspasarItemMesa / anularMesa | `POST /pos/mesas/cobrada`, `/pos/mesas/traspasar`, `/pos/mesas/anular` |
| `productoMesaController` eliminarItem / eliminarItemCant / pararTiempoBillar | `POST /pos/mesas/eliminar-item`, `/pos/mesas/eliminar-cantidad`, `/pos/mesas/parar-tiempo` |
| `productoMesaController` listarItemElimDadaFecha   | `GET /pos/mesas/items-eliminados`    |
| `meseroController` listarTodo                      | `GET /pos/meseros`                   |
| `clienteController` / `auxController` (autocompletado) | `GET /pos/clientes/*`, `POST /pos/clientes/actualizar`, `/pos/clientes/eliminar` |
| `deTajetaController` buscarTarjetaGift / guardar   | `GET /pos/tarjetas`, `POST /pos/tarjetas/consumo` |
| `sucursalController`, `printersettingController`, `docificacionController`, `empresaController` | `GET /pos/sucursales`, `POST /pos/sucursales/cambiar`, `GET /pos/sucursal/impresion`, `/pos/sucursal/dosificacion`, `/pos/sucursal/dosificacion/verificar` |

## 4. Cambios de comportamiento y errores corregidos

- **Seguridad:** consultas parametrizadas (Eloquent) en lugar de SQL concatenado; contraseñas con
  bcrypt; CSRF en todas las peticiones; límite de intentos en el login; credenciales fuera del código.
- **Concurrencia:** la numeración de facturas bloquea la fila del correlativo (`SELECT … FOR UPDATE`);
  antes dos cajas simultáneas podían emitir el mismo número.
- **Transacciones:** cada venta, cobro o traspaso es atómico (antes había `commit` dentro de bucles).
- `eliminarItemCant` (eliminar parte de un ítem de mesa) no hacía nada en el servidor aunque el POS
  mostraba "GUARDADO CORRECTAMENTE"; ahora descuenta la cantidad y la registra como ítem eliminado.
- Activar una venta anulada dejaba `ANULADO='S'`; ahora lo limpia.
- El cierre de caja reiniciaba la orden del día de **todas** las sucursales; ahora sólo la actual.
- "Mover producto a otra categoría" cambiaba `productos_mysql.grupo`, pero el menú se arma con
  `productosucursal.idCategoria`; ahora actualiza ambos.
- El modal de insumos mostraba `insumos_mysql.STOCK_ACT` (obsoleto); ahora el stock del almacén.
- Las mesas con `ESTADO='ELIMINADO'` ya no aparecen en el salón.
- Respaldo a Google Drive desde el navegador: reemplazado por respaldos de PostgreSQL
  (`docker compose exec db pg_dump -U posgourmet posgourmet > respaldo.sql`).

## 5. Estado de la migración

**Migrado y probado** (62 pruebas automatizadas + pruebas en navegador real):

- Infraestructura Docker (PHP-FPM 8.4 con Node para compilar assets, Nginx, PostgreSQL 17) y esquema completo.
- 59 modelos Eloquent con relaciones; seeders base y demo.
- Pantalla del POS completa: ingreso con tipo de cambio, menú por sucursal, búsqueda y código de
  barras, facturación con código de control SIN, recibos/facturas y múltiplos, descargo de inventario,
  histórico y anulación, ingresos/egresos, mesas (abrir, comandas, traspasos, cobro parcial/total,
  anulación, billar), clientes y envío a domicilio, tarjetas prepago, cierre de caja.
- **Panel de administración** (`/admin`, sólo nivel administrador; Blade + Tailwind 4 + Alpine.js):
  - Inicio: indicadores, gráfico de ventas, productos más vendidos, formas de pago, alertas de stock
    mínimo y de vencimiento de dosificación, últimas ventas.
  - Productos con precio, días, horario e impresoras por sucursal, imagen, opciones al vender, receta
    y grupos de reporte. Categorías, grupos de reporte, clientes, mesas, meseros/personal.
  - Usuarios con su punto de venta (sucursal, almacén, multi-sucursal, puede vender).
  - Inventario: stock valorizado por almacén, movimientos (compra con costo promedio ponderado,
    salida/merma, traspaso, ajuste por conteo) con kardex; insumos, almacenes, proveedores,
    unidades de medida y motivos.
  - Facturación SIN por sucursal: datos fiscales, dosificación con historial, numeración y turnos.
  - Reportes (filtros, totales, impresión y exportación a Excel): listado de ventas, ventas por día,
    productos vendidos, grupos de reporte, ventas por mesero con comisión, clientes, arqueo de caja,
    ingresos/egresos, ítems eliminados y mesas anuladas, libro de ventas IVA y libro de compras IVA.
- Importador de datos MySQL → PostgreSQL (`php artisan legacy:importar`).

**Cambios respecto al sistema original en esta fase:**

- El POS cobra el precio configurado para la sucursal (antes usaba siempre el precio general aunque
  la tabla `productosucursal` tuviera otro).
- Anular una mesa la marca como anulada (`anulado = 'S'`) para distinguirla de una mesa cobrada.
- El kardex registra el almacén afectado (`kardex_insumos.almacen_id`).

**Pendiente** (siguientes fases):

1. **Contabilidad** (`frmGeneral/Contabilidad`): plan de cuentas, comprobantes, libro diario/mayor,
   balance y estado de resultados. Las tablas y modelos existen; en el legacy el asiento automático
   por venta estaba comentado.
2. **Impresión**: el POS sigue enviando a un agente local `http://<ip_impresion>:8080/reportes/*.php`
   (ESC/POS). Falta el PDF de factura (`REPORTEPHP/prueba4.php`).
3. **Tigo Money** (`tigoMoneyController`, `libTigoMoney`): integración con el servicio externo.
4. A futuro: reemplazar el frontend jQuery del POS y pasar las banderas `'S'/''` a booleanos.
