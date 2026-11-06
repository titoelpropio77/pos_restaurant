<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

class UserTest extends AdminTestCase
{
    public function test_creates_a_cashier_with_its_point_of_sale_and_can_log_in_to_pos(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Lucía Mamani',
            'username' => 'lucia',
            'email' => 'Lucia@Mesatek.local',
            'password' => 'clave123',
            'password_confirmation' => 'clave123',
            'role_id' => $this->role('cashier'),
            'status' => 'ACTIVE',
            'branch_id' => Branch::first()->id,
            'default_warehouse_id' => Warehouse::first()->id,
            'can_sell' => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'lucia')->firstOrFail();
        $this->assertTrue(Hash::check('clave123', $user->password));
        $this->assertTrue($user->pointOfSale->can_sell);

        auth()->logout();
        $this->post('/login', ['email' => 'lucia@mesatek.local', 'password' => 'clave123', 'exchangeRate' => '6.96'])->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_edit_without_password_keeps_the_previous(): void
    {
        $cashier = User::where('username', 'cajero')->first();
        $hash = $cashier->password;

        $this->put(route('admin.users.update', $cashier), [
            'name' => 'CAJERO RENOMBRADO', 'username' => 'cajero', 'email' => 'cajero@mesatek.local', 'role_id' => $this->role('cashier'), 'status' => 'ACTIVE',
            'branch_id' => Branch::first()->id, 'can_sell' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame($hash, $cashier->fresh()->password);
        $this->assertSame('CAJERO RENOMBRADO', $cashier->fresh()->name);
    }

    public function test_the_admin_not_can_remove_self_its_access(): void
    {
        $this->put(route('admin.users.update', $this->admin), [
            'name' => 'ADMIN', 'username' => 'admin', 'email' => 'admin@mesatek.local', 'role_id' => $this->role('cashier'), 'status' => 'ACTIVE', 'branch_id' => Branch::first()->id,
        ])->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->canAccessPanel());
        $this->delete(route('admin.users.destroy', $this->admin))->assertSessionHas('error');
    }

    public function test_not_can_grant_a_role_with_more_permissions_that_the_own(): void
    {
        $manager = User::create(['username' => 'manager', 'email' => 'manager@mesatek.local', 'name' => 'E', 'password' => 'clave']);
        $manager->assignRole(Role::create(['name' => 'manager', 'label' => 'Encargado', 'guard_name' => 'web'])->givePermissionTo(['panel.access', 'users.view', 'users.create']));

        $this->actingAs($manager->fresh())->post(route('admin.users.store'), [
            'name' => 'INTRUSO', 'username' => 'intruder', 'email' => 'intruder@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'role_id' => $this->role('partner'), 'status' => 'ACTIVE', 'branch_id' => Branch::first()->id,
        ])->assertSessionHasErrors('role_id');
    }

    public function test_a_partner_creates_cashiers_but_not_other_partners(): void
    {
        $data = ['name' => 'NUEVO', 'username' => 'nuevo', 'email' => 'nuevo@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'status' => 'ACTIVE', 'branch_id' => Branch::first()->id];

        $this->post(route('admin.users.store'), $data + ['role_id' => $this->role('partner')])->assertSessionHasErrors('role_id');
        $this->post(route('admin.users.store'), $data + ['role_id' => $this->role('cashier')])->assertSessionHasNoErrors();

        $this->assertTrue(User::where('username', 'nuevo')->first()->hasRole('cashier'));
    }

    private function role(string $name): int
    {
        return Role::forRestaurants()->where('name', $name)->value('id');
    }
}
