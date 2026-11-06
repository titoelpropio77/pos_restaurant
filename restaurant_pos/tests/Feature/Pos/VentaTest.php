<?php

namespace Tests\Feature\Pos;

use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\InsumoAlmacen;
use App\Models\Producto;
use App\Models\Venta;

class VentaTest extends PosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ingresar();
    }

    private function vender(array $productos, array $extra = []): array
    {
        $datos = ['idProducto' => [], 'cantidad' => [], 'precio' => [], 'subTotal' => [], 'nota' => [], 'qr' => [], 'nombreProduc' => []];
        $total = 0;
        foreach ($productos as $nombre => $cantidad) {
            $producto = Producto::where('nom_prod', $nombre)->firstOrFail();
            $datos['idProducto'][] = $producto->id;
            $datos['cantidad'][] = $cantidad;
            $datos['precio'][] = $producto->pre_venta;
            $datos['subTotal'][] = $cantidad * $producto->pre_venta;
            $datos['nota'][] = '';
            $datos['qr'][] = '';
            $datos['nombreProduc'][] = $nombre;
            $total += $cantidad * $producto->pre_venta;
        }

        return $this->ajax('post', 'pos.ventas.store', array_merge($datos, [
            'nombreCli' => 'SIN NOMBRE',
            'nitCli' => '',
            'totalBs' => $total,
            'totalFacturaBs' => $total,
            'cambioBs' => 0,
            'formaPago' => 'CONTADO',
            'turno' => 'NOCHE',
            'salidaProducto' => 'LLEVAR',
            'fechaVenta' => today()->toDateString(),
        ], $extra))->assertOk()->json();
    }

    public function test_registra_la_venta_con_su_detalle_y_codigo_de_control(): void
    {
        $respuesta = $this->vender(['SILPANCHO' => 2, 'COCA COLA 2L' => 1]);

        $this->assertSame('', $respuesta['error']);
        $venta = Venta::with('detalles')->findOrFail($respuesta['result']);
        $this->assertSame(1, $respuesta['result2']);
        $this->assertEquals(85, $venta->total_fact);
        $this->assertSame($venta->id, (int) $venta->factura);
        $this->assertCount(2, $venta->detalles);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}-){3,4}[0-9A-F]{2}$/', $venta->cifrado);
        $this->assertSame(1, Correlativo::first()->ordendia);
    }

    public function test_sin_nit_avanza_el_recibo_y_con_nit_avanza_la_factura(): void
    {
        $this->vender(['FLAN' => 1]);
        $this->assertSame([0, 1], [Correlativo::first()->folio, Correlativo::first()->folior]);

        $respuesta = $this->vender(['FLAN' => 1], ['nitCli' => '4189179011', 'nombreCli' => 'JUAN']);
        $this->assertSame([1, 1], [Correlativo::first()->folio, Correlativo::first()->folior]);
        $this->assertSame(1, $respuesta['nroFac']);
        $this->assertDatabaseHas('clientes', ['nom_cli' => 'JUAN', 'nit_cli' => '4189179011']);
    }

    public function test_cada_mult_recibos_consume_un_numero_de_factura(): void
    {
        Correlativo::first()->update(['mult' => 2, 'multcon' => 0]);

        $this->vender(['FLAN' => 1]);
        $this->assertSame([0, 1, 1], [Correlativo::first()->folio, Correlativo::first()->folior, Correlativo::first()->multcon]);

        $this->vender(['FLAN' => 1]);
        $this->assertSame([1, 1, 0], [Correlativo::first()->folio, Correlativo::first()->folior, Correlativo::first()->multcon]);
    }

    public function test_descuenta_los_insumos_de_la_receta_y_registra_el_kardex(): void
    {
        $limon = InsumoAlmacen::whereHas('insumo', fn ($q) => $q->where('nom_insumo', 'LIMÓN'))->first();
        $stockInicial = (float) $limon->stock_actual;

        $respuesta = $this->vender(['LIMONADA' => 3]);

        $this->assertEquals($stockInicial - 6, (float) $limon->fresh()->stock_actual);
        $this->assertDatabaseHas('kardex_insumos', ['venta_id' => $respuesta['result'], 'insumo_id' => $limon->insumo_id, 'salida' => 6]);
        $this->assertDatabaseCount('inventarios', 1);
    }

    public function test_no_vende_con_la_dosificacion_vencida(): void
    {
        Dosificacion::first()->update(['fecha_lim' => today()->subDay()]);

        $respuesta = $this->vender(['FLAN' => 1]);

        $this->assertSame('SU DOSIFICACIÓN YA SE VENCIÓ, NO PODRÁ VENDER', $respuesta['error']);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_valida_los_datos_de_la_venta(): void
    {
        $this->ajax('post', 'pos.ventas.store', ['totalBs' => 0])
            ->assertOk()
            ->assertJsonPath('error', fn ($error) => str_contains($error, 'DEBE SELECCIONAR AL MENOS UN ITEM'));
    }

    public function test_anula_y_reactiva_una_venta(): void
    {
        $id = $this->vender(['FLAN' => 1])['result'];

        $this->ajax('post', 'pos.ventas.anular', ['id' => $id, 'estado' => 'ANULAR'])->assertJson(['error' => '']);
        $this->assertDatabaseHas('ventas', ['id' => $id, 'estado' => 'INACTIVO', 'anulado' => 'S']);
        $this->assertDatabaseHas('venta_detalles', ['venta_id' => $id, 'anulado' => 'S']);

        $this->ajax('post', 'pos.ventas.anular', ['id' => $id, 'estado' => 'ACTIVAR']);
        $this->assertDatabaseHas('ventas', ['id' => $id, 'estado' => 'ACTIVO', 'anulado' => '']);
    }

    public function test_historico_y_datos_de_impresion_usan_los_nombres_legacy(): void
    {
        $id = $this->vender(['FLAN' => 2])['result'];

        $this->ajax('get', 'pos.ventas.historico', ['fechaHistorico' => today()->toDateString()])
            ->assertJsonPath('result.0.ID', $id)
            ->assertJsonPath('result.0.ESTADO', 'ACTIVO');

        $this->ajax('post', 'pos.ventas.impresion', ['idVenta' => $id])
            ->assertJsonMissingPath('error')
            ->assertJsonPath('listaVenta.0.F_VENTA', today()->format('d/m/Y'))
            ->assertJsonPath('listaDVenta.0.NOM_PROD', 'FLAN')
            ->assertJsonPath('listaEmpresa.0.NROAUTORI', '29040011007');
    }

    public function test_totales_y_cierre_de_caja(): void
    {
        $this->vender(['FLAN' => 1]);
        $anulada = $this->vender(['HELADO' => 1])['result'];
        $this->ajax('post', 'pos.ventas.anular', ['id' => $anulada, 'estado' => 'ANULAR']);
        $this->ajax('post', 'pos.gastos.store', ['tipoGasto' => 'EGRESO', 'fecha' => today()->toDateString(), 'concepto' => 'GAS', 'monto' => 5, 'turno' => 'NOCHE']);

        $this->ajax('get', 'pos.caja.totales', ['fecha' => today()->toDateString(), 'turno' => 'MAÑANA Y NOCHE', 'empleado' => ''])
            ->assertJsonPath('result.0.facturado', '22.00')
            ->assertJsonPath('result.0.contado', '10.00')
            ->assertJsonPath('result.0.anulado', '12.00')
            ->assertJsonPath('result.0.gastos', '5.00');

        $this->ajax('post', 'pos.caja.cierre', ['fecha' => today()->toDateString(), 'empleado' => 'CAJERO', 'entrada' => 'LOS2TURNOS']);
        $this->assertSame(0, Venta::where('cerrado', '<>', 'S')->count());
        $this->assertSame(0, Correlativo::first()->ordendia);
    }
}
