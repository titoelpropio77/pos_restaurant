<?php

namespace Tests\Feature\Pos;

use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Staff;
use App\Models\TableOrder;

class TableTest extends PosTestCase
{
    private DiningTable $table1;

    private DiningTable $table2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->log_in();
        [$this->table1, $this->table2] = DiningTable::orderBy('id')->take(2)->get()->all();
    }

    private function open(DiningTable $diningTable, array $products): array
    {
        $ids = Product::whereIn('product_name', array_keys($products))->pluck('id', 'product_name');

        return $this->ajax('post', 'pos.diningTables.open', [
            'idTable' => $diningTable->id,
            'idWaiter' => Staff::first()->id,
            'waiters_no_code' => 'waiters_no_code',
            'idProduct' => array_map(fn ($n) => $ids[$n], array_keys($products)),
            'quantity' => array_values($products),
            'saleDate' => today()->toDateString(),
            'shift' => 'NOCHE',
            'qtyCustomer' => '4',
            'dining_room' => 'no_dining_room',
        ])->json();
    }

    public function test_opens_the_table_with_the_color_of_waiter(): void
    {
        $this->assertSame(['error' => '', 'result' => 1], $this->open($this->table1, ['SILPANCHO' => 2]));

        $diningTable = $this->table1->fresh();
        $this->assertSame('S', $diningTable->occupied);
        $this->assertSame(Staff::first()->staff_color, $diningTable->color);
        $this->assertSame('4', $diningTable->people_count);

        $this->assertSame('LA MESA YA ESTÁ OCUPADA', $this->open($this->table1, ['FLAN' => 1])['error']);
    }

    public function test_the_detail_uses_the_names_legacy_and_skips_the_closed(): void
    {
        $this->open($this->table1, ['SILPANCHO' => 2, 'FLAN' => 1]);

        $detail = $this->ajax('get', 'pos.diningTables.detail', ['idTable' => $this->table1->id, 'saleDate' => today()->toDateString()])->json('result');

        $this->assertCount(2, $detail);
        $this->assertSame($this->table1->id, $detail[0]['table_number']);
        $this->assertSame('', $detail[0]['time_end']);
    }

    public function test_transfers_partially_and_merges_items_equal(): void
    {
        $this->open($this->table1, ['COCA COLA 2L' => 3]);
        $item = TableOrder::first();
        $data = fn (string $status) => [
            'idTableDecrease' => $this->table1->id, 'idTableTransfer' => $this->table2->id,
            'idItem' => [$item->id], 'quantity' => [1], 'date' => today()->toDateString(),
            'statusTable' => $status, 'enableTable' => 0, 'dining_room' => 'no_dining_room',
        ];

        $this->ajax('post', 'pos.diningTables.transfer', $data('open'))->assertJson(['error' => '']);
        $this->ajax('post', 'pos.diningTables.transfer', $data('occupied'))->assertJson(['error' => '']);

        $this->assertEquals(1, $item->fresh()->quantity);
        $this->assertEquals(2, TableOrder::where('dining_table_id', $this->table2->id)->sole()->quantity);
        $this->assertSame('S', $this->table2->fresh()->occupied);

        $this->ajax('post', 'pos.diningTables.transfer', array_merge($data('occupied'), ['quantity' => [5]]))
            ->assertJson(['error' => 'NO EXISTE ESA CANTIDAD EN ESA MESA']);
    }

    public function test_checkout_total_releases_the_table(): void
    {
        $this->open($this->table1, ['SILPANCHO' => 1]);
        $item = TableOrder::first();

        $this->ajax('post', 'pos.diningTables.processing', ['idTable' => $this->table1->id]);
        $this->assertSame('S', $this->table1->fresh()->processing);

        $this->ajax('post', 'pos.diningTables.paid', ['idTable' => $this->table1->id, 'optionItemTable' => 2, 'idDetail' => [$item->id], 'quantity' => [1]]);

        $this->assertFalse($this->table1->fresh()->isOccupied());
        $this->assertSame(['A', 'S'], [$item->fresh()->closed, $item->fresh()->deleted_flag]);
    }

    public function test_delete_quantity_registers_the_item_deleted(): void
    {
        $this->open($this->table1, ['SILPANCHO' => 3]);
        $item = TableOrder::first();

        $this->ajax('post', 'pos.diningTables.delete-quantity', ['idTable' => $this->table1->id, 'idDetail' => [$item->id], 'quantity' => [2]])
            ->assertJson(['error' => '']);

        $this->assertEquals(1, $item->fresh()->quantity);
        $this->ajax('get', 'pos.diningTables.items-deleted', ['date' => today()->toDateString()])
            ->assertJsonPath('result.0.product_name', 'SILPANCHO')
            ->assertJsonPath('result.0.quantity', '2.00');
    }

    public function test_void_table_closes_the_items_and_the_releases(): void
    {
        $this->open($this->table1, ['SILPANCHO' => 1, 'FLAN' => 2]);

        $this->ajax('post', 'pos.diningTables.void', ['idTable' => $this->table1->id, 'saleDate' => today()->toDateString()]);

        $this->assertSame(0, TableOrder::pending()->count());
        $this->assertFalse($this->table1->fresh()->isOccupied());
    }
}
