<?php

namespace Tests\Feature;

use App\Admin\Catalogs;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class SecurityTest extends AdminTestCase
{
    public function test_without_session_redirects_to_login_own(): void
    {
        auth()->logout();

        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Administración del sistema');
    }

    public function test_logs_in_with_email_and_password(): void
    {
        auth()->logout();

        $this->post('/login', ['email' => 'SUPER@mesatek.test', 'password' => 'secreto123'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->super);
        $this->assertNotNull($this->super->fresh()->last_login_at);
    }

    public function test_rejects_password_wrong_and_account_inactive(): void
    {
        auth()->logout();
        $this->post('/login', ['email' => 'super@mesatek.test', 'password' => 'other'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->super->update(['status' => 'INACTIVE']);
        $this->post('/login', ['email' => 'super@mesatek.test', 'password' => 'secreto123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_account_given_of_deactivation_loses_the_session_open(): void
    {
        $this->supportRole();
        $support = $this->account('support', 'soporte@mesatek.test');
        $this->actingAs($support)->get('/')->assertOk();

        $support->update(['status' => 'INACTIVE']);

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_all_the_screens_respond(): void
    {
        foreach ([
            'dashboard', 'profile.edit', 'restaurants.index', 'restaurants.create', 'branches.index', 'branches.create',
            'users.index', 'users.create', 'admins.index', 'admins.create',
            'roles.index', 'roles.create', 'permissions.index', 'permissions.create', 'audit.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }

        foreach (['countries', 'cities'] as $catalog) {
            $this->get(route('catalogs.index', $catalog))->assertOk();
            $this->get(route('catalogs.create', $catalog))->assertOk();
        }
    }

    public function test_the_role_limits_the_that_ve_and_does_the_account(): void
    {
        $this->supportRole();
        $this->actingAs($this->account('support', 'soporte@mesatek.test'));

        $this->get(route('restaurants.index'))->assertOk()->assertDontSee('Nuevo restaurante');
        $this->get(route('users.index'))->assertOk();
        $this->get(route('restaurants.create'))->assertForbidden();
        $this->post(route('restaurants.store'), $this->dataRestaurant())->assertForbidden();
        $this->get(route('admins.index'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('permissions.index'))->assertForbidden();
        $this->get(route('audit.index'))->assertForbidden();
        $this->get(route('catalogs.index', 'countries'))->assertForbidden();
        $this->get('/')->assertDontSee(route('admins.index'));
    }

    public function test_not_can_remain_without_super_admin(): void
    {
        $this->delete(route('admins.destroy', $this->super))->assertSessionHas('error');

        $this->put(route('admins.update', $this->super), [
            'name' => 'X', 'email' => 'super@mesatek.test', 'status' => 'ACTIVE',
            'role_id' => $this->supportRole()->id,
        ])->assertSessionHas('error');

        $this->assertTrue($this->super->fresh()->isSuperAdmin());
    }

    public function test_creates_a_account_that_can_log_in(): void
    {
        $this->post(route('admins.store'), [
            'name' => 'Nueva', 'email' => 'NUEVA@mesatek.test', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'role_id' => $this->supportRole()->id, 'status' => 'ACTIVE',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->post('/login', ['email' => 'nueva@mesatek.test', 'password' => 'secreto123'])->assertRedirect(route('dashboard'));
    }

    public function test_a_role_new_only_accepts_permissions_of_its_scope(): void
    {
        $delSystem = Permission::where('guard_name', 'admin')->value('id');
        $forRestaurants = Permission::where('guard_name', 'web')->value('id');

        $this->post(route('roles.store'), ['label' => 'Mesero', 'guard_name' => 'web', 'permissions' => [$delSystem]])
            ->assertSessionHasErrors('permissions.0');

        $this->post(route('roles.store'), ['label' => 'Mesero', 'guard_name' => 'web', 'permissions' => [$forRestaurants]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Role::where('label', 'Mesero')->first()->permissions()->count());
    }

    public function test_the_roles_protected_not_deleted_nor_lose_the_essential(): void
    {
        $super = Role::findByName(Role::SUPER_ADMIN, 'admin');
        $total = $super->permissions()->count();

        $this->delete(route('roles.destroy', $super))->assertSessionHas('error');
        $this->put(route('roles.update', $super), ['description' => 'x', 'permissions' => []])->assertSessionHasNoErrors();

        $this->assertSame($total, $super->fresh()->permissions()->count());
    }

    public function test_a_role_with_accounts_not_deletes(): void
    {
        $role = Role::create(['name' => 'temporal', 'label' => 'Temporal', 'guard_name' => 'admin']);
        Admin::create(['name' => 'T', 'email' => 't@mesatek.test', 'password' => 'secreto123'])->assignRole($role);

        $this->delete(route('roles.destroy', $role))->assertSessionHas('error');

        $this->assertNotNull($role->fresh());
    }

    public function test_a_permission_new_the_receives_the_role_total_of_its_scope(): void
    {
        $this->post(route('permissions.store'), ['name' => 'panel.discounts', 'label' => 'Descuentos', 'module' => 'Panel', 'guard_name' => 'web'])
            ->assertSessionHasNoErrors();

        $permission = Permission::where('name', 'panel.discounts')->firstOrFail();

        $this->assertTrue($permission->roles()->where('name', 'partner')->exists());
        $this->assertFalse($permission->roles()->where('name', 'cashier')->exists());
    }

    public function test_the_permissions_protected_keep_key_and_not_deleted(): void
    {
        $permission = Permission::where('name', 'panel.access')->first();

        $this->delete(route('permissions.destroy', $permission))->assertSessionHas('error');
        $this->put(route('permissions.update', $permission), ['name' => 'other.thing', 'label' => 'Renombrado', 'module' => 'Panel']);

        $this->assertSame('panel.access', $permission->fresh()->name);
        $this->assertSame('Renombrado', $permission->fresh()->label);
    }

    public function test_crud_of_catalog_and_lock_if_this_in_usage(): void
    {
        $this->post(route('catalogs.store', 'countries'), ['name' => 'PERÚ', 'status' => 'ACTIVE'])->assertSessionHasNoErrors();
        $country = Country::where('name', 'PERÚ')->firstOrFail();

        $this->post(route('catalogs.store', 'cities'), ['name' => 'LIMA', 'country_id' => $country->id])->assertSessionHasNoErrors();

        $this->delete(route('catalogs.destroy', ['countries', $country->id]));

        $this->assertDatabaseHas('cities', ['name' => 'LIMA']);
    }

    public function test_changes_its_password_with_the_current(): void
    {
        $this->put(route('profile.update'), [
            'name' => 'S', 'email' => 'super@mesatek.test', 'password_current' => 'wrong', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
        ])->assertSessionHasErrors('password_current');

        $this->put(route('profile.update'), [
            'name' => 'S', 'email' => 'super@mesatek.test', 'password_current' => 'secreto123', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
        ])->assertSessionHasNoErrors();
    }

    public function test_the_seeder_creates_the_first_account_a_only_time(): void
    {
        Admin::query()->delete();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Admin::count());
        $this->assertTrue(Admin::first()->isSuperAdmin());
    }

    public function test_superadmin_passes_any_permission_even_a_future_one(): void
    {
        $this->assertTrue($this->super->can('restaurants.create'));
        $this->assertTrue($this->super->can('permission.that.does.not.exist'));
    }

    public function test_every_route_requires_a_seeded_permission(): void
    {
        $seeded = Permission::where('guard_name', 'admin')->pluck('name')->all();
        $public = ['login', 'login.store', 'logout', 'profile.edit', 'profile.update', 'storage.local', 'storage.local.upload'];

        foreach (app('router')->getRoutes() as $route) {
            $name = (string) $route->getName();
            $uri = $route->uri();

            if (in_array($name, $public, true) || str_starts_with($uri, 'up') || str_starts_with($name, 'catalogs.') || str_starts_with($name, 'password.')) {
                continue;
            }

            $permissions = array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'permission:'));

            $this->assertNotEmpty($permissions, "La ruta {$name} no exige ningún permiso");

            foreach ($permissions as $middleware) {
                $this->assertContains(substr($middleware, 11), $seeded);
            }
        }

        foreach (array_keys(Catalogs::all()) as $slug) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $this->assertContains("catalogs.{$slug}.{$action}", $seeded);
            }
        }
    }

    public function test_the_seeder_keeps_what_was_edited(): void
    {
        $custom = Permission::where('name', 'audit.view')->first();
        $custom->update(['label' => 'Etiqueta propia']);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame('Etiqueta propia', $custom->fresh()->label);
    }
}
