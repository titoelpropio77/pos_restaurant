<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RestauranteTest extends AdminTestCase
{
    private function crear(array $cambios = []): Empresa
    {
        $this->post(route('restaurantes.store'), $this->datosRestaurante($cambios))->assertSessionHasNoErrors();

        return Empresa::latest('id')->firstOrFail();
    }

    public function test_crea_el_restaurante_listo_para_ingresar(): void
    {
        $empresa = $this->crear();

        $this->assertSame('LA PACEÑA', $empresa->nombre);

        $matriz = $empresa->matriz;
        $this->assertSame('CASA MATRIZ', $matriz->nombre);
        $this->assertCount(1, $matriz->almacenes);
        $this->assertDatabaseHas('correlativos', ['sucursal_id' => $matriz->id, 'tabla' => 'ventas', 'empresa_id' => $empresa->id]);
        $this->assertDatabaseHas('motivos_movimiento', ['nombre' => 'VENTA', 'empresa_id' => $empresa->id]);
        $this->assertDatabaseHas('unidades_medida', ['abreviatura' => 'KG', 'empresa_id' => $empresa->id]);

        $dueno = Usuario::where('usuario', 'ANA')->firstOrFail();
        $this->assertSame($empresa->id, $dueno->empresa_id);
        $this->assertSame('Dueño', $dueno->rol->nombre);
        $this->assertSame(Usuario::NIVEL_ADMIN, $dueno->nivel);
        $this->assertSame($matriz->id, PuntoVenta::where('usuario_id', $dueno->id)->value('sucursal_id'));
        $this->assertSame($empresa->id, PuntoVenta::where('usuario_id', $dueno->id)->value('empresa_id'));
    }

    public function test_la_contrasena_queda_como_la_compara_el_pos(): void
    {
        $this->crear(['dueno_password' => 'MiClave', 'dueno_password_confirmation' => 'MiClave']);

        // El POS convierte a mayúsculas antes de comparar
        $this->assertTrue(Hash::check('MICLAVE', Usuario::where('usuario', 'ANA')->value('password')));
    }

    public function test_la_dosificacion_nace_vencida_y_sin_autorizacion(): void
    {
        $empresa = $this->crear();

        $dosificacion = DB::table('dosificaciones')->where('empresa_id', $empresa->id)->first();

        $this->assertNull($dosificacion->nroautori);
        $this->assertNull($dosificacion->llave);
        $this->assertTrue($dosificacion->fecha_lim < today()->toDateString());
    }

    public function test_el_usuario_del_dueno_es_unico_en_todo_el_sistema(): void
    {
        $this->crear();

        $this->post(route('restaurantes.store'), $this->datosRestaurante(['nombre' => 'Otro', 'dueno_usuario' => 'ANA']))
            ->assertSessionHasErrors('dueno_usuario');

        $this->assertSame(1, Empresa::count());
    }

    public function test_si_falla_no_queda_nada_a_medias(): void
    {
        $this->post(route('restaurantes.store'), $this->datosRestaurante(['matriz_ciudad_id' => 999999]))
            ->assertSessionHasErrors('matriz_ciudad_id');

        $this->assertSame(0, Empresa::count());
        $this->assertSame(0, Sucursal::count());
    }

    public function test_dar_de_baja_y_reactivar_queda_en_auditoria(): void
    {
        $empresa = $this->crear();

        $this->post(route('restaurantes.estado', $empresa));
        $this->assertSame('INACTIVO', $empresa->fresh()->estado);

        $this->post(route('restaurantes.estado', $empresa));
        $this->assertSame('ACTIVO', $empresa->fresh()->estado);

        $this->assertSame(2, Auditoria::where('accion', 'ESTADO')->count());
        $this->get(route('auditoria.index'))->assertOk()->assertSee('LA PACEÑA');
    }

    public function test_guarda_las_opciones_del_pos_por_restaurante(): void
    {
        $empresa = $this->crear();

        $this->put(route('restaurantes.update', $empresa), [
            'nombre' => 'La Paceña', 'estado' => 'ACTIVO', 'opciones' => ['billar' => 1],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($empresa->fresh()->opciones['billar']);
        $this->assertFalse($empresa->fresh()->opciones['salon']);
    }

    public function test_una_sucursal_nueva_no_es_matriz_y_trae_su_almacen(): void
    {
        $empresa = $this->crear();

        $this->post(route('sucursales.store'), ['empresa_id' => $empresa->id, 'nombre' => 'Sopocachi'])->assertSessionHasNoErrors();

        $sucursal = Sucursal::where('nombre', 'SOPOCACHI')->firstOrFail();
        $this->assertFalse($sucursal->es_matriz);
        $this->assertCount(1, $sucursal->almacenes);
    }

    public function test_cambia_la_casa_matriz_y_siempre_hay_una_sola(): void
    {
        $empresa = $this->crear();
        $anterior = $empresa->matriz;
        $this->post(route('sucursales.store'), ['empresa_id' => $empresa->id, 'nombre' => 'Sopocachi']);
        $nueva = Sucursal::where('nombre', 'SOPOCACHI')->firstOrFail();

        $this->post(route('sucursales.matriz', $nueva))->assertSessionHas('ok');

        $this->assertTrue($nueva->fresh()->es_matriz);
        $this->assertFalse($anterior->fresh()->es_matriz);
        $this->assertSame(1, Sucursal::where('empresa_id', $empresa->id)->where('es_matriz', true)->count());
    }

    public function test_la_casa_matriz_no_se_da_de_baja(): void
    {
        $matriz = $this->crear()->matriz;

        $this->post(route('sucursales.estado', $matriz))->assertSessionHas('error');

        $this->assertSame('ACTIVO', $matriz->fresh()->estado);
    }

    public function test_crea_un_usuario_en_una_sucursal_de_su_restaurante(): void
    {
        $empresa = $this->crear();

        $this->post(route('usuarios.store'), [
            'empresa_id' => $empresa->id, 'nombres' => 'Luis', 'usuario' => 'luis', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'rol_id' => Rol::delAmbito(Rol::RESTAURANTE)->where('nombre', 'Cajero')->value('id'),
            'estado' => 'ACTIVO', 'sucursal_id' => $empresa->matriz->id, 'puede_vender' => 1,
        ])->assertSessionHasNoErrors();

        $usuario = Usuario::where('usuario', 'LUIS')->firstOrFail();
        $this->assertSame(Usuario::NIVEL_CAJERO, $usuario->nivel);
        $this->assertTrue($usuario->puntoVenta->puede_vender);
    }

    public function test_no_asigna_un_usuario_a_una_sucursal_de_otro_restaurante(): void
    {
        $uno = $this->crear();
        $dos = $this->crear(['nombre' => 'Dos', 'dueno_usuario' => 'dos']);

        $this->post(route('usuarios.store'), [
            'empresa_id' => $dos->id, 'nombres' => 'Luis', 'usuario' => 'luis', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'rol_id' => Rol::delAmbito(Rol::RESTAURANTE)->value('id'), 'estado' => 'ACTIVO', 'sucursal_id' => $uno->matriz->id,
        ])->assertSessionHasErrors('sucursal_id');
    }

    public function test_no_asigna_un_rol_del_sistema_a_un_usuario_de_restaurante(): void
    {
        $empresa = $this->crear();

        $this->post(route('usuarios.store'), [
            'empresa_id' => $empresa->id, 'nombres' => 'Luis', 'usuario' => 'luis', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'rol_id' => Rol::delAmbito(Rol::SISTEMA)->value('id'), 'estado' => 'ACTIVO', 'sucursal_id' => $empresa->matriz->id,
        ])->assertSessionHasErrors('rol_id');
    }

    public function test_editar_sin_contrasena_conserva_la_anterior(): void
    {
        $empresa = $this->crear();
        $dueno = Usuario::where('usuario', 'ANA')->firstOrFail();
        $hash = $dueno->password;

        $this->put(route('usuarios.update', $dueno), [
            'nombres' => 'Ana', 'usuario' => 'ana', 'rol_id' => $dueno->rol_id, 'estado' => 'ACTIVO', 'sucursal_id' => $empresa->matriz->id, 'puede_vender' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame($hash, $dueno->fresh()->password);
    }
}
