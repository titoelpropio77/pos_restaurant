<?php

namespace Tests\Feature\Admin;

use App\Exceptions\PosException;
use App\Models\User;
use Database\Seeders\BaseDataSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Cualquier error inesperado (500) hace fallar la prueba en lugar de quedar oculto en la respuesta
        $this->withoutExceptionHandling([ValidationException::class, HttpException::class, ModelNotFoundException::class, PosException::class]);
        $this->seed([BaseDataSeeder::class, DemoSeeder::class]);
        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($this->admin);
    }
}
