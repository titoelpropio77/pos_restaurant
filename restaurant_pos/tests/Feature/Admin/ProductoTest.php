<?php

namespace Tests\Feature\Admin;

use App\Models\Categoria;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Http\UploadedFile;

class ProductoTest extends AdminTestCase
{
    private function datos(array $extra = []): array
    {
        $sucursal = Sucursal::first();

        return array_merge([
            'nom_prod' => 'Chicharrón',
            'categoria_id' => Categoria::first()->id,
            'pre_venta' => 45,
            'estado' => 'ACTIVO',
            'pide_opciones' => 1,
            'opciones' => "con mote\nsin llajua\n",
            'sucursales' => [$sucursal->id => [
                'habilitado' => 1, 'precio_venta' => 48, 'hora_inicio' => '11:00', 'hora_fin' => '15:00',
                'dias' => ['sab', 'dom'], 'impresoras' => ['impcoc'],
            ]],
            'receta' => [['insumo_id' => Insumo::first()->id, 'cantidad' => 0.5]],
        ], $extra);
    }

    public function test_crea_un_producto_con_su_configuracion_completa(): void
    {
        $this->post(route('admin.productos.store'), $this->datos())->assertSessionHasNoErrors()->assertRedirect();

        $producto = Producto::where('nom_prod', 'CHICHARRÓN')->firstOrFail();
        $config = $producto->sucursales()->first();

        $this->assertSame('OPCIONES', $producto->presa);
        $this->assertSame(['CON MOTE', 'SIN LLAJUA'], $producto->opciones()->pluck('opcion')->all());
        $this->assertEquals(48, $config->precio_venta);
        $this->assertSame(['x', '', 'x'], [$config->sab, $config->lun, $config->impcoc]);
        $this->assertSame('11:00', $config->hora_inicio);
        $this->assertCount(1, $producto->receta);
    }

    public function test_el_pos_cobra_el_precio_de_la_sucursal(): void
    {
        $this->post(route('admin.productos.store'), $this->datos());
        $producto = Producto::where('nom_prod', 'CHICHARRÓN')->firstOrFail();

        $this->assertEquals(48, Producto::paraPos(Sucursal::first()->id)->where('productos.id', $producto->id)->value('pre_venta'));
    }

    public function test_deshabilitar_una_sucursal_quita_el_producto_de_esa_sucursal(): void
    {
        $this->post(route('admin.productos.store'), $this->datos());
        $producto = Producto::where('nom_prod', 'CHICHARRÓN')->firstOrFail();

        $this->put(route('admin.productos.update', $producto), $this->datos([
            'sucursales' => [Sucursal::first()->id => ['habilitado' => 0]],
            'receta' => [],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(0, $producto->sucursales()->count());
        $this->assertSame(0, $producto->receta()->count());
    }

    public function test_no_permite_nombres_duplicados(): void
    {
        $this->post(route('admin.productos.store'), $this->datos(['nom_prod' => 'SILPANCHO']))->assertSessionHasErrors('nom_prod');
    }

    public function test_guarda_la_imagen_en_la_carpeta_que_lee_el_pos(): void
    {
        $this->post(route('admin.productos.store'), $this->datos(['imagen' => UploadedFile::fake()->image('plato.png')]));

        $producto = Producto::where('nom_prod', 'CHICHARRÓN')->firstOrFail();
        $this->assertStringStartsWith('abms1/imagenes/imagenboton/', $producto->imgnormal);
        $this->assertFileExists(public_path($producto->imgnormal));
        @unlink(public_path($producto->imgnormal));
    }

    public function test_desactivar_no_borra_el_producto(): void
    {
        $producto = Producto::first();

        $this->delete(route('admin.productos.destroy', $producto));

        $this->assertSame('INACTIVO', $producto->fresh()->estado);
    }
}
