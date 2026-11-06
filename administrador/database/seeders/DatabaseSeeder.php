<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Crea la primera cuenta del sistema. No hace nada si ya existe alguna.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Administrador::exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: (app()->isProduction() ? null : 'admin1234');

        if (! $password) {
            $this->command?->warn('Defina ADMIN_PASSWORD para crear la primera cuenta del sistema.');

            return;
        }

        Administrador::create([
            'nombre' => 'Superadministrador',
            'correo' => env('ADMIN_CORREO', 'admin@mesatek.local'),
            'password' => $password,
            'rol_id' => Rol::delAmbito(Rol::SISTEMA)->where('nombre', 'Superadministrador')->value('id'),
        ]);
    }
}
