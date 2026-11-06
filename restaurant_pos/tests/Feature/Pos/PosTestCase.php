<?php

namespace Tests\Feature\Pos;

use App\Models\Usuario;
use Database\Seeders\DatosBaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Base para las pruebas del POS: siembra datos de ejemplo e ingresa como cajero.
 */
abstract class PosTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([DatosBaseSeeder::class, DemoSeeder::class]);
    }

    protected function ingresar(string $usuario = 'cajero', string $password = 'cajero'): static
    {
        $this->post('/login', ['usuario' => $usuario, 'password' => $password, 'tipoCambio' => '6.96'])
            ->assertRedirect(route('pos.index'));

        $this->assertAuthenticatedAs(Usuario::where('usuario', mb_strtoupper($usuario))->first());

        return $this;
    }

    protected function ajax(string $metodo, string $ruta, array $datos = []): TestResponse
    {
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        return $metodo === 'get'
            ? $this->getJson(route($ruta, $datos), $headers)
            : $this->postJson(route($ruta), $datos, $headers);
    }
}
