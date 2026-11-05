<?php

namespace Tests\Feature\Admin;

use App\Admin\Reportes\Filtros;
use App\Admin\Reportes\Registro;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Services\Ventas\RegistrarVenta;
use App\Support\PosContexto;

class FacturacionYReportesTest extends AdminTestCase
{
    private function vender(string $nit = ''): Venta
    {
        $pos = app(PosContexto::class);
        $pos->iniciar($this->admin->puntoVenta()->with('sucursal')->first(), 6.96);
        $producto = Producto::where('nom_prod', 'SILPANCHO')->first();

        return app(RegistrarVenta::class)->ejecutar([
            'idProducto' => [$producto->id], 'cantidad' => [2], 'subTotal' => [70], 'nombreProduc' => ['SILPANCHO'],
            'nombreCli' => $nit ? 'JUAN' : 'SIN NOMBRE', 'nitCli' => $nit, 'totalBs' => 70,
            'formaPago' => 'CONTADO', 'turno' => 'NOCHE', 'fechaVenta' => today()->toDateString(),
        ], $this->admin)['venta'];
    }

    public function test_actualiza_la_dosificacion_y_conserva_el_historial(): void
    {
        $sucursal = Sucursal::first();
        $datos = [
            'nomemp' => 'EL PORTAL SRL', 'nitruc' => '1020304', 'nroautori' => '79040011859',
            'llave' => 'A3Fs4s$)2cvD(eY667A5C4A2rsdf53kw9654E2B23s24df35F5', 'fecha_lim' => today()->addMonths(6)->toDateString(),
            'hinitm' => '07:00', 'hfintm' => '15:00', 'folio' => 500, 'folior' => 500, 'nueva' => 1,
        ];

        $this->put(route('admin.facturacion.update', $sucursal), $datos)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(2, Dosificacion::where('sucursal_id', $sucursal->id)->count());
        $this->assertSame('79040011859', Dosificacion::vigenteDe($sucursal->id)->nroautori);
        $this->assertSame(500, Correlativo::deVentas($sucursal->id)->folio);

        // La siguiente factura continúa la numeración configurada
        $this->assertSame(501, $this->vender('4189179011')->fortax);
    }

    public function test_rechaza_una_llave_con_caracteres_invalidos(): void
    {
        $this->put(route('admin.facturacion.update', Sucursal::first()), [
            'nomemp' => 'X', 'nitruc' => '1', 'nroautori' => '1', 'llave' => 'llave con espacios', 'fecha_lim' => '2030-01-01',
            'hinitm' => '07:00', 'hfintm' => '15:00', 'folio' => 0, 'folior' => 0,
        ])->assertSessionHasErrors('llave');
    }

    public function test_todos_los_reportes_se_generan_y_exportan(): void
    {
        $this->vender();
        $this->vender('4189179011');

        foreach (array_keys(Registro::REPORTES) as $slug) {
            $this->get(route('admin.reportes.show', $slug))->assertOk();
            $this->get(route('admin.reportes.show', [$slug, 'exportar' => 'csv']))->assertOk();
        }
    }

    public function test_el_libro_de_ventas_incluye_solo_facturas(): void
    {
        $this->vender();
        $factura = $this->vender('4189179011');

        $filas = collect(Registro::obtener('libro-ventas')->filas(Filtros::desde(request())));

        $this->assertCount(1, $filas);
        $this->assertSame($factura->fortax, $filas->first()['factura']);
        $this->assertEquals(9.1, $filas->first()['debito']);
    }

    public function test_el_arqueo_del_panel_coincide_con_el_cierre_del_pos(): void
    {
        $this->vender();
        $this->vender('4189179011');

        $fila = collect(Registro::obtener('caja')->filas(Filtros::desde(request())))->first();

        $this->assertEquals(140, $fila['facturado']);
        $this->assertEquals(140, $fila['saldo']);
    }

    public function test_anular_una_venta_desde_el_panel(): void
    {
        $venta = $this->vender();

        $this->post(route('admin.ventas.anular', $venta));

        $this->assertSame('INACTIVO', $venta->fresh()->estado);
        $this->assertSame('ADMIN', $venta->fresh()->eliminpor);
    }
}
