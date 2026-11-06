<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Permisos del sistema y primera cuenta superadmin, que sólo se crea si no existe ninguna.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (Admin::exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: (app()->isProduction() ? null : 'admin1234');

        if (! $password) {
            $this->command?->warn('Defina ADMIN_PASSWORD para crear la primera cuenta del sistema.');

            return;
        }

        Admin::create([
            'name' => 'Superadministrador',
            'email' => env('ADMIN_CORREO', 'admin@mesatek.local'),
            'password' => $password,
        ])->assignRole(Role::SUPER_ADMIN);
    }
}
