<?php

namespace Tests\Feature\Pos;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Restaurant;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

class CustomerTest extends PosTestCase
{
    private function form(array $changes = []): array
    {
        return array_merge([
            'customerName' => 'ANA ROJAS',
            'customerTaxId' => '123456',
            'customerIdNumber' => '7654321',
            'customerPhone' => '4412345',
            'customerMobile' => '70012345',
            'customerEmail' => 'ana@example.com',
            'customerAddressA' => 'AV. BOLIVAR 100',
            'birthDate' => '1990-05-20',
        ], $changes);
    }

    public function test_the_cashier_registers_a_customer_with_all_the_fields(): void
    {
        $this->log_in();

        $this->ajax('post', 'pos.customers.store', $this->form())->assertOk()->assertJsonPath('error', '');

        $this->assertDatabaseHas('customers', [
            'customer_name' => 'ANA ROJAS',
            'customer_tax_id' => '123456',
            'customer_id_number' => '7654321',
            'customer_phone' => '4412345',
            'customer_mobile' => '70012345',
            'customer_email' => 'ana@example.com',
            'customer_address' => 'AV. BOLIVAR 100',
            'birth_date' => '1990-05-20',
        ]);
    }

    public function test_the_partner_registers_a_customer(): void
    {
        $this->log_in('partner@mesatek.local', 'partner');

        $this->ajax('post', 'pos.customers.store', $this->form())->assertOk()->assertJsonPath('error', '');

        $this->assertSame(1, Customer::where('customer_name', 'ANA ROJAS')->count());
    }

    public function test_the_button_and_the_route_require_the_permission(): void
    {
        $this->log_in();
        $this->get(route('pos.index'))->assertSee('NUEVO CLIENTE');

        Role::findByName('cashier', 'web')->revokePermissionTo('pos.customers.create');

        $this->get(route('pos.index'))->assertDontSee('NUEVO CLIENTE');
        $this->ajax('post', 'pos.customers.store', $this->form())->assertOk()->assertJsonPath('error', 'NO TIENE PERMISO PARA ESTA ACCIÓN');
        $this->assertFalse(Customer::where('customer_name', 'ANA ROJAS')->exists());
    }

    public function test_it_rejects_a_repeated_customer_and_invalid_data(): void
    {
        $this->log_in();
        $this->ajax('post', 'pos.customers.store', $this->form());

        $this->ajax('post', 'pos.customers.store', $this->form(['customerName' => 'Ana Rojas']))
            ->assertJsonPath('error', 'YA EXISTE UN CLIENTE CON ESE NOMBRE Y NIT');
        $this->ajax('post', 'pos.customers.store', $this->form(['customerName' => '', 'customerTaxId' => '9']))
            ->assertJsonPath('error', fn (string $error) => $error !== '');
        $this->ajax('post', 'pos.customers.store', $this->form(['customerEmail' => 'no-es-correo', 'customerTaxId' => '9']))
            ->assertJsonPath('error', fn (string $error) => $error !== '');

        $this->assertSame(1, Customer::where('customer_name', 'ilike', 'ana rojas')->count());
    }

    public function test_a_customer_without_tax_id_is_the_same_one_the_sale_finds(): void
    {
        $this->log_in();

        $this->ajax('post', 'pos.customers.store', $this->form(['customerTaxId' => '']));

        $this->assertDatabaseHas('customers', ['customer_name' => 'ANA ROJAS', 'customer_tax_id' => '0']);
    }

    public function test_the_list_returns_every_field_and_the_update_saves_them(): void
    {
        $this->log_in();
        $id = $this->ajax('post', 'pos.customers.store', $this->form())->json('result');

        $this->ajax('get', 'pos.customers.search-name', ['name' => ''])
            ->assertJsonFragment(['customer_id_number' => '7654321', 'customer_email' => 'ana@example.com', 'birth_date' => '1990-05-20']);

        $this->ajax('post', 'pos.customers.update', $this->form(['idCustomer' => $id, 'customerEmail' => 'nuevo@example.com', 'birthDate' => '']))
            ->assertJsonPath('error', '');

        $customer = Customer::find($id);
        $this->assertSame('nuevo@example.com', $customer->customer_email);
        $this->assertNull($customer->birth_date);
    }

    public function test_the_list_does_not_show_customers_of_another_restaurant(): void
    {
        $this->log_in();
        $this->ajax('post', 'pos.customers.store', $this->form());

        $other = Restaurant::create(['name' => 'OTRO']);
        Customer::withoutGlobalScopes()->whereKey(Customer::where('customer_name', 'ANA ROJAS')->value('id'))->update(['restaurant_id' => $other->id]);

        $this->ajax('get', 'pos.customers.search-name', ['name' => 'ANA'])->assertJsonPath('result', []);
    }

    public function test_a_new_permission_reaches_the_cashier_without_undoing_what_was_removed(): void
    {
        $cashier = Role::findByName('cashier', 'web');
        $cashier->revokePermissionTo('pos.customers.delete');
        Permission::where('name', 'pos.customers.create')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);

        $cashier = Role::findByName('cashier', 'web')->fresh();
        $this->assertTrue($cashier->hasPermissionTo('pos.customers.create'));
        $this->assertFalse($cashier->hasPermissionTo('pos.customers.delete'));
    }
}
