<?php

use App\Http\Controllers\Pos\CatalogoController;
use App\Http\Controllers\Pos\ClienteController;
use App\Http\Controllers\Pos\GastoController;
use App\Http\Controllers\Pos\MesaController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\SesionController;
use App\Http\Controllers\Pos\SucursalController;
use App\Http\Controllers\Pos\TarjetaPrepagoController;
use App\Http\Controllers\Pos\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Punto de venta
|--------------------------------------------------------------------------
| La pantalla del POS (legacy index.php) muestra el modal de ingreso si no hay sesión.
| Las rutas bajo /pos responden JSON con el contrato {"error", "result"} del frontend heredado.
*/

Route::get('/', [PosController::class, 'index'])->name('pos.index');

Route::post('/login', [SesionController::class, 'login'])->middleware('throttle:10,1')->name('login');
Route::post('/logout', [SesionController::class, 'logout'])->name('logout');
Route::post('/pos/tipo-cambio/autorizar', [SesionController::class, 'autorizarTipoCambio'])
    ->middleware('throttle:10,1')
    ->name('pos.tipo-cambio.autorizar');

Route::middleware('pos')->prefix('pos')->name('pos.')->group(function () {
    // Sucursal de trabajo
    Route::get('sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
    Route::post('sucursales/cambiar', [SucursalController::class, 'cambiar'])->name('sucursales.cambiar');
    Route::get('sucursal/impresion', [SucursalController::class, 'impresion'])->name('sucursal.impresion');
    Route::get('sucursal/dosificacion', [SucursalController::class, 'dosificacion'])->name('sucursal.dosificacion');
    Route::get('sucursal/dosificacion/verificar', [SucursalController::class, 'verificarDosificacion'])->name('sucursal.dosificacion.verificar');
    Route::get('meseros', [SucursalController::class, 'meseros'])->name('meseros.index');

    // Menú y productos
    Route::get('categorias', [CatalogoController::class, 'categorias'])->name('categorias.index');
    Route::get('productos', [CatalogoController::class, 'productosPorCategoria'])->name('productos.index');
    Route::get('productos/buscar', [CatalogoController::class, 'buscar'])->name('productos.buscar');
    Route::get('productos/codigo-barra', [CatalogoController::class, 'buscarCodigoBarra'])->name('productos.codigo-barra');
    Route::get('productos/opciones', [CatalogoController::class, 'opciones'])->name('productos.opciones');
    Route::get('productos/insumos', [CatalogoController::class, 'insumos'])->name('productos.insumos');
    Route::post('productos/desactivar', [CatalogoController::class, 'desactivar'])->name('productos.desactivar');
    Route::post('productos/mover', [CatalogoController::class, 'mover'])->name('productos.mover');

    // Ventas y caja
    Route::post('ventas', [VentaController::class, 'store'])->middleware('permiso:pos.vender')->name('ventas.store');
    Route::get('ventas/historico', [VentaController::class, 'historico'])->name('ventas.historico');
    Route::get('ventas/detalle', [VentaController::class, 'detalle'])->name('ventas.detalle');
    Route::post('ventas/anular', [VentaController::class, 'anular'])->name('ventas.anular');
    Route::post('ventas/impresion', [VentaController::class, 'impresion'])->name('ventas.impresion');
    Route::get('caja/cajeros', [VentaController::class, 'cajeros'])->name('caja.cajeros');
    Route::get('caja/totales', [VentaController::class, 'totalesCierre'])->name('caja.totales');
    Route::post('caja/cierre', [VentaController::class, 'cierre'])->name('caja.cierre');

    Route::get('gastos', [GastoController::class, 'index'])->name('gastos.index');
    Route::get('gastos/entregados', [GastoController::class, 'entregados'])->name('gastos.entregados');
    Route::post('gastos', [GastoController::class, 'store'])->name('gastos.store');

    // Mesas
    Route::get('mesas', [MesaController::class, 'index'])->name('mesas.index');
    Route::post('mesas/procesando', [MesaController::class, 'procesando'])->name('mesas.procesando');
    Route::post('mesas/liberar-procesando', [MesaController::class, 'liberarProcesando'])->name('mesas.liberar-procesando');
    Route::get('mesas/detalle', [MesaController::class, 'detalle'])->name('mesas.detalle');
    Route::post('mesas/abrir', [MesaController::class, 'abrir'])->name('mesas.abrir');
    Route::post('mesas/agregar', [MesaController::class, 'agregar'])->name('mesas.agregar');
    Route::post('mesas/cobrada', [MesaController::class, 'actualizarTrasCobro'])->name('mesas.cobrada');
    Route::post('mesas/traspasar', [MesaController::class, 'traspasar'])->name('mesas.traspasar');
    Route::post('mesas/anular', [MesaController::class, 'anular'])->name('mesas.anular');
    Route::post('mesas/eliminar-item', [MesaController::class, 'eliminarItem'])->name('mesas.eliminar-item');
    Route::post('mesas/eliminar-cantidad', [MesaController::class, 'eliminarCantidades'])->name('mesas.eliminar-cantidad');
    Route::post('mesas/parar-tiempo', [MesaController::class, 'pararTiempo'])->name('mesas.parar-tiempo');
    Route::get('mesas/items-eliminados', [MesaController::class, 'itemsEliminados'])->name('mesas.items-eliminados');

    // Clientes
    Route::get('clientes/sugerencias/nombre', [ClienteController::class, 'sugerenciasNombre'])->name('clientes.sugerencias.nombre');
    Route::get('clientes/sugerencias/nit', [ClienteController::class, 'sugerenciasNit'])->name('clientes.sugerencias.nit');
    Route::get('clientes/sugerencias/domicilio', [ClienteController::class, 'sugerenciasDomicilio'])->name('clientes.sugerencias.domicilio');
    Route::get('clientes/buscar-nombre', [ClienteController::class, 'buscarNombre'])->name('clientes.buscar-nombre');
    Route::get('clientes/buscar-nit', [ClienteController::class, 'buscarNit'])->name('clientes.buscar-nit');
    Route::post('clientes/actualizar', [ClienteController::class, 'update'])->name('clientes.update');
    Route::post('clientes/eliminar', [ClienteController::class, 'destroy'])->name('clientes.destroy');

    // Tarjetas prepago
    Route::get('tarjetas', [TarjetaPrepagoController::class, 'buscar'])->name('tarjetas.buscar');
    Route::post('tarjetas/consumo', [TarjetaPrepagoController::class, 'consumir'])->name('tarjetas.consumir');
});
