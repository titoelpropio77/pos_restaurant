<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CashSessionController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de administración (prefijo /admin, sólo administradores)
|--------------------------------------------------------------------------
*/

Route::get('/', DashboardController::class)->name('dashboard');

// Productos (precios por sucursal, opciones, receta y grupos de reporte)
Route::get('products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
Route::get('products/create', [ProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
Route::post('products', [ProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
Route::get('products/import/template', [ProductController::class, 'importTemplate'])->middleware('permission:products.create')->name('products.import.template');
Route::post('products/import', [ProductController::class, 'import'])->middleware('permission:products.create')->name('products.import');
Route::get('products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:products.update')->name('products.edit');
Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update')->name('products.update');
Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');

// Usuarios del restaurante y su punto de venta
Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

// Sucursales: dosificación SIN y numeración de facturas
Route::get('billing', [BillingController::class, 'index'])->middleware('permission:billing.view')->name('billing.index');
Route::get('billing/{branch}', [BillingController::class, 'edit'])->middleware('permission:billing.update')->name('billing.edit');
Route::put('billing/{branch}', [BillingController::class, 'update'])->middleware('permission:billing.update')->name('billing.update');

// Inventario
Route::middleware('permission:inventory.view')->group(function () {
    Route::get('inventory/stock', [InventoryController::class, 'stock'])->name('inventory.stock');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('inventory/kardex', [InventoryController::class, 'kardex'])->name('inventory.kardex');
});
Route::middleware('permission:inventory.create')->group(function () {
    Route::get('inventory/movements/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('inventory/movements', [InventoryController::class, 'store'])->name('inventory.store');
});
Route::get('inventory/movements/{inventory}', [InventoryController::class, 'show'])->middleware('permission:inventory.view')->name('inventory.show');

// Ventas: detalle y anulación
Route::get('sales/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view')->name('sales.show');
Route::post('sales/{sale}/void', [SaleController::class, 'void'])->middleware('permission:sales.void')->name('sales.void');

// Caja: cajas abiertas y cierres
Route::get('cash-sessions', [CashSessionController::class, 'index'])->middleware('permission:cash_sessions.view')->name('cash_sessions.index');
Route::post('cash-sessions/{cashSession}/close', [CashSessionController::class, 'close'])->middleware('permission:cash_sessions.close')->name('cash_sessions.close');

// Reportes (exportar exige además reports.export: lo valida ReportController)
Route::middleware('permission:reports.view')->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
});

// Catálogos simples: CatalogController exige catalogs.{catalogo}.{accion}
Route::get('catalogs/{catalog}', [CatalogController::class, 'index'])->name('catalogs.index');
Route::get('catalogs/{catalog}/create', [CatalogController::class, 'create'])->name('catalogs.create');
Route::get('catalogs/{catalog}/import/template', [CatalogController::class, 'importTemplate'])->name('catalogs.import.template');
Route::post('catalogs/{catalog}/import', [CatalogController::class, 'import'])->name('catalogs.import');
Route::post('catalogs/{catalog}', [CatalogController::class, 'store'])->name('catalogs.store');
Route::get('catalogs/{catalog}/{id}/edit', [CatalogController::class, 'edit'])->name('catalogs.edit');
Route::put('catalogs/{catalog}/{id}', [CatalogController::class, 'update'])->name('catalogs.update');
Route::delete('catalogs/{catalog}/{id}', [CatalogController::class, 'destroy'])->name('catalogs.destroy');
