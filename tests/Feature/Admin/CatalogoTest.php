<?php

namespace Tests\Feature\Admin;

use App\Models\Categoria;
use App\Models\Correlativo;
use App\Models\Mesa;
use App\Models\Sucursal;

class CatalogoTest extends AdminTestCase
{
    public function test_crea_y_edita_una_categoria(): void
    {
        $this->post(route('admin.catalogos.store', 'categorias'), ['nom_grupo' => 'SOPAS', 'colores' => '#ff0000', 'orden' => 5, 'estado' => 'ACTIVO'])
            ->assertRedirect(route('admin.catalogos.index', 'categorias'));

        $categoria = Categoria::where('nom_grupo', 'SOPAS')->firstOrFail();
        $this->assertSame((string) $categoria->id, $categoria->cod_grupo);

        $this->put(route('admin.catalogos.update', ['categorias', $categoria->id]), ['nom_grupo' => 'SOPAS Y CALDOS', 'orden' => 1, 'estado' => 'ACTIVO']);
        $this->assertSame('SOPAS Y CALDOS', $categoria->fresh()->nom_grupo);
    }

    public function test_valida_los_campos(): void
    {
        $this->post(route('admin.catalogos.store', 'mesas'), ['nro_mesa' => '', 'estado' => 'ACTIVO'])
            ->assertSessionHasErrors(['nro_mesa', 'sucursal_id']);
    }

    public function test_dar_de_baja_una_mesa_la_oculta_del_pos(): void
    {
        $mesa = Mesa::first();

        $this->delete(route('admin.catalogos.destroy', ['mesas', $mesa->id]));

        $this->assertSame('ELIMINADO', $mesa->fresh()->estado);
        $this->get(route('admin.catalogos.index', 'mesas'))->assertDontSee('ELIMINADO');
    }

    public function test_una_sucursal_nueva_recibe_su_correlativo(): void
    {
        $this->post(route('admin.catalogos.store', 'sucursales'), ['nombre' => 'SUCURSAL NORTE', 'estado' => 'ACTIVO']);

        $sucursal = Sucursal::where('nombre', 'SUCURSAL NORTE')->firstOrFail();
        $this->assertNotNull(Correlativo::deVentas($sucursal->id));
    }

    public function test_catalogo_inexistente_da_404(): void
    {
        $this->get('/admin/catalogos/no-existe')->assertNotFound();
    }
}
