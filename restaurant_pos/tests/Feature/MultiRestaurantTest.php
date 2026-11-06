<?php

namespace Tests\Feature;

use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InvoiceSequence;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Warehouse;
use App\Support\Tenant;
use Database\Seeders\BaseDataSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lo que un restaurante no debe poder ver ni tocar de otro.
 */
class MultiRestaurantTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $main;

    private Restaurant $other;

    private User $ownerOther;

    private Branch $branchOther;

    /** Tablas comunes a todos los restaurantes. Agregar aquí exige una decisión consciente. */
    private const TABLES_GLOBAL = [
        'cache', 'cache_locks', 'cities', 'countries', 'failed_jobs', 'job_batches', 'jobs', 'migrations',
        'password_reset_tokens', 'restaurants', 'sessions',
        'admins', 'audit_logs', 'restaurant_user', 'users', 'model_has_permissions', 'model_has_roles', 'permissions', 'role_has_permissions', 'roles',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([BaseDataSeeder::class, DemoSeeder::class]);
        $this->main = Restaurant::first();

        $this->other = Restaurant::create(['name' => 'OTRO RESTAURANTE']);
        Tenant::use($this->other->id);

        $this->branchOther = Branch::create(['name' => 'MATRIZ OTRO', 'status' => 'ACTIVE']);
        $this->branchOther->forceFill(['is_main' => true])->save();
        BillingAuthorization::create(['branch_id' => $this->branchOther->id, 'business_name' => 'OTRO', 'morning_start' => '06:00', 'morning_end' => '16:00']);
        $warehouse = Warehouse::create(['name' => 'ALMACEN OTRO', 'branch_id' => $this->branchOther->id]);
        $category = Category::create(['group_name' => 'CATEGORIA OTRO']);
        $product = Product::create(['product_name' => 'PLATO SECRETO OTRO', 'category_id' => $category->id, 'sale_price' => 10]);
        BranchProduct::create(['product_id' => $product->id, 'branch_id' => $this->branchOther->id, 'category_id' => $category->id]);
        Customer::create(['customer_name' => 'CLIENTE SECRETO OTRO']);

        $this->ownerOther = User::create([
            'username' => 'dueno2', 'email' => 'dueno2@mesatek.local', 'name' => 'DUEÑO DOS', 'password' => 'clave',
        ]);
        $this->ownerOther->assignRole('partner');
        PointOfSale::create([
            'user_id' => $this->ownerOther->id, 'branch_id' => $this->branchOther->id,
            'default_warehouse_id' => $warehouse->id, 'start_date' => today(),
        ]);

        Tenant::use($this->main->id);
    }

    private function asOtherRestaurant(): void
    {
        $this->post('/login', ['email' => 'dueno2@mesatek.local', 'password' => 'clave', 'exchangeRate' => '6.96'])
            ->assertRedirect(route('pos.index'));
    }

    public function test_all_table_of_business_has_restaurant_id_required(): void
    {
        $withoutRestaurant = collect(DB::select(<<<'SQL'
            SELECT t.table_name FROM information_schema.tables t
            LEFT JOIN information_schema.columns c
              ON c.table_schema = t.table_schema AND c.table_name = t.table_name
             AND c.column_name = 'restaurant_id' AND c.is_nullable = 'NO'
            WHERE t.table_schema = current_schema() AND t.table_type = 'BASE TABLE' AND c.column_name IS NULL
        SQL))->pluck('table_name')->diff(self::TABLES_GLOBAL)->values()->all();

        $this->assertSame([], $withoutRestaurant);
    }

    public function test_all_model_with_restaurant_id_this_filtered(): void
    {
        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            /** @var Model $modelo */
            $model = new $class;

            if (in_array($model->getTable(), self::TABLES_GLOBAL, true)) {
                continue;
            }

            $this->assertStringContainsString('restaurant_id', $class::query()->toSql(), "{$class} no está filtrado por restaurante");
        }
    }

    public function test_without_restaurant_in_context_a_request_not_ve_nothing(): void
    {
        Tenant::current()->startRequest(null);

        $this->assertSame(0, Product::count());
        $this->assertSame(0, User::count());
    }

    public function test_the_panel_not_shows_data_of_other_restaurant(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());

        foreach ([
            route('admin.products.index'),
            route('admin.catalogs.index', 'customers'),
            route('admin.catalogs.index', 'categories'),
            route('admin.catalogs.index', 'branches'),
            route('admin.users.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('SECRETO OTRO')->assertDontSee('CATEGORIA OTRO')
                ->assertDontSee('MATRIZ OTRO')->assertDontSee('DUEÑO DOS');
        }
    }

    public function test_the_pos_of_other_restaurant_keeps_seeing_the_own(): void
    {
        $this->asOtherRestaurant();

        $this->getJson(route('pos.categories.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJsonFragment(['group_name' => 'CATEGORIA OTRO']);
        $this->get(route('admin.products.index'))->assertOk()
            ->assertSee('PLATO SECRETO OTRO')->assertDontSee('PIQUE MACHO');
    }

    public function test_not_opens_records_of_other_restaurant_by_url(): void
    {
        Tenant::use($this->main->id);
        $sale = Sale::first() ?? null;
        $user = User::where('username', 'cajero')->first();
        $product = Product::first();

        $this->asOtherRestaurant();

        $this->get(route('admin.users.edit', $user))->assertNotFound();
        $this->get(route('admin.products.edit', $product))->assertNotFound();
        $this->get(route('admin.billing.edit', Branch::withoutGlobalScopes()->where('name', 'CASA MATRIZ')->first()))->assertNotFound();
        $this->get(route('admin.catalogs.edit', ['customers', Customer::withoutGlobalScopes()->where('customer_name', '<>', 'CLIENTE SECRETO OTRO')->value('id') ?? 0]))->assertNotFound();

        if ($sale) {
            $this->get(route('admin.sales.show', $sale))->assertNotFound();
        }
    }

    public function test_not_accepts_references_to_data_of_other_restaurant(): void
    {
        $this->asOtherRestaurant();

        $this->post(route('admin.products.store'), [
            'product_name' => 'NUEVO', 'category_id' => Category::withoutGlobalScopes()->where('restaurant_id', $this->main->id)->value('id'),
            'sale_price' => 5, 'status' => 'ACTIVE',
        ])->assertSessionHasErrors('category_id');
    }

    public function test_the_name_of_product_repeats_between_restaurants_but_not_inside_of_one(): void
    {
        $this->asOtherRestaurant();
        $category = Category::withoutGlobalScopes()->where('restaurant_id', $this->other->id)->value('id');

        $data = ['category_id' => $category, 'sale_price' => 5, 'quantity' => 1, 'unit_factor' => 1, 'status' => 'ACTIVE', 'type' => 'P'];

        $this->post(route('admin.products.store'), $data + ['product_name' => 'PIQUE MACHO'])->assertSessionDoesntHaveErrors('product_name');
        $this->post(route('admin.products.store'), $data + ['product_name' => 'PLATO SECRETO OTRO'])->assertSessionHasErrors('product_name');
    }

    public function test_the_created_remains_in_the_restaurant_of_user(): void
    {
        $this->asOtherRestaurant();

        $this->post(route('admin.catalogs.store', 'categories'), ['group_name' => 'NUEVA DEL OTRO', 'status' => 'ACTIVE', 'order_number' => 1]);

        $category = Category::withoutGlobalScopes()->where('group_name', 'NUEVA DEL OTRO')->first();

        $this->assertNotNull($category);
        $this->assertSame($this->other->id, $category->restaurant_id);
    }

    public function test_a_restaurant_inactive_not_logs_in_and_loses_the_session_open(): void
    {
        $this->asOtherRestaurant();
        $this->get(route('admin.dashboard'))->assertOk();

        $this->other->update(['status' => 'INACTIVE']);

        $this->get(route('admin.dashboard'))->assertRedirect(route('pos.index'));
        $this->assertGuest();

        $this->post('/login', ['email' => 'dueno2@mesatek.local', 'password' => 'clave', 'exchangeRate' => '6.96'])
            ->assertSessionHas('error_login');
        $this->assertGuest();
    }

    public function test_each_restaurant_keeps_its_type_of_change(): void
    {
        Tenant::use($this->other->id);
        UserLogin::create(['username' => 'dueno2', 'login_date' => today(), 'exchange_rate' => 7.50]);
        Tenant::use($this->main->id);

        // Sin autorización no cambia, aunque el formulario envíe otro valor
        $this->post('/login', ['email' => 'dueno2@mesatek.local', 'password' => 'clave', 'exchangeRate' => '6.96']);
        $this->assertDatabaseHas('user_logins', ['username' => 'dueno2', 'exchange_rate' => 7.50, 'restaurant_id' => $this->other->id]);
        $this->post('/logout');

        // Autorizado por un administrador de otro restaurante tampoco
        $this->postJson(route('pos.exchange-rate.authorize'), ['email' => 'admin@mesatek.local', 'password' => 'admin']);
        $this->post('/login', ['email' => 'dueno2@mesatek.local', 'password' => 'clave', 'exchangeRate' => '8']);
        $this->assertDatabaseMissing('user_logins', ['username' => 'dueno2', 'exchange_rate' => 8]);
        $this->post('/logout');

        // Autorizado por el suyo, sí
        $this->postJson(route('pos.exchange-rate.authorize'), ['email' => 'dueno2@mesatek.local', 'password' => 'clave']);
        $this->post('/login', ['email' => 'dueno2@mesatek.local', 'password' => 'clave', 'exchangeRate' => '8']);
        $this->assertDatabaseHas('user_logins', ['username' => 'dueno2', 'exchange_rate' => 8]);
    }

    public function test_a_restaurant_has_a_only_main_branch(): void
    {
        $this->expectException(QueryException::class);

        Tenant::use($this->other->id);
        Branch::create(['name' => 'OTRA'])->forceFill(['is_main' => true])->save();
    }

    public function test_the_main_branch_not_gives_of_deactivation(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        $main = Branch::where('is_main', true)->first();

        $this->delete(route('admin.catalogs.destroy', ['branches', $main->id]))->assertSessionHas('error');

        $this->assertSame('ACTIVE', $main->fresh()->status);
    }

    public function test_the_role_limits_the_sections_of_panel(): void
    {
        $manager = User::create([
            'username' => 'manager', 'email' => 'manager@mesatek.local', 'name' => 'E', 'password' => 'clave',
        ]);
        $manager->assignRole(Role::create(['name' => 'manager', 'label' => 'Encargado', 'guard_name' => 'web'])->givePermissionTo(['panel.access', 'products.view']));

        $this->actingAs($manager->fresh());

        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.billing.index'))->assertForbidden();
    }

    public function test_a_sale_remains_in_its_restaurant_with_numbering_own(): void
    {
        Tenant::use($this->other->id);
        InvoiceSequence::create(['table_name' => 'sales', 'branch_id' => $this->branchOther->id, 'folio' => 0, 'real_folio' => 0, 'daily_order' => 0]);
        $authorization = BillingAuthorization::where('branch_id', $this->branchOther->id)->first();
        $authorization->update(['authorization_number' => '29040011007', 'auth_key' => '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A', 'limit_date' => today()->addYear()->toDateString(), 'first_invoice_number' => 1]);
        $product = Product::where('product_name', 'PLATO SECRETO OTRO')->first();
        Tenant::use($this->main->id);

        $this->asOtherRestaurant();

        $response = $this->postJson(route('pos.sales.store'), [
            'idProduct' => [$product->id], 'quantity' => [2], 'price' => [10], 'subTotal' => [20], 'note' => [''], 'qr' => [''],
            'nameProduct' => ['PLATO SECRETO OTRO'], 'nameCustomer' => 'SIN NOMBRE', 'customerTaxId' => '', 'totalBs' => 20, 'totalInvoiceBs' => 20,
            'changeBs' => 0, 'paymentMethod' => 'CONTADO', 'shift' => 'NOCHE', 'exitProduct' => 'LLEVAR', 'saleDate' => today()->toDateString(),
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJson(['error' => ''])->json();

        $this->assertSame(1, $response['result2']);
        $this->assertDatabaseHas('sales', ['id' => $response['result'], 'restaurant_id' => $this->other->id, 'branch_id' => $this->branchOther->id]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $response['result'], 'restaurant_id' => $this->other->id]);
        $this->assertSame(0, InvoiceSequence::withoutGlobalScopes()->where('branch_id', Branch::withoutGlobalScopes()->where('name', 'CASA MATRIZ')->value('id'))->value('daily_order'));

        // El reporte agrupado por vista SQL tampoco cruza restaurantes
        $this->assertSame(0, DB::table('report_group_sales_view')->where('restaurant_id', $this->main->id)->count());
    }

    public function test_a_role_without_permission_of_sale_not_sells(): void
    {
        $role = Role::create(['name' => 'readonly', 'label' => 'Solo consulta', 'guard_name' => 'web']);
        $this->ownerOther->syncRoles($role);

        $this->assertFalse($this->ownerOther->fresh()->can('pos.access'));
        $this->post('/login', ['email' => 'dueno2@mesatek.local', 'password' => 'clave', 'exchangeRate' => '6.96'])->assertSessionHas('error_login');
        $this->assertGuest();
    }

    private function secondRestaurantForAdmin(): User
    {
        $admin = User::withoutGlobalScopes()->where('username', 'admin')->first();
        $admin->restaurants()->attach($this->other->id);

        Tenant::use($this->other->id);
        PointOfSale::create(['user_id' => $admin->id, 'branch_id' => $this->branchOther->id, 'start_date' => today()]);
        Tenant::use($this->main->id);

        return $admin;
    }

    public function test_a_partner_works_with_each_of_its_restaurants(): void
    {
        $this->secondRestaurantForAdmin();

        $this->post('/login', ['email' => 'admin@mesatek.local', 'password' => 'admin', 'exchangeRate' => '6.96'])->assertRedirect();
        $this->get(route('admin.products.index'))->assertOk()->assertSee('PIQUE MACHO')->assertDontSee('PLATO SECRETO OTRO')->assertSee('OTRO RESTAURANTE');

        $this->from(route('admin.products.index'))->post(route('restaurant.switch'), ['restaurant_id' => $this->other->id])
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.products.index'))->assertOk()->assertSee('PLATO SECRETO OTRO')->assertDontSee('PIQUE MACHO');
    }

    public function test_the_partner_returns_to_the_last_restaurant_where_it_worked(): void
    {
        $this->secondRestaurantForAdmin();

        $this->post('/login', ['email' => 'admin@mesatek.local', 'password' => 'admin', 'exchangeRate' => '6.96']);
        $this->post(route('restaurant.switch'), ['restaurant_id' => $this->other->id]);
        $this->post('/logout');

        $this->post('/login', ['email' => 'admin@mesatek.local', 'password' => 'admin', 'exchangeRate' => '6.96']);

        $this->get(route('admin.products.index'))->assertSee('PLATO SECRETO OTRO');
    }

    public function test_nobody_switches_to_a_restaurant_that_is_not_theirs(): void
    {
        $this->post('/login', ['email' => 'admin@mesatek.local', 'password' => 'admin', 'exchangeRate' => '6.96']);

        $this->post(route('restaurant.switch'), ['restaurant_id' => $this->other->id])->assertForbidden();

        $this->get(route('admin.products.index'))->assertSee('PIQUE MACHO');
    }

    public function test_a_stolen_session_value_does_not_open_another_restaurant(): void
    {
        $this->actingAs(User::where('username', 'admin')->first())
            ->withSession([Tenant::SESSION_KEY => $this->other->id])
            ->get(route('admin.products.index'))
            ->assertRedirect(route('pos.index'));

        $this->assertGuest();
    }

    public function test_losing_the_restaurant_closes_the_session(): void
    {
        $admin = $this->secondRestaurantForAdmin();
        $this->post('/login', ['email' => 'admin@mesatek.local', 'password' => 'admin', 'exchangeRate' => '6.96']);
        $this->post(route('restaurant.switch'), ['restaurant_id' => $this->other->id]);

        $admin->restaurants()->detach($this->other->id);

        $this->get(route('admin.dashboard'))->assertRedirect(route('pos.index'));
        $this->assertGuest();
    }

    public function test_a_partner_can_not_touch_another_partner_of_the_same_restaurant(): void
    {
        $partner = User::where('username', 'admin')->first();
        $other = User::create(['username' => 'socio', 'email' => 'socio@mesatek.local', 'name' => 'SOCIO', 'password' => 'clave']);
        $other->assignRole('partner');

        $this->actingAs($partner->fresh());

        $this->get(route('admin.users.edit', $other))->assertForbidden();
        $this->put(route('admin.users.update', $other), ['name' => 'X', 'username' => 'socio', 'email' => 'x@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1'])->assertForbidden();
        $this->delete(route('admin.users.destroy', $other))->assertForbidden();

        $this->assertSame('socio@mesatek.local', $other->fresh()->email);
    }
}
