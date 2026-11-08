<?php

namespace Tests\Feature\Pos;

use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Sale;
use App\Models\Staff;
use App\Support\AmountInWords;

/**
 * Comandas y precuentas que el POS imprime en la ticketera USB desde el navegador.
 * En los datos demo sólo los PLATOS van a Cocina.
 */
class PrintTest extends PosTestCase
{
    private DiningTable $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->log_in();
        $this->table = DiningTable::orderBy('id')->firstOrFail();
    }

    private function products(array $products): array
    {
        $ids = Product::whereIn('product_name', array_keys($products))->pluck('id', 'product_name');

        return [
            'idTable' => $this->table->id,
            'idProduct' => array_map(fn ($n) => $ids[$n], array_keys($products)),
            'quantity' => array_values($products),
            'saleDate' => today()->toDateString(),
        ];
    }

    private function open(array $products, array $notes = []): int
    {
        return $this->ajax('post', 'pos.diningTables.open', $this->products($products) + [
            'idWaiter' => Staff::first()->id,
            'waiters_no_code' => 'waiters_no_code',
            'note' => $notes,
            'shift' => 'NOCHE',
            'qtyCustomer' => '2',
            'dining_room' => 'no_dining_room',
        ])->assertJsonPath('error', '')->json('result');
    }

    public function test_the_table_order_only_prints_the_products_of_each_station(): void
    {
        $ticket = $this->open(['SILPANCHO' => 2, 'FLAN' => 1], ['sin huevo', '']);

        $this->get(route('pos.print.table-order', ['table' => $this->table->id, 'ticket' => $ticket, 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('data-empty="0"', false)
            ->assertSee('COCINA')
            ->assertSee('MESA '.$this->table->table_number)
            ->assertSee('SILPANCHO')
            ->assertSee('→ SIN HUEVO')
            ->assertDontSee('FLAN')
            ->assertDontSee('ADICIONAL')
            ->assertDontSee('Bs');
    }

    public function test_an_added_order_is_marked_additional_and_only_has_the_new_products(): void
    {
        $this->open(['SILPANCHO' => 1]);
        $ticket = $this->ajax('post', 'pos.diningTables.add', $this->products(['PIQUE MACHO' => 1]) + ['numberTable' => $this->table->table_number])
            ->assertJsonPath('error', '')->json('result');

        $this->get(route('pos.print.table-order', ['table' => $this->table->id, 'ticket' => $ticket, 'date' => today()->toDateString()]))
            ->assertSee('ADICIONAL')
            ->assertSee('PIQUE MACHO')
            ->assertDontSee('SILPANCHO');
    }

    public function test_one_sheet_per_station(): void
    {
        $flan = Product::where('product_name', 'FLAN')->value('id');
        BranchProduct::where('product_id', $flan)->update(['print_station_1' => 'x']);

        $ticket = $this->open(['SILPANCHO' => 1, 'FLAN' => 1]);

        $html = $this->get(route('pos.print.table-order', ['table' => $this->table->id, 'ticket' => $ticket, 'date' => today()->toDateString()]))
            ->assertSee('COCINA')->assertSee('PUNTO 1')->getContent();
        $this->assertSame(2, substr_count($html, '<div class="ticket">'));
    }

    public function test_without_kitchen_products_there_is_nothing_to_print(): void
    {
        $ticket = $this->open(['FLAN' => 1]);

        $this->get(route('pos.print.table-order', ['table' => $this->table->id, 'ticket' => $ticket, 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('data-empty="1"', false);
    }

    public function test_the_pre_bill_has_prices_and_total(): void
    {
        $this->open(['SILPANCHO' => 2, 'FLAN' => 1]);
        $silpancho = (float) Product::where('product_name', 'SILPANCHO')->value('sale_price');
        $flan = (float) Product::where('product_name', 'FLAN')->value('sale_price');

        $this->get(route('pos.print.pre-bill', ['table' => $this->table->id, 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('PRECUENTA')
            ->assertSee('SILPANCHO')
            ->assertSee('FLAN')
            ->assertSee(number_format($silpancho * 2 + $flan, 2))
            ->assertSee('NO VÁLIDO COMO FACTURA');
    }

    public function test_the_sale_order_prints_the_kitchen_products(): void
    {
        $product = Product::where('product_name', 'SILPANCHO')->firstOrFail();
        $sale = $this->ajax('post', 'pos.sales.store', [
            'idProduct' => [$product->id], 'quantity' => [1], 'price' => [$product->sale_price], 'subTotal' => [$product->sale_price],
            'note' => ['bien cocido'], 'qr' => [''], 'nameProduct' => ['SILPANCHO'],
            'nameCustomer' => 'SIN NOMBRE', 'customerTaxId' => '', 'totalBs' => $product->sale_price, 'totalInvoiceBs' => $product->sale_price,
            'changeBs' => 0, 'paymentMethod' => 'CONTADO', 'shift' => 'NOCHE', 'exitProduct' => 'LLEVAR', 'saleDate' => today()->toDateString(),
        ])->assertJsonPath('error', '')->json('result');

        $this->get(route('pos.print.sale-order', ['sale' => $sale]))
            ->assertOk()
            ->assertSee('ORDEN N°')
            ->assertSee('LLEVAR')
            ->assertSee('SILPANCHO');
    }

    private function sell(string $name, array $extra = []): int
    {
        $product = Product::where('product_name', $name)->firstOrFail();

        return $this->ajax('post', 'pos.sales.store', array_merge([
            'idProduct' => [$product->id], 'quantity' => [2], 'price' => [$product->sale_price], 'subTotal' => [$product->sale_price * 2],
            'note' => [''], 'qr' => [''], 'nameProduct' => [$name],
            'nameCustomer' => 'SIN NOMBRE', 'customerTaxId' => '', 'totalBs' => $product->sale_price * 2, 'totalInvoiceBs' => 100,
            'changeBs' => 100 - $product->sale_price * 2, 'paymentMethod' => 'CONTADO', 'shift' => 'NOCHE', 'exitProduct' => 'LLEVAR',
            'saleDate' => today()->toDateString(),
        ], $extra))->assertJsonPath('error', '')->json('result');
    }

    public function test_the_sales_note_without_billing(): void
    {
        Restaurant::firstOrFail()->update(['options' => ['billing' => false]]);
        $sale = Sale::findOrFail($this->sell('SILPANCHO'));
        $total = (float) $sale->invoice_total;

        $this->get(route('pos.print.receipt', ['sale' => $sale->id]))
            ->assertOk()
            ->assertSee('NOTA DE VENTA')
            ->assertSee('Este documento no es una factura')
            ->assertSee('SILPANCHO')
            ->assertSee(number_format($total, 2))
            ->assertSee(AmountInWords::bolivianos($total))
            ->assertSee('Recibido Bs')
            ->assertDontSee('Código de control')
            ->assertDontSee('<svg', false)
            ->assertDontSee('COPIA');

        $this->get(route('pos.print.receipt', ['sale' => $sale->id, 'copy' => 1]))->assertSee('*** COPIA ***');
    }

    public function test_with_the_authorization_deactivated_prints_a_sales_note(): void
    {
        BillingAuthorization::first()->update(['inactive' => true]);
        $sale = Sale::findOrFail($this->sell('SILPANCHO', ['nameCustomer' => 'ANA ROJAS', 'customerTaxId' => '1234567']));

        $this->get(route('pos.print.receipt', ['sale' => $sale->id]))
            ->assertOk()
            ->assertSee('NOTA DE VENTA')
            ->assertSee('Este documento no es una factura')
            ->assertDontSee('FACTURA')
            ->assertDontSee('Código de control')
            ->assertDontSee('Autorización')
            ->assertDontSee('<svg', false);
    }

    public function test_the_invoice_with_billing_has_control_code_and_qr(): void
    {
        $sale = Sale::findOrFail($this->sell('SILPANCHO', ['nameCustomer' => 'ANA ROJAS', 'customerTaxId' => '1234567']));

        $this->get(route('pos.print.receipt', ['sale' => $sale->id]))
            ->assertOk()
            ->assertSee('FACTURA')
            ->assertSee($sale->control_code)
            ->assertSee($sale->authorization_number)
            ->assertSee('ANA ROJAS')
            ->assertSee('1234567')
            ->assertSee('<svg', false)
            ->assertDontSee('NOTA DE VENTA');
    }

    public function test_a_voided_sale_prints_as_voided(): void
    {
        $sale = Sale::findOrFail($this->sell('FLAN'));
        $sale->update(['status' => 'INACTIVE']);

        $this->get(route('pos.print.receipt', ['sale' => $sale->id]))->assertSee('*** ANULADA ***');
    }

    public function test_amount_in_words(): void
    {
        $this->assertSame('OCHENTA Y CINCO 00/100 BOLIVIANOS', AmountInWords::bolivianos(85));
        $this->assertSame('CIEN 50/100 BOLIVIANOS', AmountInWords::bolivianos(100.5));
        $this->assertSame('CIENTO VEINTIUN 05/100 BOLIVIANOS', AmountInWords::bolivianos(121.05));
        $this->assertSame('MIL DOSCIENTOS QUINCE 00/100 BOLIVIANOS', AmountInWords::bolivianos(1215));
        $this->assertSame('DOS MIL UN 00/100 BOLIVIANOS', AmountInWords::bolivianos(2001));
        $this->assertSame('UN MILLÓN QUINIENTOS MIL 00/100 BOLIVIANOS', AmountInWords::bolivianos(1500000));
        $this->assertSame('CERO 90/100 BOLIVIANOS', AmountInWords::bolivianos(0.9));
    }

    public function test_uses_the_paper_width_of_the_branch(): void
    {
        $this->open(['SILPANCHO' => 1]);
        $url = route('pos.print.pre-bill', ['table' => $this->table->id, 'date' => today()->toDateString()]);

        $this->get($url)->assertSee('width: 72mm', false);
        Branch::query()->update(['paper_width' => 58]);
        $this->get($url)->assertSee('width: 48mm', false);
    }

    public function test_cannot_print_tables_of_another_branch(): void
    {
        $other = Branch::create(['name' => 'NORTE', 'status' => 'ACTIVE']);
        $table = DiningTable::create(['table_number' => 'N1', 'branch_id' => $other->id, 'status' => 'ACTIVE']);

        $this->get(route('pos.print.pre-bill', ['table' => $table->id, 'date' => today()->toDateString()]))->assertNotFound();
    }
}
