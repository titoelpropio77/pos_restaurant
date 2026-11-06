<?php

use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacturacionController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de administración (prefijo /admin, sólo administradores)
|--------------------------------------------------------------------------
*/

Route::get('/', DashboardController::class)->name('dashboard');

// Productos (precios por sucursal, opciones, receta y grupos de reporte)
Route::resource('productos', ProductoController::class)->except('show')->middleware('permiso:panel.productos');

// Usuarios del sistema y su punto de venta
Route::resource('usuarios', UsuarioController::class)->except('show')->middleware('permiso:panel.usuarios');

// Sucursales: dosificación SIN y numeración de facturas
Route::middleware('permiso:panel.facturacion')->group(function () {
    Route::get('facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    Route::get('facturacion/{sucursal}', [FacturacionController::class, 'edit'])->name('facturacion.edit');
    Route::put('facturacion/{sucursal}', [FacturacionController::class, 'update'])->name('facturacion.update');
});

// Inventario
Route::middleware('permiso:panel.inventario')->group(function () {
    Route::get('inventario/stock', [InventarioController::class, 'stock'])->name('inventario.stock');
    Route::get('inventario/movimientos', [InventarioController::class, 'movimientos'])->name('inventario.movimientos');
    Route::get('inventario/movimientos/nuevo', [InventarioController::class, 'create'])->name('inventario.create');
    Route::post('inventario/movimientos', [InventarioController::class, 'store'])->name('inventario.store');
    Route::get('inventario/movimientos/{inventario}', [InventarioController::class, 'show'])->name('inventario.show');
    Route::get('inventario/kardex', [InventarioController::class, 'kardex'])->name('inventario.kardex');
});

// Ventas: detalle y anulación
Route::middleware('permiso:panel.ventas')->group(function () {
    Route::get('ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    Route::post('ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
});

// Reportes
Route::middleware('permiso:panel.reportes')->group(function () {
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/{reporte}', [ReporteController::class, 'show'])->name('reportes.show');
});

// Catálogos simples (categorías, mesas, personal, clientes, insumos, almacenes...)
Route::middleware('permiso:panel.catalogos')->group(function () {
    Route::get('catalogos/{catalogo}', [CatalogoController::class, 'index'])->name('catalogos.index');
    Route::get('catalogos/{catalogo}/nuevo', [CatalogoController::class, 'create'])->name('catalogos.create');
    Route::post('catalogos/{catalogo}', [CatalogoController::class, 'store'])->name('catalogos.store');
    Route::get('catalogos/{catalogo}/{id}/editar', [CatalogoController::class, 'edit'])->name('catalogos.edit');
    Route::put('catalogos/{catalogo}/{id}', [CatalogoController::class, 'update'])->name('catalogos.update');
    Route::delete('catalogos/{catalogo}/{id}', [CatalogoController::class, 'destroy'])->name('catalogos.destroy');
});
