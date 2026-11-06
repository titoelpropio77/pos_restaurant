<?php

namespace Tests\Feature\Admin;

use App\Admin\Catalogs;
use App\Models\Permission;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

class AccessTest extends AdminTestCase
{
    public function test_the_admin_ve_the_panel(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Productos más vendidos');
    }

    public function test_a_cashier_not_can_enter(): void
    {
        $this->actingAs(User::where('username', 'cajero')->first())
            ->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_without_session_redirects_to_login(): void
    {
        auth()->logout();

        $this->get(route('admin.products.index'))->assertRedirect(route('pos.index'));
    }

    public function test_the_pos_shows_the_access_to_panel_only_to_admin(): void
    {
        $this->post('/login', ['email' => 'admin@mesatek.local', 'password' => 'admin', 'exchangeRate' => '6.96']);
        $this->get('/')->assertSee(route('admin.dashboard'), false);

        $this->post('/logout');
        $this->post('/login', ['email' => 'cajero@mesatek.local', 'password' => 'cajero', 'exchangeRate' => '6.96']);
        $this->get('/')->assertDontSee(route('admin.dashboard'), false);
    }

    public function test_the_partner_has_all_the_restaurant_permissions(): void
    {
        $this->assertSame(Permission::where('guard_name', 'web')->count(), $this->admin->getAllPermissions()->count());
    }

    public function test_the_demo_partner_enters_the_first_restaurant(): void
    {
        auth()->logout();

        $this->post('/login', ['email' => 'partner@mesatek.local', 'password' => 'partner', 'exchangeRate' => '6.96'])
            ->assertRedirect(route('pos.index'));

        $partner = User::where('username', 'partner')->first();

        $this->assertTrue($partner->hasRole('partner'));
        $this->assertSame([Restaurant::orderBy('id')->value('id')], $partner->restaurants()->pluck('restaurants.id')->all());
        $this->get(route('admin.products.index'))->assertOk()->assertSee('PIQUE MACHO');
    }

    public function test_the_cashier_only_has_pos_permissions(): void
    {
        $permissions = User::where('username', 'cajero')->first()->getAllPermissions()->pluck('name');

        $this->assertNotEmpty($permissions);
        $this->assertTrue($permissions->every(fn ($name) => str_starts_with($name, 'pos.')));
        $this->assertNotContains('pos.exchange_rate.change', $permissions);
    }

    public function test_a_catalog_requires_its_own_permission(): void
    {
        $role = Role::create(['name' => 'limited', 'label' => 'Limitado', 'guard_name' => 'web'])
            ->givePermissionTo(['panel.access', 'catalogs.categories.view']);
        $user = User::create(['username' => 'limitado', 'email' => 'limitado@mesatek.local', 'name' => 'L', 'password' => 'clave']);
        $user->assignRole($role);

        $this->actingAs($user->fresh());

        $this->get(route('admin.catalogs.index', 'categories'))->assertOk()->assertDontSee(route('admin.catalogs.create', 'categories'));
        $this->get(route('admin.catalogs.create', 'categories'))->assertForbidden();
        $this->get(route('admin.catalogs.index', 'suppliers'))->assertForbidden();
    }

    public function test_exporting_a_report_requires_its_permission(): void
    {
        $role = Role::create(['name' => 'readonly', 'label' => 'Consulta', 'guard_name' => 'web'])
            ->givePermissionTo(['panel.access', 'reports.view']);
        $user = User::create(['username' => 'consulta', 'email' => 'consulta@mesatek.local', 'name' => 'C', 'password' => 'clave']);
        $user->assignRole($role);

        $this->actingAs($user->fresh());

        $this->get(route('admin.reports.show', 'sales'))->assertOk();
        $this->get(route('admin.reports.show', ['sales', 'export' => 'csv']))->assertForbidden();
    }

    public function test_every_route_requires_a_seeded_permission(): void
    {
        $seeded = Permission::where('guard_name', 'web')->pluck('name')->all();
        // La pantalla del POS es pública (muestra el ingreso) y el tipo de cambio se autoriza con contraseña
        $public = ['pos.index', 'pos.exchange-rate.authorize'];

        foreach (app('router')->getRoutes() as $route) {
            $name = (string) $route->getName();

            if (! str_starts_with($name, 'pos.') && ! str_starts_with($name, 'admin.') || in_array($name, $public, true)) {
                continue;
            }

            $permissions = array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'permission:'));

            $this->assertNotEmpty($permissions, "La ruta {$name} no exige ningún permiso");

            foreach ($permissions as $middleware) {
                $this->assertContains(substr($middleware, 11), $seeded);
            }

            // Cada acción (POST, PUT, DELETE) exige algo más que entrar al POS o al panel
            $specific = array_diff($permissions, ['permission:pos.access', 'permission:panel.access']);

            if (array_diff($route->methods(), ['GET', 'HEAD']) && ! str_starts_with($name, 'admin.catalogs.')) {
                $this->assertNotEmpty($specific, "La acción {$name} no exige un permiso propio");
            }
        }

        foreach (array_keys(Catalogs::all()) as $slug) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $this->assertContains("catalogs.{$slug}.{$action}", $seeded);
            }
        }
    }

    public function test_the_seeder_does_not_undo_changes_to_the_cashier_role(): void
    {
        Role::findByName('cashier', 'web')->revokePermissionTo('pos.sales.void');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse(Role::findByName('cashier', 'web')->hasPermissionTo('pos.sales.void'));
    }
}
