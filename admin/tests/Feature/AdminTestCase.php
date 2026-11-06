<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected Admin $super;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seedRestaurantRoles();
        $this->super = $this->account(Role::SUPER_ADMIN, 'super@mesatek.test');
        $this->actingAs($this->super);
    }

    /** En producción los siembra restaurant_pos, cuyo seeder no está en esta app. */
    private function seedRestaurantRoles(): void
    {
        $access = Permission::create(['name' => 'panel.access', 'guard_name' => 'web', 'label' => 'Entrar al panel', 'module' => 'Panel', 'is_protected' => true]);

        Role::create(['name' => Role::PARTNER, 'guard_name' => 'web', 'label' => 'Partner', 'is_protected' => true])->givePermissionTo($access);
        Role::create(['name' => 'cashier', 'guard_name' => 'web', 'label' => 'Cajero', 'is_protected' => true]);
    }

    /** Rol limitado para probar que los permisos se respetan. */
    protected function supportRole(): Role
    {
        return Role::firstOrCreate(
            ['name' => 'support', 'guard_name' => 'admin'],
            ['label' => 'Soporte'],
        )->syncPermissions(['dashboard.view', 'restaurants.view', 'users.view', 'users.create', 'users.update']);
    }

    protected function account(string $role, string $email): Admin
    {
        return Admin::create([
            'name' => $role, 'email' => $email, 'password' => 'secreto123', 'status' => 'ACTIVE',
        ])->assignRole($role);
    }

    protected function dataRestaurant(array $changes = []): array
    {
        return $changes + [
            'name' => 'La Paceña', 'status' => 'ACTIVE', 'main_name' => 'Casa Matriz',
            'owner_names' => 'Ana Pérez', 'owner_user' => 'ana', 'owner_email' => 'Ana@Mesatek.local', 'owner_password' => 'clave1', 'owner_password_confirmation' => 'clave1',
        ];
    }
}
