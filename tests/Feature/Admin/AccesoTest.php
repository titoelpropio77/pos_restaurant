<?php

namespace Tests\Feature\Admin;

use App\Models\Usuario;

class AccesoTest extends AdminTestCase
{
    public function test_el_administrador_ve_el_panel(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Productos más vendidos');
    }

    public function test_un_cajero_no_puede_entrar(): void
    {
        $this->actingAs(Usuario::where('usuario', 'CAJERO')->first())
            ->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_sin_sesion_redirige_al_ingreso(): void
    {
        auth()->logout();

        $this->get(route('admin.productos.index'))->assertRedirect(route('pos.index'));
    }

    public function test_el_pos_muestra_el_acceso_al_panel_solo_al_administrador(): void
    {
        $this->post('/login', ['usuario' => 'admin', 'password' => 'admin', 'tipoCambio' => '6.96']);
        $this->get('/')->assertSee(route('admin.dashboard'), false);

        $this->post('/logout');
        $this->post('/login', ['usuario' => 'cajero', 'password' => 'cajero', 'tipoCambio' => '6.96']);
        $this->get('/')->assertDontSee(route('admin.dashboard'), false);
    }
}
