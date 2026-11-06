<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del guard web y roles de los restaurantes: partner (dueño) y cashier (cajero).
 *
 * Corre en cada arranque del contenedor: agrega lo que falte sin pisar lo editado desde la app
 * admin. Partner recibe siempre todos; cashier recibe los suyos al crearse y, después, sólo los
 * permisos del POS que aparezcan nuevos, para no devolverle lo que le hayan quitado.
 * Los permisos del guard admin los siembra la app admin.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    /** Sólo el partner: cambiar el tipo de cambio exige autorización de un dueño. */
    private const NOT_FOR_CASHIER = ['pos.exchange_rate.change'];

    private const PERMISSIONS = [
        'Punto de venta' => [
            'pos.access' => 'Entrar al punto de venta',
            'pos.exchange_rate.change' => 'Autorizar el cambio del tipo de cambio',
            'pos.branches.change' => 'Cambiar de sucursal',
            'pos.sales.create' => 'Registrar ventas',
            'pos.sales.view' => 'Ver el histórico de ventas',
            'pos.sales.print' => 'Reimprimir ventas',
            'pos.sales.void' => 'Anular ventas',
            'pos.cash.close' => 'Hacer el cierre de caja',
            'pos.expenses.view' => 'Ver ingresos y egresos',
            'pos.expenses.create' => 'Registrar ingresos y egresos',
            'pos.tables.view' => 'Ver mesas',
            'pos.tables.update' => 'Abrir mesas y agregar pedidos',
            'pos.tables.charge' => 'Cobrar mesas',
            'pos.tables.transfer' => 'Traspasar mesas',
            'pos.tables.void' => 'Anular mesas e ítems',
            'pos.customers.view' => 'Buscar clientes',
            'pos.customers.create' => 'Registrar clientes',
            'pos.customers.update' => 'Editar clientes',
            'pos.customers.delete' => 'Eliminar clientes',
            'pos.products.deactivate' => 'Desactivar productos desde el POS',
            'pos.products.move' => 'Mover productos de categoría desde el POS',
            'pos.cards.view' => 'Consultar tarjetas prepago',
            'pos.cards.consume' => 'Consumir tarjetas prepago',
        ],
        'Panel' => [
            'panel.access' => 'Entrar al panel del restaurante',
        ],
        'Reportes' => [
            'reports.view' => 'Ver reportes',
            'reports.export' => 'Exportar reportes',
        ],
        'Productos' => [
            'products.view' => 'Ver productos',
            'products.create' => 'Crear productos',
            'products.update' => 'Editar productos',
            'products.delete' => 'Dar de baja productos',
        ],
        'Usuarios' => [
            'users.view' => 'Ver usuarios',
            'users.create' => 'Crear usuarios',
            'users.update' => 'Editar usuarios',
            'users.delete' => 'Dar de baja usuarios',
        ],
        'Inventario' => [
            'inventory.view' => 'Ver stock, movimientos y kardex',
            'inventory.create' => 'Registrar movimientos de inventario',
        ],
        'Ventas' => [
            'sales.view' => 'Ver el detalle de ventas',
            'sales.void' => 'Anular ventas desde el panel',
        ],
        'Facturación' => [
            'billing.view' => 'Ver la facturación',
            'billing.update' => 'Editar la dosificación',
        ],
    ];

    /** Cada catálogo de App\Admin\Catalogs tiene ver, crear, editar y eliminar. */
    private const CATALOGS = [
        'categories' => 'Categorías',
        'report_groups' => 'Grupos de reporte',
        'customers' => 'Clientes',
        'dining_tables' => 'Mesas',
        'staff' => 'Personal',
        'supplies' => 'Insumos',
        'warehouses' => 'Almacenes',
        'suppliers' => 'Proveedores',
        'units' => 'Unidades de medida',
        'reasons' => 'Motivos de movimiento',
        'branches' => 'Sucursales',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $new = [];

        foreach ($this->permissions() as $name => [$label, $module]) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                ['label' => $label, 'module' => $module, 'is_protected' => true],
            );

            if ($permission->wasRecentlyCreated) {
                $new[] = $name;
            }
        }

        $all = array_keys($this->permissions());

        $this->role('partner', 'Partner', 'Dueño del restaurante: administra su panel y sus cajeros')
            ->givePermissionTo($all);

        $cashier = $this->role('cashier', 'Cajero', 'Vende en el punto de venta');

        $forCashier = array_values(array_diff(
            array_filter($all, fn ($name) => str_starts_with($name, 'pos.')),
            self::NOT_FOR_CASHIER,
        ));

        if ($cashier->wasRecentlyCreated) {
            $cashier->syncPermissions($forCashier);
        } else {
            $cashier->givePermissionTo(array_intersect($forCashier, $new));
        }

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

    private function role(string $name, string $label, string $description): Role
    {
        return Role::firstOrCreate(
            ['name' => $name, 'guard_name' => self::GUARD],
            ['label' => $label, 'description' => $description, 'is_protected' => true],
        );
    }
}
