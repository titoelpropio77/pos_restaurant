<?php

namespace Tests\Feature\Admin;

use App\Models\Almacen;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioTest extends AdminTestCase
{
    public function test_crea_un_cajero_con_su_punto_de_venta_y_puede_ingresar_al_pos(): void
    {
        $this->post(route('admin.usuarios.store'), [
            'nombres' => 'Lucía Mamani',
            'usuario' => 'lucia',
            'password' => 'clave123',
            'password_confirmation' => 'clave123',
            'rol_id' => $this->rol('Cajero'),
            'estado' => 'ACTIVO',
            'sucursal_id' => Sucursal::first()->id,
            'almacen_por_defecto_id' => Almacen::first()->id,
            'puede_vender' => 1,
        ])->assertSessionHasNoErrors();

        $usuario = Usuario::where('usuario', 'LUCIA')->firstOrFail();
        $this->assertTrue(Hash::check('CLAVE123', $usuario->password));
        $this->assertTrue($usuario->puntoVenta->puede_vender);

        auth()->logout();
        $this->post('/login', ['usuario' => 'lucia', 'password' => 'clave123', 'tipoCambio' => '6.96'])->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_editar_sin_contrasena_conserva_la_anterior(): void
    {
        $cajero = Usuario::where('usuario', 'CAJERO')->first();
        $hash = $cajero->password;

        $this->put(route('admin.usuarios.update', $cajero), [
            'nombres' => 'CAJERO RENOMBRADO', 'usuario' => 'cajero', 'rol_id' => $this->rol('Cajero'), 'estado' => 'ACTIVO',
            'sucursal_id' => Sucursal::first()->id, 'puede_vender' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame($hash, $cajero->fresh()->password);
        $this->assertSame('CAJERO RENOMBRADO', $cajero->fresh()->nombres);
    }

    public function test_el_administrador_no_puede_quitarse_su_acceso(): void
    {
        $this->put(route('admin.usuarios.update', $this->admin), [
            'nombres' => 'ADMIN', 'usuario' => 'admin', 'rol_id' => $this->rol('Cajero'), 'estado' => 'ACTIVO', 'sucursal_id' => Sucursal::first()->id,
        ])->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->esAdministrador());
        $this->delete(route('admin.usuarios.destroy', $this->admin))->assertSessionHas('error');
    }

    public function test_no_puede_otorgar_un_rol_con_mas_permisos_que_el_suyo(): void
    {
        $encargado = Usuario::create(['usuario' => 'encargado', 'nombres' => 'E', 'password' => 'CLAVE', 'rol_id' => $this->rol('Encargado')]);
        Rol::delRestaurante()->where('nombre', 'Encargado')->first()->permisos()
            ->attach(Permiso::where('clave', 'panel.usuarios')->value('id'));

        $this->actingAs($encargado->fresh())->post(route('admin.usuarios.store'), [
            'nombres' => 'INTRUSO', 'usuario' => 'intruso', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'rol_id' => $this->rol('Dueño'), 'estado' => 'ACTIVO', 'sucursal_id' => Sucursal::first()->id,
        ])->assertSessionHasErrors('rol_id');
    }

    private function rol(string $nombre): int
    {
        return Rol::delRestaurante()->where('nombre', $nombre)->value('id');
    }
}
