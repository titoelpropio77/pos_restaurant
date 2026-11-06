<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\Rol;
use Database\Seeders\DatabaseSeeder;

class SeguridadTest extends AdminTestCase
{
    public function test_sin_sesion_redirige_al_login_propio(): void
    {
        auth()->logout();

        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Administración del sistema');
    }

    public function test_ingresa_con_correo_y_contrasena(): void
    {
        auth()->logout();

        $this->post('/login', ['correo' => 'SUPER@mesatek.test', 'password' => 'secreto123'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->super);
        $this->assertNotNull($this->super->fresh()->ultimo_ingreso);
    }

    public function test_rechaza_contrasena_incorrecta_y_cuenta_inactiva(): void
    {
        auth()->logout();
        $this->post('/login', ['correo' => 'super@mesatek.test', 'password' => 'otra'])->assertSessionHas('error');
        $this->assertGuest();

        $this->super->update(['estado' => 'INACTIVO']);
        $this->post('/login', ['correo' => 'super@mesatek.test', 'password' => 'secreto123'])->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_una_cuenta_dada_de_baja_pierde_la_sesion_abierta(): void
    {
        $soporte = $this->cuenta('Soporte', 'soporte@mesatek.test');
        $this->actingAs($soporte)->get('/')->assertOk();

        $soporte->update(['estado' => 'INACTIVO']);

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_todas_las_pantallas_responden(): void
    {
        foreach ([
            'dashboard', 'perfil.edit', 'restaurantes.index', 'restaurantes.create', 'sucursales.index', 'sucursales.create',
            'usuarios.index', 'usuarios.create', 'administradores.index', 'administradores.create',
            'roles.index', 'roles.create', 'permisos.index', 'permisos.create', 'auditoria.index',
        ] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }

        foreach (['paises', 'ciudades', 'monedas', 'bancos'] as $catalogo) {
            $this->get(route('catalogos.index', $catalogo))->assertOk();
            $this->get(route('catalogos.create', $catalogo))->assertOk();
        }
    }

    public function test_el_rol_limita_lo_que_ve_y_hace_la_cuenta(): void
    {
        $this->actingAs($this->cuenta('Soporte', 'soporte@mesatek.test'));

        $this->get(route('restaurantes.index'))->assertOk()->assertDontSee('Nuevo restaurante');
        $this->get(route('usuarios.index'))->assertOk();
        $this->get(route('restaurantes.create'))->assertForbidden();
        $this->post(route('restaurantes.store'), $this->datosRestaurante())->assertForbidden();
        $this->get(route('administradores.index'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('permisos.index'))->assertForbidden();
        $this->get(route('auditoria.index'))->assertForbidden();
        $this->get(route('catalogos.index', 'paises'))->assertForbidden();
        $this->get('/')->assertDontSee(route('administradores.index'));
    }

    public function test_no_se_puede_quedar_sin_superadministrador(): void
    {
        $this->delete(route('administradores.destroy', $this->super))->assertSessionHas('error');

        $this->put(route('administradores.update', $this->super), [
            'nombre' => 'X', 'correo' => 'super@mesatek.test', 'estado' => 'ACTIVO',
            'rol_id' => Rol::delAmbito(Rol::SISTEMA)->where('nombre', 'Soporte')->value('id'),
        ])->assertSessionHas('error');

        $this->assertTrue($this->super->fresh()->esSuperadministrador());
    }

    public function test_crea_una_cuenta_que_puede_ingresar(): void
    {
        $this->post(route('administradores.store'), [
            'nombre' => 'Nueva', 'correo' => 'NUEVA@mesatek.test', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'rol_id' => Rol::delAmbito(Rol::SISTEMA)->where('nombre', 'Soporte')->value('id'), 'estado' => 'ACTIVO',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->post('/login', ['correo' => 'nueva@mesatek.test', 'password' => 'secreto123'])->assertRedirect(route('dashboard'));
    }

    public function test_un_rol_nuevo_solo_acepta_permisos_de_su_ambito(): void
    {
        $delSistema = Permiso::where('ambito', Rol::SISTEMA)->value('id');
        $delRestaurante = Permiso::where('ambito', Rol::RESTAURANTE)->value('id');

        $this->post(route('roles.store'), ['nombre' => 'Mesero', 'ambito' => Rol::RESTAURANTE, 'permisos' => [$delSistema]])
            ->assertSessionHasErrors('permisos.0');

        $this->post(route('roles.store'), ['nombre' => 'Mesero', 'ambito' => Rol::RESTAURANTE, 'permisos' => [$delRestaurante]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Rol::where('nombre', 'Mesero')->first()->permisos()->count());
    }

    public function test_los_roles_protegidos_no_se_eliminan_ni_pierden_lo_esencial(): void
    {
        $super = Rol::where('nombre', 'Superadministrador')->first();
        $total = $super->permisos()->count();

        $this->delete(route('roles.destroy', $super))->assertSessionHas('error');
        $this->put(route('roles.update', $super), ['descripcion' => 'x', 'permisos' => []])->assertSessionHasNoErrors();

        $this->assertSame($total, $super->fresh()->permisos()->count());
    }

    public function test_un_rol_con_cuentas_no_se_elimina(): void
    {
        $rol = Rol::create(['nombre' => 'Temporal', 'ambito' => Rol::SISTEMA]);
        Administrador::create(['nombre' => 'T', 'correo' => 't@mesatek.test', 'password' => 'secreto123', 'rol_id' => $rol->id]);

        $this->delete(route('roles.destroy', $rol))->assertSessionHas('error');

        $this->assertNotNull($rol->fresh());
    }

    public function test_un_permiso_nuevo_lo_recibe_el_rol_total_de_su_ambito(): void
    {
        $this->post(route('permisos.store'), ['clave' => 'panel.descuentos', 'nombre' => 'Descuentos', 'modulo' => 'Panel', 'ambito' => Rol::RESTAURANTE])
            ->assertSessionHasNoErrors();

        $permiso = Permiso::where('clave', 'panel.descuentos')->firstOrFail();

        $this->assertTrue($permiso->roles()->where('nombre', 'Dueño')->exists());
        $this->assertFalse($permiso->roles()->where('nombre', 'Cajero')->exists());
    }

    public function test_los_permisos_protegidos_conservan_clave_y_no_se_eliminan(): void
    {
        $permiso = Permiso::where('clave', 'panel.acceder')->first();

        $this->delete(route('permisos.destroy', $permiso))->assertSessionHas('error');
        $this->put(route('permisos.update', $permiso), ['clave' => 'otra.cosa', 'nombre' => 'Renombrado', 'modulo' => 'Panel']);

        $this->assertSame('panel.acceder', $permiso->fresh()->clave);
        $this->assertSame('Renombrado', $permiso->fresh()->nombre);
    }

    public function test_crud_de_catalogo_y_bloqueo_si_esta_en_uso(): void
    {
        $this->post(route('catalogos.store', 'paises'), ['nombre' => 'PERÚ', 'estado' => 'ACTIVO'])->assertSessionHasNoErrors();
        $pais = Pais::where('nombre', 'PERÚ')->firstOrFail();

        $this->post(route('catalogos.store', 'ciudades'), ['nombre' => 'LIMA', 'pais_id' => $pais->id])->assertSessionHasNoErrors();

        $this->delete(route('catalogos.destroy', ['paises', $pais->id]));

        $this->assertDatabaseHas('ciudades', ['nombre' => 'LIMA']);
    }

    public function test_cambia_su_contrasena_con_la_actual(): void
    {
        $this->put(route('perfil.update'), [
            'nombre' => 'S', 'correo' => 'super@mesatek.test', 'password_actual' => 'equivocada', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
        ])->assertSessionHasErrors('password_actual');

        $this->put(route('perfil.update'), [
            'nombre' => 'S', 'correo' => 'super@mesatek.test', 'password_actual' => 'secreto123', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
        ])->assertSessionHasNoErrors();
    }

    public function test_el_seeder_crea_la_primera_cuenta_una_sola_vez(): void
    {
        Administrador::query()->delete();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Administrador::count());
        $this->assertTrue(Administrador::first()->esSuperadministrador());
    }
}
