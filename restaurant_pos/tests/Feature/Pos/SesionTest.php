<?php

namespace Tests\Feature\Pos;

use App\Models\IngresoUsuario;
use App\Models\Usuario;

class SesionTest extends PosTestCase
{
    public function test_muestra_el_modal_de_ingreso_sin_sesion(): void
    {
        $this->get('/')->assertOk()->assertSee('ModalLogin')->assertDontSee('js/pos/posgourmet.js');
    }

    public function test_el_cajero_ingresa_y_se_registra_el_tipo_de_cambio(): void
    {
        $this->ingresar();

        $this->get('/')->assertOk()->assertSee('idSucursal = ', false)->assertSee('js/pos/posgourmet.js');
        $this->assertDatabaseHas('ingresos_usuario', ['usuario' => 'CAJERO', 'cam_of' => 6.96]);
    }

    public function test_usuario_y_contrasena_no_distinguen_mayusculas(): void
    {
        $this->ingresar('CaJeRo', 'CAJERO');
    }

    public function test_rechaza_credenciales_invalidas(): void
    {
        $this->post('/login', ['usuario' => 'cajero', 'password' => 'otra', 'tipoCambio' => '6.96'])
            ->assertSessionHas('error_login');

        $this->assertGuest();
    }

    public function test_rechaza_usuario_sin_punto_de_venta(): void
    {
        Usuario::create(['usuario' => 'sinpv', 'nombres' => 'SIN PV', 'password' => 'CLAVE']);

        $this->post('/login', ['usuario' => 'sinpv', 'password' => 'clave', 'tipoCambio' => '6.96'])
            ->assertSessionHas('error_login', 'EL USUARIO NO TIENE UN PUNTO DE VENTA ASIGNADO');

        $this->assertGuest();
    }

    public function test_rechaza_ingreso_si_la_fecha_del_equipo_es_anterior_al_ultimo_ingreso(): void
    {
        IngresoUsuario::create(['usuario' => 'ADMIN', 'vfecha' => today()->addDay(), 'cam_of' => 6.96]);

        $this->post('/login', ['usuario' => 'cajero', 'password' => 'cajero', 'tipoCambio' => '6.96'])
            ->assertSessionHas('error_login', 'LA FECHA NO ES LA ACTUAL, POR FAVOR COLOQUE LA FECHA CORRECTAMENTE');
    }

    public function test_las_peticiones_ajax_sin_sesion_reciben_error_session(): void
    {
        $this->ajax('get', 'pos.categorias.index')->assertOk()->assertJson(['error' => 'Error Session']);
    }

    public function test_solo_un_administrador_autoriza_cambiar_el_tipo_de_cambio(): void
    {
        $this->postJson(route('pos.tipo-cambio.autorizar'), ['usuario' => 'admin', 'password' => 'admin'])
            ->assertJson(['result' => 'habilitado']);
        $this->postJson(route('pos.tipo-cambio.autorizar'), ['usuario' => 'cajero', 'password' => 'cajero'])
            ->assertJson(['result' => 'nohabilitado']);
    }
}
