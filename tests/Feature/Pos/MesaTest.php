<?php

namespace Tests\Feature\Pos;

use App\Models\Mesa;
use App\Models\MesaPedido;
use App\Models\Personal;
use App\Models\Producto;

class MesaTest extends PosTestCase
{
    private Mesa $mesa1;

    private Mesa $mesa2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingresar();
        [$this->mesa1, $this->mesa2] = Mesa::orderBy('id')->take(2)->get()->all();
    }

    private function abrir(Mesa $mesa, array $productos): array
    {
        $ids = Producto::whereIn('nom_prod', array_keys($productos))->pluck('id', 'nom_prod');

        return $this->ajax('post', 'pos.mesas.abrir', [
            'idMesa' => $mesa->id,
            'idMesero' => Personal::first()->id,
            'meserosc' => 'meserosc',
            'idProducto' => array_map(fn ($n) => $ids[$n], array_keys($productos)),
            'cantidad' => array_values($productos),
            'fechaVenta' => today()->toDateString(),
            'turno' => 'NOCHE',
            'cantCli' => '4',
            'salon' => 'nosalon',
        ])->json();
    }

    public function test_abre_la_mesa_con_el_color_del_mesero(): void
    {
        $this->assertSame(['error' => '', 'result' => 1], $this->abrir($this->mesa1, ['SILPANCHO' => 2]));

        $mesa = $this->mesa1->fresh();
        $this->assertSame('S', $mesa->uso);
        $this->assertSame(Personal::first()->coloremp, $mesa->colores);
        $this->assertSame('4', $mesa->nropersona);

        $this->assertSame('LA MESA YA ESTÁ OCUPADA', $this->abrir($this->mesa1, ['FLAN' => 1])['error']);
    }

    public function test_el_detalle_usa_los_nombres_legacy_y_omite_lo_cerrado(): void
    {
        $this->abrir($this->mesa1, ['SILPANCHO' => 2, 'FLAN' => 1]);

        $detalle = $this->ajax('get', 'pos.mesas.detalle', ['idMesa' => $this->mesa1->id, 'fechaVenta' => today()->toDateString()])->json('result');

        $this->assertCount(2, $detalle);
        $this->assertSame($this->mesa1->id, $detalle[0]['NRO_MESA']);
        $this->assertSame('', $detalle[0]['TIEMPO_FIN']);
    }

    public function test_traspasa_parcialmente_y_fusiona_items_iguales(): void
    {
        $this->abrir($this->mesa1, ['COCA COLA 2L' => 3]);
        $item = MesaPedido::first();
        $datos = fn (string $estado) => [
            'idMesaDisminuir' => $this->mesa1->id, 'idMesaTraspaso' => $this->mesa2->id,
            'idDet' => [$item->id], 'cantidad' => [1], 'fecha' => today()->toDateString(),
            'estadoMesa' => $estado, 'habilitarMesa' => 0, 'salon' => 'nosalon',
        ];

        $this->ajax('post', 'pos.mesas.traspasar', $datos('abierta'))->assertJson(['error' => '']);
        $this->ajax('post', 'pos.mesas.traspasar', $datos('ocupado'))->assertJson(['error' => '']);

        $this->assertEquals(1, $item->fresh()->cantidad);
        $this->assertEquals(2, MesaPedido::where('mesa_id', $this->mesa2->id)->sole()->cantidad);
        $this->assertSame('S', $this->mesa2->fresh()->uso);

        $this->ajax('post', 'pos.mesas.traspasar', array_merge($datos('ocupado'), ['cantidad' => [5]]))
            ->assertJson(['error' => 'NO EXISTE ESA CANTIDAD EN ESA MESA']);
    }

    public function test_cobro_total_libera_la_mesa(): void
    {
        $this->abrir($this->mesa1, ['SILPANCHO' => 1]);
        $item = MesaPedido::first();

        $this->ajax('post', 'pos.mesas.procesando', ['idMesa' => $this->mesa1->id]);
        $this->assertSame('S', $this->mesa1->fresh()->procesando);

        $this->ajax('post', 'pos.mesas.cobrada', ['idMesa' => $this->mesa1->id, 'opcionItemMesa' => 2, 'idDetalle' => [$item->id], 'cantidad' => [1]]);

        $this->assertFalse($this->mesa1->fresh()->estaOcupada());
        $this->assertSame(['A', 'S'], [$item->fresh()->cerrado, $item->fresh()->deleteado]);
    }

    public function test_eliminar_cantidad_registra_el_item_eliminado(): void
    {
        $this->abrir($this->mesa1, ['SILPANCHO' => 3]);
        $item = MesaPedido::first();

        $this->ajax('post', 'pos.mesas.eliminar-cantidad', ['idMesa' => $this->mesa1->id, 'idDetalle' => [$item->id], 'cantidad' => [2]])
            ->assertJson(['error' => '']);

        $this->assertEquals(1, $item->fresh()->cantidad);
        $this->ajax('get', 'pos.mesas.items-eliminados', ['fecha' => today()->toDateString()])
            ->assertJsonPath('result.0.NOM_PROD', 'SILPANCHO')
            ->assertJsonPath('result.0.CANTIDAD', '2.00');
    }

    public function test_anular_mesa_cierra_los_items_y_la_libera(): void
    {
        $this->abrir($this->mesa1, ['SILPANCHO' => 1, 'FLAN' => 2]);

        $this->ajax('post', 'pos.mesas.anular', ['idMesa' => $this->mesa1->id, 'fechaVenta' => today()->toDateString()]);

        $this->assertSame(0, MesaPedido::pendientes()->count());
        $this->assertFalse($this->mesa1->fresh()->estaOcupada());
    }
}
