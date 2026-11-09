<?php

use App\Http\Controllers\Pos\BranchController;
use App\Http\Controllers\Pos\CashRegisterController;
use App\Http\Controllers\Pos\CatalogController;
use App\Http\Controllers\Pos\CustomerController;
use App\Http\Controllers\Pos\ExpenseController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\PrepaidCardController;
use App\Http\Controllers\Pos\PrintController;
use App\Http\Controllers\Pos\SaleController;
use App\Http\Controllers\Pos\SessionController;
use App\Http\Controllers\Pos\TableController;
use App\Http\Controllers\RestaurantSwitchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Punto de venta
|--------------------------------------------------------------------------
| La pantalla del POS (legacy index.php) muestra el modal de ingreso si no hay sesión.
| Las rutas bajo /pos responden JSON con el contrato {"error", "result"} del frontend heredado.
*/

Route::get('/', [PosController::class, 'index'])->name('pos.index');

Route::post('/restaurant/switch', RestaurantSwitchController::class)->middleware('auth')->name('restaurant.switch');

Route::post('/pos/exchange-rate/authorize', [SessionController::class, 'authorizeExchangeRate'])
    ->middleware('throttle:10,1')
    ->name('pos.exchange-rate.authorize');

// pos.access cubre las consultas de sólo lectura; cada acción exige además su propio permiso
Route::middleware(['pos', 'permission:pos.access'])->prefix('pos')->name('pos.')->group(function () {
    // Sucursal de trabajo
    Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('branches/change', [BranchController::class, 'change'])->middleware('permission:pos.branches.change')->name('branches.change');
    Route::get('branch/print', [BranchController::class, 'print'])->name('branch.print');
    Route::get('branch/authorization', [BranchController::class, 'authorization'])->name('branch.authorization');
    Route::get('branch/authorization/verify', [BranchController::class, 'verifyAuthorization'])->name('branch.authorization.verify');
    Route::get('waiters', [BranchController::class, 'waiters'])->name('waiters.index');

    // Menú y productos
    Route::get('categories', [CatalogController::class, 'categories'])->name('categories.index');
    Route::get('products', [CatalogController::class, 'productsPorCategory'])->name('products.index');
    Route::get('products/search', [CatalogController::class, 'search'])->name('products.search');
    Route::get('products/code-bar', [CatalogController::class, 'searchBarcode'])->name('products.code-bar');
    Route::get('products/options', [CatalogController::class, 'options'])->name('products.options');
    Route::get('products/supplies', [CatalogController::class, 'supplies'])->name('products.supplies');
    Route::post('products/deactivate', [CatalogController::class, 'deactivate'])->middleware('permission:pos.products.deactivate')->name('products.deactivate');
    Route::post('products/move', [CatalogController::class, 'move'])->middleware('permission:pos.products.move')->name('products.move');

    // Ventas y caja
    Route::post('sales', [SaleController::class, 'store'])->middleware('permission:pos.sales.create')->name('sales.store');
    Route::get('sales/history', [SaleController::class, 'history'])->middleware('permission:pos.sales.view')->name('sales.history');
    Route::get('sales/detail', [SaleController::class, 'detail'])->middleware('permission:pos.sales.view')->name('sales.detail');
    Route::post('sales/void', [SaleController::class, 'void'])->middleware('permission:pos.sales.void')->name('sales.void');
    Route::post('sales/print', [SaleController::class, 'print'])->middleware('permission:pos.sales.print')->name('sales.print');

    // Tickets para la ticketera USB de la caja (HTML que el POS imprime desde un iframe)
    Route::middleware('permission:pos.orders.print')->group(function () {
        Route::get('print/table-order', [PrintController::class, 'tableOrder'])->name('print.table-order');
        Route::get('print/sale-order', [PrintController::class, 'saleOrder'])->name('print.sale-order');
        Route::get('print/pre-bill', [PrintController::class, 'preBill'])->name('print.pre-bill');
        Route::get('print/receipt', [PrintController::class, 'receipt'])->name('print.receipt');
    });
    Route::post('cash_register/open', [CashRegisterController::class, 'open'])->middleware('permission:pos.cash.open')->name('cash_register.open');
    Route::post('cash_register/closing', [CashRegisterController::class, 'closing'])->middleware('permission:pos.cash.close')->name('cash_register.closing');
    Route::post('cash_register/closing/note', [CashRegisterController::class, 'note'])->middleware('permission:pos.cash.close')->name('cash_register.note');

    Route::get('expenses', [ExpenseController::class, 'index'])->middleware('permission:pos.expenses.view')->name('expenses.index');
    Route::get('expenses/delivered', [ExpenseController::class, 'delivered'])->middleware('permission:pos.expenses.view')->name('expenses.delivered');
    Route::post('expenses', [ExpenseController::class, 'store'])->middleware('permission:pos.expenses.create')->name('expenses.store');

    // Mesas
    Route::get('tables', [TableController::class, 'index'])->middleware('permission:pos.tables.view')->name('diningTables.index');
    Route::post('tables/processing', [TableController::class, 'processing'])->middleware('permission:pos.tables.update')->name('diningTables.processing');
    Route::post('tables/release-processing', [TableController::class, 'releaseProcessing'])->middleware('permission:pos.tables.update')->name('diningTables.release-processing');
    Route::get('tables/detail', [TableController::class, 'detail'])->middleware('permission:pos.tables.view')->name('diningTables.detail');
    Route::post('tables/open', [TableController::class, 'open'])->middleware('permission:pos.tables.update')->name('diningTables.open');
    Route::post('tables/add', [TableController::class, 'add'])->middleware('permission:pos.tables.update')->name('diningTables.add');
    Route::post('tables/paid', [TableController::class, 'updateAfterCheckout'])->middleware('permission:pos.tables.charge')->name('diningTables.paid');
    Route::get('tables/split/preview', [TableController::class, 'splitPreview'])->middleware('permission:pos.tables.charge')->name('diningTables.split-preview');
    Route::post('tables/split/charge', [TableController::class, 'splitCharge'])->middleware('permission:pos.tables.charge')->name('diningTables.split-charge');
    Route::post('tables/transfer', [TableController::class, 'transfer'])->middleware('permission:pos.tables.transfer')->name('diningTables.transfer');
    Route::post('tables/void', [TableController::class, 'void'])->middleware('permission:pos.tables.void')->name('diningTables.void');
    Route::post('tables/delete-item', [TableController::class, 'deleteItem'])->middleware('permission:pos.tables.void')->name('diningTables.delete-item');
    Route::post('tables/delete-quantity', [TableController::class, 'deleteQuantities'])->middleware('permission:pos.tables.void')->name('diningTables.delete-quantity');
    Route::post('tables/stop-timed', [TableController::class, 'stopTime'])->middleware('permission:pos.tables.update')->name('diningTables.stop-timed');
    Route::get('tables/items-deleted', [TableController::class, 'itemsDeleted'])->middleware('permission:pos.tables.view')->name('diningTables.items-deleted');

    // Clientes
    Route::get('customers/suggestions/name', [CustomerController::class, 'suggestionsName'])->middleware('permission:pos.customers.view')->name('customers.suggestions.name');
    Route::get('customers/suggestions/tax-id', [CustomerController::class, 'suggestionsTaxId'])->middleware('permission:pos.customers.view')->name('customers.suggestions.tax_id');
    Route::get('customers/suggestions/delivery', [CustomerController::class, 'suggestionsDelivery'])->middleware('permission:pos.customers.view')->name('customers.suggestions.delivery');
    Route::get('customers/search-name', [CustomerController::class, 'searchName'])->middleware('permission:pos.customers.view')->name('customers.search-name');
    Route::get('customers/search-tax_id', [CustomerController::class, 'searchTaxId'])->middleware('permission:pos.customers.view')->name('customers.search-tax_id');
    Route::post('customers/store', [CustomerController::class, 'store'])->middleware('permission:pos.customers.create')->name('customers.store');
    Route::post('customers/update', [CustomerController::class, 'update'])->middleware('permission:pos.customers.update')->name('customers.update');
    Route::post('customers/delete', [CustomerController::class, 'destroy'])->middleware('permission:pos.customers.delete')->name('customers.destroy');

    // Tarjetas prepago
    Route::get('cards', [PrepaidCardController::class, 'search'])->middleware('permission:pos.cards.view')->name('cards.search');
    Route::post('cards/consume', [PrepaidCardController::class, 'consume'])->middleware('permission:pos.cards.consume')->name('cards.consume');
});
