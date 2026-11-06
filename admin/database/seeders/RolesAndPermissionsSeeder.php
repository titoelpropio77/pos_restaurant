<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del guard admin y rol superadmin, el dueño del sistema.
 *
 * Corre en cada arranque del contenedor: agrega lo que falte sin pisar lo editado.
 * Superadmin además pasa todas las comprobaciones por Gate::before (AppServiceProvider).
 * Los permisos del guard web y los roles partner y cashier los siembra restaurant_pos.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'admin';

    private const PERMISSIONS = [
        'General' => [
            'dashboard.view' => 'Ver el inicio',
        ],
        'Restaurantes' => [
            'restaurants.view' => 'Ver restaurantes',
            'restaurants.create' => 'Crear restaurantes',
            'restaurants.update' => 'Editar restaurantes',
            'restaurants.deactivate' => 'Dar de baja restaurantes',
        ],
        'Sucursales' => [
            'branches.view' => 'Ver sucursales',
            'branches.create' => 'Crear sucursales',
            'branches.update' => 'Editar sucursales',
            'branches.set_main' => 'Definir la casa matriz',
            'branches.deactivate' => 'Dar de baja sucursales',
        ],
        'Usuarios' => [
            'users.view' => 'Ver usuarios de restaurantes',
            'users.create' => 'Crear usuarios de restaurantes',
            'users.update' => 'Editar usuarios de restaurantes',
            'users.deactivate' => 'Dar de baja usuarios de restaurantes',
        ],
        'Seguridad' => [
            'admins.view' => 'Ver cuentas del sistema',
            'admins.create' => 'Crear cuentas del sistema',
            'admins.update' => 'Editar cuentas del sistema',
            'admins.deactivate' => 'Dar de baja cuentas del sistema',
            'roles.view' => 'Ver roles',
            'roles.create' => 'Crear roles',
            'roles.update' => 'Editar roles',
            'roles.delete' => 'Eliminar roles',
            'permissions.view' => 'Ver permisos',
            'permissions.create' => 'Crear permisos',
            'permissions.update' => 'Editar permisos',
            'permissions.delete' => 'Eliminar permisos',
            'audit.view' => 'Ver la bitácora de auditoría',
        ],
    ];

    /** Cada catálogo de App\Admin\Catalogs tiene ver, crear, editar y eliminar. */
    private const CATALOGS = ['countries' => 'Países', 'cities' => 'Ciudades'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();

        foreach ($permissions as $name => [$label, $module]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                ['label' => $label, 'module' => $module, 'is_protected' => true],
            );
        }

        Role::firstOrCreate(
            ['name' => Role::SUPER_ADMIN, 'guard_name' => self::GUARD],
            ['label' => 'Superadministrador', 'description' => 'Dueño del sistema: acceso total', 'is_protected' => true],
        )->givePermissionTo(array_keys($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<string, array{0: string, 1: string}> nombre => [etiqueta, módulo] */
    private function permissions(): array
    {
        $permissions = [];

        foreach (self::PERMISSIONS as $module => $list) {
            foreach ($list as $name => $label) {
                $permissions[$name] = [$label, $module];
            }
        }

        foreach (self::CATALOGS as $slug => $title) {
            foreach (['view' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar'] as $action => $verb) {
                $permissions["catalogs.{$slug}.{$action}"] = ["{$verb}: {$title}", 'Catálogos'];
            }
        }

        return $permissions;
    }
}
