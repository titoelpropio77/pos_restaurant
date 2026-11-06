<?php

namespace Tests\Feature\Pos;

use App\Models\User;
use Database\Seeders\BaseDataSeeder;
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

        $this->seed([BaseDataSeeder::class, DemoSeeder::class]);
    }

    protected function log_in(string $email = 'cajero@mesatek.local', string $password = 'cajero'): static
    {
        $this->post('/login', ['email' => $email, 'password' => $password, 'exchangeRate' => '6.96'])
            ->assertRedirect(route('pos.index'));

        $this->assertAuthenticatedAs(User::where('email', mb_strtolower($email))->first());

        return $this;
    }

    protected function ajax(string $method, string $route, array $data = []): TestResponse
    {
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        return $method === 'get'
            ? $this->getJson(route($route, $data), $headers)
            : $this->postJson(route($route), $data, $headers);
    }
}
