<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected Administrador $super;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->super = $this->cuenta('Superadministrador', 'super@mesatek.test');
        $this->actingAs($this->super);
    }

    protected function cuenta(string $rol, string $correo): Administrador
    {
        return Administrador::create([
            'nombre' => $rol, 'correo' => $correo, 'password' => 'secreto123', 'estado' => 'ACTIVO',
            'rol_id' => Rol::delAmbito(Rol::SISTEMA)->where('nombre', $rol)->value('id'),
        ]);
    }

    protected function datosRestaurante(array $cambios = []): array
    {
        return $cambios + [
            'nombre' => 'La Paceña', 'estado' => 'ACTIVO', 'matriz_nombre' => 'Casa Matriz',
            'dueno_nombres' => 'Ana Pérez', 'dueno_usuario' => 'ana', 'dueno_password' => 'clave1', 'dueno_password_confirmation' => 'clave1',
        ];
    }
}
