<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\StockLedgerEntry;
use App\Models\Supply;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

class InventoryTest extends AdminTestCase
{
    private Supply $limon;

    private Warehouse $main;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limon = Supply::where('supply_name', 'LIMÓN')->firstOrFail();
        $this->main = Warehouse::firstOrFail();
        $this->limon->update(['average_cost' => 1]);
    }

    private function movement_type(string $type, float $quantity, array $extra = [])
    {
        return $this->post(route('admin.inventory.store'), array_merge([
            'type' => $type,
            'date' => today()->toDateString(),
            'warehouse_id' => $this->main->id,
            'lines' => [['supply_id' => $this->limon->id, 'quantity' => $quantity, 'cost' => $extra['cost'] ?? null]],
        ], $extra));
    }

    public function test_the_form_uses_the_names_the_controller_validates(): void
    {
        $html = $this->get(route('admin.inventory.create'))->assertOk()->getContent();

        foreach (['lines[${i}][supply_id]', 'lines[${i}][quantity]', 'lines[${i}][cost]', 'name="destination_warehouse_id"'] as $field) {
            $this->assertStringContainsString($field, $html);
        }
    }

    public function test_a_transfer_requires_its_destination(): void
    {
        $this->movement_type('TRASPASO', 1)->assertSessionHasErrors('destination_warehouse_id');
    }

    private function stock(?Warehouse $warehouse = null): float
    {
        return (float) WarehouseStock::where(['supply_id' => $this->limon->id, 'warehouse_id' => ($warehouse ?? $this->main)->id])->value('current_stock');
    }

    public function test_the_purchase_adds_stock_and_recalculates_the_cost_average(): void
    {
        // Stock inicial demo: 100 a Bs 1 => compra 100 a Bs 2 => promedio 1.5
        $this->movement_type('COMPRA', 100, ['cost' => 2])->assertSessionHasNoErrors();

        $this->assertEquals(200, $this->stock());
        $this->assertEquals(1.5, (float) $this->limon->fresh()->average_cost);
        $this->assertDatabaseHas('stock_ledger', ['supply_id' => $this->limon->id, 'movement_type' => 'COMPRA', 'qty_in' => 100, 'balance' => 200]);
    }

    public function test_the_exit_not_leaves_stock_negative(): void
    {
        $this->movement_type('SALIDA', 500)->assertSessionHas('error');

        $this->assertEquals(100, $this->stock());
        $this->assertSame(0, StockLedgerEntry::count());
    }

    public function test_the_transfer_moves_between_warehouses(): void
    {
        $destination = Warehouse::create(['name' => 'BARRA', 'branch_id' => Branch::first()->id]);

        $this->movement_type('TRASPASO', 30, ['destination_warehouse_id' => $destination->id])->assertSessionHasNoErrors();

        $this->assertEquals(70, $this->stock());
        $this->assertEquals(30, $this->stock($destination));
        $this->assertSame(['TRASP-SAL', 'TRASP-ENT'], StockLedgerEntry::orderBy('id')->pluck('movement_type')->all());
    }

    public function test_the_adjustment_registers_the_difference_against_the_count(): void
    {
        $this->movement_type('AJUSTE', 92)->assertSessionHasNoErrors();

        $this->assertEquals(92, $this->stock());
        $this->assertDatabaseHas('stock_ledger', ['movement_type' => 'AJUSTE', 'qty_out' => 8, 'balance' => 92]);
    }

    public function test_the_kardex_shows_the_movements(): void
    {
        $this->movement_type('COMPRA', 10, ['cost' => 1]);

        $this->get(route('admin.inventory.kardex', ['supply' => $this->limon->id]))->assertOk()->assertSee('COMPRA');
        $this->get(route('admin.inventory.stock', ['export' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
