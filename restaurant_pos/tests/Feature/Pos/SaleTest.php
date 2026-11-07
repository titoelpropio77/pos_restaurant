<?php

namespace Tests\Feature\Pos;

use App\Admin\Reports\DailySales;
use App\Admin\Reports\Filters;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\InvoiceSequence;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Sale;
use App\Models\WarehouseStock;
use App\Services\CashRegister\CashCount;
use App\Support\PosContext;

class SaleTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->log_in();
    }

    private function sell(array $products, array $extra = []): array
    {
        $data = ['idProduct' => [], 'quantity' => [], 'price' => [], 'subTotal' => [], 'note' => [], 'qr' => [], 'nameProduct' => []];
        $total = 0;
        foreach ($products as $name => $quantity) {
            $product = Product::where('product_name', $name)->firstOrFail();
            $data['idProduct'][] = $product->id;
            $data['quantity'][] = $quantity;
            $data['price'][] = $product->sale_price;
            $data['subTotal'][] = $quantity * $product->sale_price;
            $data['note'][] = '';
            $data['qr'][] = '';
            $data['nameProduct'][] = $name;
            $total += $quantity * $product->sale_price;
        }

        return $this->ajax('post', 'pos.sales.store', array_merge($data, [
            'nameCustomer' => 'SIN NOMBRE',
            'customerTaxId' => '',
            'totalBs' => $total,
            'totalInvoiceBs' => $total,
            'changeBs' => 0,
            'paymentMethod' => 'CONTADO',
            'shift' => 'NOCHE',
            'exitProduct' => 'LLEVAR',
            'saleDate' => today()->toDateString(),
        ], $extra))->assertOk()->json();
    }

    public function test_registers_the_sale_with_its_detail_and_code_of_control(): void
    {
        $response = $this->sell(['SILPANCHO' => 2, 'COCA COLA 2L' => 1]);

        $this->assertSame('', $response['error']);
        $sale = Sale::with('details')->findOrFail($response['result']);
        $this->assertSame(1, $response['result2']);
        $this->assertEquals(85, $sale->invoice_total);
        $this->assertSame($sale->id, (int) $sale->invoice_ref);
        $this->assertCount(2, $sale->details);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}-){3,4}[0-9A-F]{2}$/', $sale->control_code);
        $this->assertSame(1, InvoiceSequence::first()->daily_order);
    }

    public function test_qr_sale_is_reported_and_does_not_count_as_cash_in_the_drawer(): void
    {
        $this->sell(['SILPANCHO' => 1]);
        $qrSale = $this->sell(['COCA COLA 2L' => 1], ['paymentMethod' => 'QR']);

        $this->assertSame('QR', Sale::findOrFail($qrSale['result'])->payment_method);

        $cash = (float) Sale::where('payment_method', 'CONTADO')->sum('invoice_total');
        $qr = (float) Sale::where('payment_method', 'QR')->sum('invoice_total');
        $totals = app(CashCount::class)->totals(today()->toDateString(), today()->toDateString());

        $this->assertEquals($qr, $totals['qr']);
        $this->assertEquals($cash, $totals['cash']);
        $this->assertEquals($cash, $totals['balance']);

        $row = collect(app(DailySales::class)->rows(new Filters(today(), today())))->first();
        $this->assertEquals([$cash, $qr, 0.0], [$row['cash'], $row['qr'], $row['others']]);
    }

    public function test_without_tax_id_avanza_the_receipt_and_with_tax_id_avanza_the_invoice(): void
    {
        $this->sell(['FLAN' => 1]);
        $this->assertSame([0, 1], [InvoiceSequence::first()->folio, InvoiceSequence::first()->real_folio]);

        $response = $this->sell(['FLAN' => 1], ['customerTaxId' => '4189179011', 'nameCustomer' => 'JUAN']);
        $this->assertSame([1, 1], [InvoiceSequence::first()->folio, InvoiceSequence::first()->real_folio]);
        $this->assertSame(1, $response['numberInvoice']);
        $this->assertDatabaseHas('customers', ['customer_name' => 'JUAN', 'customer_tax_id' => '4189179011']);
    }

    public function test_each_mult_receipts_consumes_a_number_of_invoice(): void
    {
        InvoiceSequence::first()->update(['receipt_multiplier' => 2, 'receipt_counter' => 0]);

        $this->sell(['FLAN' => 1]);
        $this->assertSame([0, 1, 1], [InvoiceSequence::first()->folio, InvoiceSequence::first()->real_folio, InvoiceSequence::first()->receipt_counter]);

        $this->sell(['FLAN' => 1]);
        $this->assertSame([1, 1, 0], [InvoiceSequence::first()->folio, InvoiceSequence::first()->real_folio, InvoiceSequence::first()->receipt_counter]);
    }

    public function test_deducts_the_supplies_of_the_recipe_and_registers_the_kardex(): void
    {
        $limon = WarehouseStock::whereHas('supply', fn ($q) => $q->where('supply_name', 'LIMÓN'))->first();
        $stockInitial = (float) $limon->current_stock;

        $response = $this->sell(['LIMONADA' => 3]);

        $this->assertEquals($stockInitial - 6, (float) $limon->fresh()->current_stock);
        $this->assertDatabaseHas('stock_ledger', ['sale_id' => $response['result'], 'supply_id' => $limon->supply_id, 'qty_out' => 6]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_not_sells_with_the_authorization_expired(): void
    {
        BillingAuthorization::first()->update(['limit_date' => today()->subDay()]);

        $response = $this->sell(['FLAN' => 1]);

        $this->assertSame('SU DOSIFICACIÓN YA SE VENCIÓ, NO PODRÁ VENDER', $response['error']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_without_billing_sells_without_authorization(): void
    {
        BillingAuthorization::first()->update(['limit_date' => today()->subDay()]);
        Restaurant::firstOrFail()->update(['options' => ['billing' => false]]);

        $this->assertSame('', $this->ajax('get', 'pos.branch.authorization.verify', ['date' => today()->toDateString()])->json('result'));

        $response = $this->sell(['FLAN' => 1]);

        $this->assertSame('', $response['error']);
        $sale = Sale::findOrFail($response['result']);
        $this->assertSame('', (string) $sale->control_code);
        $this->assertNull($sale->authorization_number);

        // Tampoco la exige al cambiar de sucursal
        BillingAuthorization::query()->delete();
        app(PosContext::class)->changeBranch(Branch::firstOrFail());
        $this->assertSame('', $this->sell(['FLAN' => 1])['error']);
    }

    public function test_validates_the_data_of_the_sale(): void
    {
        $this->ajax('post', 'pos.sales.store', ['totalBs' => 0])
            ->assertOk()
            ->assertJsonPath('error', fn ($error) => str_contains($error, 'DEBE SELECCIONAR AL MENOS UN ITEM'));
    }

    public function test_voids_and_reactivates_a_sale(): void
    {
        $id = $this->sell(['FLAN' => 1])['result'];

        $this->ajax('post', 'pos.sales.void', ['id' => $id, 'status' => 'ANULAR'])->assertJson(['error' => '']);
        $this->assertDatabaseHas('sales', ['id' => $id, 'status' => 'INACTIVE', 'voided' => 'S']);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $id, 'voided' => 'S']);

        $this->ajax('post', 'pos.sales.void', ['id' => $id, 'status' => 'ACTIVAR']);
        $this->assertDatabaseHas('sales', ['id' => $id, 'status' => 'ACTIVE', 'voided' => '']);
    }

    public function test_history_and_data_of_print_use_the_names_legacy(): void
    {
        $id = $this->sell(['FLAN' => 2])['result'];

        $this->ajax('get', 'pos.sales.history', ['dateHistory' => today()->toDateString()])
            ->assertJsonPath('result.0.id', $id)
            ->assertJsonPath('result.0.status', 'ACTIVE');

        $this->ajax('post', 'pos.sales.print', ['idSale' => $id])
            ->assertJsonMissingPath('error')
            ->assertJsonPath('listSale.0.sale_date', today()->format('d/m/Y'))
            ->assertJsonPath('listDSale.0.product_name', 'FLAN')
            ->assertJsonPath('listRestaurant.0.authorization_number', '29040011007');
    }

    public function test_totals_and_closing_of_cash_register(): void
    {
        $this->sell(['FLAN' => 1]);
        $voided = $this->sell(['HELADO' => 1])['result'];
        $this->ajax('post', 'pos.sales.void', ['id' => $voided, 'status' => 'ANULAR']);
        $this->ajax('post', 'pos.expenses.store', ['typeExpense' => 'EGRESO', 'date' => today()->toDateString(), 'concept' => 'GAS', 'amount' => 5, 'shift' => 'NOCHE']);

        $this->ajax('post', 'pos.cash_register.closing', ['countedCash' => 5])
            ->assertJsonPath('result.0.invoiced', '22.00')
            ->assertJsonPath('result.0.cash', '10.00')
            ->assertJsonPath('result.0.voided', '12.00')
            ->assertJsonPath('result.0.expenses', '5.00')
            ->assertJsonPath('result.0.expected', '5.00')
            ->assertJsonPath('result.0.difference', '0.00');

        $this->assertSame(0, Sale::where('closed', '<>', 'S')->count());
        $this->assertSame(0, InvoiceSequence::first()->daily_order);
    }
}
