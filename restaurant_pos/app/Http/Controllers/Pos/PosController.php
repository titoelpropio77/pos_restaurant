<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\UserLogin;
use App\Services\CashRegister\CashSessions;
use App\Support\PosContext;
use App\Support\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pantalla principal del punto de venta (legacy: index.php).
 */
class PosController extends Controller
{
    public function index(PosContext $pos, CashSessions $cashSessions): View
    {
        $authenticated = Auth::check() && $pos->active();

        return view('pos.index', [
            'authenticated' => $authenticated,
            'user' => Auth::user(),
            'pos' => $pos,
            'options' => Tenant::options(),
            'mustOpenCash' => $authenticated && $cashSessions->enabled() && ! $cashSessions->current(Auth::user()),
            'exchangeRateCurrent' => (float) UserLogin::latest('id')->value('exchange_rate'),
            'routes' => $this->routes(),
        ]);
    }

    /**
     * URLs que usa pos.js, indexadas por el nombre con que las referencia el script.
     */
    private function routes(): array
    {
        return array_map(fn (string $name) => route($name, absolute: false), [
            'authorizeExchangeRate' => 'pos.exchange-rate.authorize',
            'branches' => 'pos.branches.index',
            'changeBranch' => 'pos.branches.change',
            'printBranch' => 'pos.branch.print',
            'authorization' => 'pos.branch.authorization',
            'verifyAuthorization' => 'pos.branch.authorization.verify',
            'waiters' => 'pos.waiters.index',
            'categories' => 'pos.categories.index',
            'products' => 'pos.products.index',
            'searchProduct' => 'pos.products.search',
            'searchBarcode' => 'pos.products.code-bar',
            'optionsProduct' => 'pos.products.options',
            'suppliesProduct' => 'pos.products.supplies',
            'deactivateProduct' => 'pos.products.deactivate',
            'moveProduct' => 'pos.products.move',
            'invoice' => 'pos.sales.store',
            'history' => 'pos.sales.history',
            'saleItem' => 'pos.sales.detail',
            'voidSale' => 'pos.sales.void',
            'printSale' => 'pos.sales.print',
            'printTableOrder' => 'pos.print.table-order',
            'printSaleOrder' => 'pos.print.sale-order',
            'printPreBill' => 'pos.print.pre-bill',
            'printReceipt' => 'pos.print.receipt',
            'openCashRegister' => 'pos.cash_register.open',
            'closingCashRegister' => 'pos.cash_register.closing',
            'closingNote' => 'pos.cash_register.note',
            'expenses' => 'pos.expenses.index',
            'delivered' => 'pos.expenses.delivered',
            'saveExpense' => 'pos.expenses.store',
            'diningTables' => 'pos.diningTables.index',
            'tablesProcessing' => 'pos.diningTables.processing',
            'tablesReleaseProcessing' => 'pos.diningTables.release-processing',
            'tableDetail' => 'pos.diningTables.detail',
            'openTable' => 'pos.diningTables.open',
            'addTable' => 'pos.diningTables.add',
            'tablePaid' => 'pos.diningTables.paid',
            'transferTable' => 'pos.diningTables.transfer',
            'voidTable' => 'pos.diningTables.void',
            'deleteItemTable' => 'pos.diningTables.delete-item',
            'deleteQuantityTable' => 'pos.diningTables.delete-quantity',
            'stopTime' => 'pos.diningTables.stop-timed',
            'itemsDeleted' => 'pos.diningTables.items-deleted',
            'customersSuggestionsName' => 'pos.customers.suggestions.name',
            'customersSuggestionsTaxId' => 'pos.customers.suggestions.tax_id',
            'customersSuggestionsDelivery' => 'pos.customers.suggestions.delivery',
            'searchCustomersByName' => 'pos.customers.search-name',
            'searchCustomersByTaxId' => 'pos.customers.search-tax_id',
            'customersStore' => 'pos.customers.store',
            'customersUpdate' => 'pos.customers.update',
            'customersDelete' => 'pos.customers.destroy',
            'cards' => 'pos.cards.search',
            'consumptionCard' => 'pos.cards.consume',
            'logout' => 'logout',
        ]);
    }
}
