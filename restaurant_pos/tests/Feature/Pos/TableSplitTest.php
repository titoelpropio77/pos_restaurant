<?php

namespace Tests\Feature\Pos;

use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Staff;
use App\Models\TableOrder;

/**
 * Apertura de mesas sin productos y cuenta compartida (cobro de la mesa en partes).
 */
class TableSplitTest extends PosTestCase
{
    private DiningTable $diningTable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->log_in();
        $this->diningTable = DiningTable::orderBy('id')->firstOrFail();
    }

    private function open(array $products): array
    {
        $ids = Product::whereIn('product_name', array_keys($products))->pluck('id', 'product_name');

        return $this->ajax('post', 'pos.diningTables.open', [
            'idTable' => $this->diningTable->id,
            'idWaiter' => Staff::first()->id,
            'idProduct' => array_map(fn ($n) => $ids[$n], array_keys($products)),
            'quantity' => array_values($products),
            'saleDate' => today()->toDateString(),
            'qtyCustomer' => '2',
            'dining_room' => 'no_dining_room',
        ])->json();
    }

    private function item(string $name): TableOrder
    {
        return TableOrder::where('dining_table_id', $this->diningTable->id)->where('product_name', $name)->firstOrFail();
    }

    private function charge(array $data): array
    {
        return $this->ajax('post', 'pos.diningTables.split-charge', $data + [
            'idTable' => $this->diningTable->id,
            'saleDate' => today()->toDateString(),
        ])->json();
    }

    public function test_opens_the_table_without_products_and_the_next_order_keeps_the_waiter(): void
    {
        $this->assertSame(['error' => '', 'result' => 0], $this->open([]));

        $diningTable = $this->diningTable->fresh();
        $this->assertTrue($diningTable->isOccupied());
        $this->assertSame(Staff::first()->staff_name, $diningTable->waiter_name);

        $this->ajax('post', 'pos.diningTables.add', [
            'idTable' => $diningTable->id,
            'idProduct' => [Product::where('product_name', 'FLAN')->value('id')],
            'quantity' => [1],
            'saleDate' => today()->toDateString(),
        ])->assertJsonPath('result', 1);

        $this->assertSame(Staff::first()->staff_name, $this->item('FLAN')->staff_name);

        $diningTable->fresh()->release();
        $this->assertSame('', $diningTable->fresh()->waiter_name);
    }

    public function test_equal_parts_charge_each_customer_separately_and_release_the_table(): void
    {
        $this->open(['SILPANCHO' => 2, 'FLAN' => 1]); // 70 + 10 = 80

        $preview = $this->ajax('get', 'pos.diningTables.split-preview', [
            'idTable' => $this->diningTable->id, 'saleDate' => today()->toDateString(), 'mode' => 'equal', 'parts' => 3,
        ])->json('result');
        $this->assertSame(80, (int) $preview['pending']);
        $this->assertEqualsWithDelta(26.75, $preview['total'], 0.001); // 0.67 x 35 + 0.33 x 10

        $first = $this->charge(['mode' => 'equal', 'parts' => 2, 'nameCustomer' => 'ANA', 'customerTaxId' => '123', 'paymentMethod' => 'CONTADO', 'received' => 50]);
        $this->assertSame('', $first['error']);
        $this->assertEqualsWithDelta(40, $first['result']['total'], 0.001);
        $this->assertEqualsWithDelta(10, $first['result']['change'], 0.001);
        $this->assertEqualsWithDelta(40, $first['result']['pending'], 0.001);
        $this->assertFalse($first['result']['closed']);
        $this->assertTrue($this->diningTable->fresh()->isOccupied());

        $second = $this->charge(['mode' => 'equal', 'parts' => 1, 'nameCustomer' => 'LUIS', 'customerTaxId' => '456', 'paymentMethod' => 'QR']);
        $this->assertSame('', $second['error']);
        $this->assertEqualsWithDelta(40, $second['result']['total'], 0.001);
        $this->assertTrue($second['result']['closed']);
        $this->assertFalse($this->diningTable->fresh()->isOccupied());

        $sales = Sale::orderBy('id')->get(['customer_name', 'customer_tax_id', 'payment_method', 'invoice_total']);
        $this->assertSame(['ANA', 'LUIS'], $sales->pluck('customer_name')->all());
        $this->assertSame(['123', '456'], $sales->pluck('customer_tax_id')->all());
        $this->assertSame(['CONTADO', 'QR'], $sales->pluck('payment_method')->all());
        $this->assertEqualsWithDelta(80, $sales->sum('invoice_total'), 0.001);
        $this->assertSame(0, TableOrder::where('dining_table_id', $this->diningTable->id)->pending()->count());
    }

    public function test_by_items_each_customer_pays_what_they_consumed(): void
    {
        $this->open(['SILPANCHO' => 2, 'FLAN' => 1]);
        $silpancho = $this->item('SILPANCHO');
        $flan = $this->item('FLAN');

        $first = $this->charge(['mode' => 'items', 'items' => [['id' => $silpancho->id, 'quantity' => 1], ['id' => $flan->id, 'quantity' => 1]], 'paymentMethod' => 'CONTADO']);
        $this->assertEqualsWithDelta(45, $first['result']['total'], 0.001);
        $this->assertEqualsWithDelta(35, $first['result']['pending'], 0.001);
        $this->assertSame('SIN NOMBRE', Sale::latest('id')->value('customer_name'));

        $this->assertSame(
            'NO EXISTE ESA CANTIDAD DE SILPANCHO EN LA MESA',
            $this->charge(['mode' => 'items', 'items' => [['id' => $silpancho->id, 'quantity' => 2]], 'paymentMethod' => 'CONTADO'])['error'],
        );

        $last = $this->charge(['mode' => 'items', 'items' => [['id' => $silpancho->id, 'quantity' => 1]], 'nameCustomer' => 'LUIS', 'paymentMethod' => 'CREDITO']);
        $this->assertTrue($last['result']['closed']);
        $this->assertSame(2, Sale::count());
    }

    public function test_cash_received_must_cover_the_part(): void
    {
        $this->open(['SILPANCHO' => 1]);

        $this->assertSame(
            'EL MONTO RECIBIDO ES MENOR AL TOTAL DE LA PARTE',
            $this->charge(['mode' => 'equal', 'parts' => 1, 'paymentMethod' => 'CONTADO', 'received' => 20])['error'],
        );
        $this->assertSame(0, Sale::count());
        $this->assertEqualsWithDelta(1, (float) $this->item('SILPANCHO')->quantity, 0.001);
    }
}
