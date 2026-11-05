<?php

namespace Tests\Feature\Admin;

use App\Models\Almacen;
use App\Models\Insumo;
use App\Models\InsumoAlmacen;
use App\Models\KardexInsumo;
use App\Models\Sucursal;

class InventarioTest extends AdminTestCase
{
    private Insumo $limon;

    private Almacen $principal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limon = Insumo::where('nom_insumo', 'LIMÓN')->firstOrFail();
        $this->principal = Almacen::firstOrFail();
        $this->limon->update(['ppm' => 1]);
    }

    private function movimiento(string $tipo, float $cantidad, array $extra = [])
    {
        return $this->post(route('admin.inventario.store'), array_merge([
            'tipo' => $tipo,
            'fecha' => today()->toDateString(),
            'almacen_id' => $this->principal->id,
            'lineas' => [['insumo_id' => $this->limon->id, 'cantidad' => $cantidad, 'costo' => $extra['costo'] ?? null]],
        ], $extra));
    }

    private function stock(?Almacen $almacen = null): float
    {
        return (float) InsumoAlmacen::where(['insumo_id' => $this->limon->id, 'almacen_id' => ($almacen ?? $this->principal)->id])->value('stock_actual');
    }

    public function test_la_compra_suma_stock_y_recalcula_el_costo_promedio(): void
    {
        // Stock inicial demo: 100 a Bs 1 => compra 100 a Bs 2 => promedio 1.5
        $this->movimiento('COMPRA', 100, ['costo' => 2])->assertSessionHasNoErrors();

        $this->assertEquals(200, $this->stock());
        $this->assertEquals(1.5, (float) $this->limon->fresh()->ppm);
        $this->assertDatabaseHas('kardex_insumos', ['insumo_id' => $this->limon->id, 'movimiento' => 'COMPRA', 'entrada' => 100, 'saldo' => 200]);
    }

    public function test_la_salida_no_deja_stock_negativo(): void
    {
        $this->movimiento('SALIDA', 500)->assertSessionHas('error');

        $this->assertEquals(100, $this->stock());
        $this->assertSame(0, KardexInsumo::count());
    }

    public function test_el_traspaso_mueve_entre_almacenes(): void
    {
        $destino = Almacen::create(['nombre' => 'BARRA', 'sucursal_id' => Sucursal::first()->id]);

        $this->movimiento('TRASPASO', 30, ['almacen_destino_id' => $destino->id])->assertSessionHasNoErrors();

        $this->assertEquals(70, $this->stock());
        $this->assertEquals(30, $this->stock($destino));
        $this->assertSame(['TRASP-SAL', 'TRASP-ENT'], KardexInsumo::orderBy('id')->pluck('movimiento')->all());
    }

    public function test_el_ajuste_registra_la_diferencia_contra_el_conteo(): void
    {
        $this->movimiento('AJUSTE', 92)->assertSessionHasNoErrors();

        $this->assertEquals(92, $this->stock());
        $this->assertDatabaseHas('kardex_insumos', ['movimiento' => 'AJUSTE', 'salida' => 8, 'saldo' => 92]);
    }

    public function test_el_kardex_muestra_los_movimientos(): void
    {
        $this->movimiento('COMPRA', 10, ['costo' => 1]);

        $this->get(route('admin.inventario.kardex', ['insumo' => $this->limon->id]))->assertOk()->assertSee('COMPRA');
        $this->get(route('admin.inventario.stock', ['exportar' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
