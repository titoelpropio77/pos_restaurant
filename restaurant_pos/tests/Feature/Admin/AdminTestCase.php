<?php

namespace Tests\Feature\Admin;

use App\Exceptions\ErrorPos;
use App\Models\Usuario;
use Database\Seeders\DatosBaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Cualquier error inesperado (500) hace fallar la prueba en lugar de quedar oculto en la respuesta
        $this->withoutExceptionHandling([ValidationException::class, HttpException::class, ModelNotFoundException::class, ErrorPos::class]);
        $this->seed([DatosBaseSeeder::class, DemoSeeder::class]);
        $this->admin = Usuario::where('usuario', 'ADMIN')->firstOrFail();
        $this->actingAs($this->admin);
    }
}
