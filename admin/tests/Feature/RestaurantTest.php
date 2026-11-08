<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PointOfSale;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RestaurantTest extends AdminTestCase
{
    private function create(array $changes = []): Restaurant
    {
        $this->post(route('restaurants.store'), $this->dataRestaurant($changes))->assertSessionHasNoErrors();

        return Restaurant::latest('id')->firstOrFail();
    }

    public function test_creates_the_restaurant_ready_for_log_in(): void
    {
        $restaurant = $this->create();

        $this->assertSame('LA PACEÑA', $restaurant->name);

        $main = $restaurant->main;
        $this->assertSame('CASA MATRIZ', $main->name);
        $this->assertCount(1, $main->warehouses);
        $this->assertDatabaseHas('invoice_sequences', ['branch_id' => $main->id, 'table_name' => 'sales', 'restaurant_id' => $restaurant->id]);
        $this->assertDatabaseHas('movement_reasons', ['name' => 'VENTA', 'restaurant_id' => $restaurant->id]);
        $this->assertDatabaseHas('units', ['abbreviation' => 'KG', 'restaurant_id' => $restaurant->id]);

        $owner = User::where('username', 'ana')->firstOrFail();
        $this->assertTrue($owner->restaurants->contains($restaurant));
        $this->assertTrue($owner->hasRole('partner'));
        $this->assertSame('ana@mesatek.local', $owner->email);
        $this->assertSame($main->id, PointOfSale::where('user_id', $owner->id)->value('branch_id'));
        $this->assertSame($restaurant->id, PointOfSale::where('user_id', $owner->id)->value('restaurant_id'));
    }

    public function test_the_owner_email_is_unique_in_all_the_system(): void
    {
        $this->create();

        $this->post(route('restaurants.store'), $this->dataRestaurant(['name' => 'Otro', 'owner_user' => 'otra', 'owner_email' => 'ANA@mesatek.local']))
            ->assertSessionHasErrors('owner_email');
    }

    public function test_a_partner_can_belong_to_several_restaurants(): void
    {
        $uno = $this->create();
        $ana = User::where('username', 'ana')->first();

        $this->post(route('restaurants.store'), $this->dataRestaurant([
            'name' => 'Dos', 'partners' => [$ana->id], 'owner_names' => '', 'owner_user' => '', 'owner_email' => '', 'owner_password' => '', 'owner_password_confirmation' => '',
        ]))->assertSessionHasNoErrors();

        $dos = Restaurant::where('name', 'DOS')->firstOrFail();

        $this->assertCount(2, $ana->fresh()->restaurants);
        $this->assertNotNull($ana->pointOfSaleIn($uno->id));
        $this->assertSame($dos->main->id, $ana->pointOfSaleIn($dos->id)->branch_id);
    }

    public function test_the_multiselect_replaces_the_partners_of_the_restaurant(): void
    {
        $restaurant = $this->create();
        $ana = User::where('username', 'ana')->first();
        $this->create(['name' => 'Dos', 'owner_user' => 'luz', 'owner_email' => 'luz@mesatek.local']);
        $luz = User::where('username', 'luz')->first();

        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => [$luz->id]])->assertSessionHasNoErrors();

        $this->assertSame([$luz->id], $restaurant->partners()->pluck('users.id')->all());
        $this->assertFalse($ana->fresh()->restaurants->contains($restaurant));
        $this->assertNotNull($luz->pointOfSaleIn($restaurant->id));
    }

    public function test_the_multiselect_keeps_the_cashiers_of_the_restaurant(): void
    {
        $restaurant = $this->create();
        $ana = User::where('username', 'ana')->first();

        $this->post(route('users.store'), [
            'restaurant_id' => $restaurant->id, 'name' => 'Luis', 'username' => 'luis', 'email' => 'luis@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'role_id' => Role::findByName('cashier', 'web')->id, 'status' => 'ACTIVE', 'branch_id' => $restaurant->main->id, 'can_sell' => 1,
        ])->assertSessionHasNoErrors();

        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => [$ana->id]])->assertSessionHasNoErrors();

        $this->assertCount(2, $restaurant->users()->get());
    }

    public function test_a_restaurant_needs_at_least_one_partner(): void
    {
        $restaurant = $this->create();

        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => []])->assertSessionHasErrors('partners');
    }

    public function test_only_partners_can_be_owners(): void
    {
        $restaurant = $this->create();
        $this->post(route('users.store'), [
            'restaurant_id' => $restaurant->id, 'name' => 'Luis', 'username' => 'luis', 'email' => 'luis@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'role_id' => Role::findByName('cashier', 'web')->id, 'status' => 'ACTIVE', 'branch_id' => $restaurant->main->id,
        ]);

        $this->put(route('restaurants.update', $restaurant), [
            'name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => [User::where('username', 'luis')->value('id')],
        ])->assertSessionHasErrors('partners.0');
    }

    public function test_the_password_is_stored_as_typed(): void
    {
        $this->create(['owner_password' => 'MiClave', 'owner_password_confirmation' => 'MiClave']);

        $this->assertTrue(Hash::check('MiClave', User::where('username', 'ana')->value('password')));
    }

    public function test_the_authorization_starts_expired_and_without_authorization(): void
    {
        $restaurant = $this->create();

        $authorization = DB::table('billing_authorizations')->where('restaurant_id', $restaurant->id)->first();

        $this->assertNull($authorization->authorization_number);
        $this->assertNull($authorization->auth_key);
        $this->assertTrue($authorization->limit_date < today()->toDateString());
    }

    public function test_the_user_of_owner_is_unique_in_all_the_system(): void
    {
        $this->create();

        $this->post(route('restaurants.store'), $this->dataRestaurant(['name' => 'Otro', 'owner_user' => 'ANA']))
            ->assertSessionHasErrors('owner_user');

        $this->assertSame(1, Restaurant::count());
    }

    public function test_if_fails_not_remains_nothing_to_half(): void
    {
        $this->post(route('restaurants.store'), $this->dataRestaurant(['main_city_id' => 999999]))
            ->assertSessionHasErrors('main_city_id');

        $this->assertSame(0, Restaurant::count());
        $this->assertSame(0, Branch::count());
    }

    public function test_give_of_deactivation_and_reactivate_remains_in_audit(): void
    {
        $restaurant = $this->create();

        $this->post(route('restaurants.status', $restaurant));
        $this->assertSame('INACTIVE', $restaurant->fresh()->status);

        $this->post(route('restaurants.status', $restaurant));
        $this->assertSame('ACTIVE', $restaurant->fresh()->status);

        $this->assertSame(2, AuditLog::where('action', 'STATUS')->count());
        $this->get(route('audit.index'))->assertOk()->assertSee('LA PACEÑA');
    }

    public function test_the_form_sends_and_shows_the_options_of_pos(): void
    {
        $restaurant = $this->create();
        $restaurant->update(['options' => ['daily_close' => true]]);

        $response = $this->get(route('restaurants.edit', $restaurant))->assertOk();

        foreach (array_keys(Restaurant::OPTIONS) as $key) {
            $response->assertSee('name="options['.$key.']"', false);
        }

        $response->assertSee('id="fieldoptions_daily_close_" name="options[daily_close]" value="1" checked', false);
        // Opción aún no guardada: muestra su valor inicial (la facturación viene marcada)
        $response->assertSee('id="fieldoptions_billing_" name="options[billing]" value="1" checked', false);
        $response->assertDontSee('id="fieldoptions_billiards_" name="options[billiards]" value="1" checked', false);
    }

    public function test_billing_can_be_turned_off(): void
    {
        $restaurant = $this->create();

        $this->put(route('restaurants.update', $restaurant), [
            'name' => 'La Paceña', 'status' => 'ACTIVE', 'options' => ['daily_close' => 1],
            'partners' => $restaurant->partners()->pluck('users.id')->all(),
        ])->assertSessionHasNoErrors();
        $this->assertFalse($restaurant->fresh()->options['billing']);

        $this->put(route('restaurants.update', $restaurant), [
            'name' => 'La Paceña', 'status' => 'ACTIVE', 'options' => ['billing' => 1],
            'partners' => $restaurant->partners()->pluck('users.id')->all(),
        ])->assertSessionHasNoErrors();
        $this->assertTrue($restaurant->fresh()->options['billing']);
    }

    public function test_saves_the_options_of_pos_by_restaurant(): void
    {
        $restaurant = $this->create();

        $this->put(route('restaurants.update', $restaurant), [
            'name' => 'La Paceña', 'status' => 'ACTIVE', 'options' => ['billiards' => 1],
            'partners' => $restaurant->partners()->pluck('users.id')->all(),
        ])->assertSessionHasNoErrors();

        $this->assertTrue($restaurant->fresh()->options['billiards']);
        $this->assertFalse($restaurant->fresh()->options['dining_room']);
    }

    public function test_a_branch_new_not_is_main_and_brings_its_warehouse(): void
    {
        $restaurant = $this->create();

        $this->post(route('branches.store'), ['restaurant_id' => $restaurant->id, 'name' => 'Sopocachi'])->assertSessionHasNoErrors();

        $branch = Branch::where('name', 'SOPOCACHI')->firstOrFail();
        $this->assertFalse($branch->is_main);
        $this->assertCount(1, $branch->warehouses);
    }

    public function test_changes_the_main_branch_and_always_there_is_a_only(): void
    {
        $restaurant = $this->create();
        $previous = $restaurant->main;
        $this->post(route('branches.store'), ['restaurant_id' => $restaurant->id, 'name' => 'Sopocachi']);
        $new = Branch::where('name', 'SOPOCACHI')->firstOrFail();

        $this->post(route('branches.main', $new))->assertSessionHas('ok');

        $this->assertTrue($new->fresh()->is_main);
        $this->assertFalse($previous->fresh()->is_main);
        $this->assertSame(1, Branch::where('restaurant_id', $restaurant->id)->where('is_main', true)->count());
    }

    public function test_the_main_branch_not_gives_of_deactivation(): void
    {
        $main = $this->create()->main;

        $this->post(route('branches.status', $main))->assertSessionHas('error');

        $this->assertSame('ACTIVE', $main->fresh()->status);
    }

    public function test_creates_a_user_in_a_branch_of_its_restaurant(): void
    {
        $restaurant = $this->create();

        $this->post(route('users.store'), [
            'restaurant_id' => $restaurant->id, 'name' => 'Luis', 'username' => 'luis', 'email' => 'luis@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'role_id' => Role::findByName('cashier', 'web')->id,
            'status' => 'ACTIVE', 'branch_id' => $restaurant->main->id, 'can_sell' => 1,
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'luis')->firstOrFail();
        $this->assertTrue($user->pointOfSaleIn($restaurant->id)->can_sell);
    }

    public function test_not_assigns_a_user_to_a_branch_of_other_restaurant(): void
    {
        $one = $this->create();
        $two = $this->create(['name' => 'Dos', 'owner_user' => 'two', 'owner_email' => 'two@mesatek.local']);

        $this->post(route('users.store'), [
            'restaurant_id' => $two->id, 'name' => 'Luis', 'username' => 'luis', 'email' => 'luis@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'role_id' => Role::findByName('cashier', 'web')->id, 'status' => 'ACTIVE', 'branch_id' => $one->main->id,
        ])->assertSessionHasErrors('branch_id');
    }

    public function test_not_assigns_a_role_of_system_to_a_user_of_restaurant(): void
    {
        $restaurant = $this->create();

        $this->post(route('users.store'), [
            'restaurant_id' => $restaurant->id, 'name' => 'Luis', 'username' => 'luis', 'email' => 'luis@mesatek.local', 'password' => 'clave1', 'password_confirmation' => 'clave1',
            'role_id' => $this->supportRole()->id, 'status' => 'ACTIVE', 'branch_id' => $restaurant->main->id,
        ])->assertSessionHasErrors('role_id');
    }

    public function test_edit_without_password_keeps_the_previous(): void
    {
        $restaurant = $this->create();
        $owner = User::where('username', 'ana')->firstOrFail();
        $hash = $owner->password;

        $this->put(route('users.update', $owner), [
            'name' => 'Ana', 'username' => 'ana', 'email' => 'ana@mesatek.local', 'role_id' => Role::findByName('partner', 'web')->id, 'status' => 'ACTIVE', 'branch_id' => $restaurant->main->id, 'can_sell' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame($hash, $owner->fresh()->password);
    }
    public function test_saves_the_logo_and_keeps_it_when_editing_without_a_new_file(): void
    {
        $restaurant = $this->create(['logo' => UploadedFile::fake()->image('logo.png', 200, 200)]);

        $this->assertStringStartsWith('data:image/png;base64,', $restaurant->logo);

        $partners = $restaurant->partners()->pluck('users.id')->all();
        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => $partners])->assertSessionHasNoErrors();
        $this->assertSame($restaurant->logo, $restaurant->fresh()->logo);

        $this->get(route('restaurants.edit', $restaurant))->assertOk()->assertSee('Quitar logo');

        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => $partners, 'remove_logo' => 1])->assertSessionHasNoErrors();
        $this->assertNull($restaurant->fresh()->logo);
    }

    public function test_rejects_a_logo_that_is_not_an_image_or_too_big(): void
    {
        $this->post(route('restaurants.store'), $this->dataRestaurant(['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('logo');
        $this->post(route('restaurants.store'), $this->dataRestaurant(['logo' => UploadedFile::fake()->image('logo.png')->size(Restaurant::LOGO_MAX_KB + 1)]))
            ->assertSessionHasErrors('logo');
    }

    public function test_saves_the_store_background_from_the_palette_or_a_custom_color(): void
    {
        $restaurant = $this->create(['background_color' => '#14213D']);
        $this->assertSame('#14213D', $restaurant->background_color);

        $partners = $restaurant->partners()->pluck('users.id')->all();
        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => $partners, 'background_color' => '#a1b2c3'])->assertSessionHasNoErrors();
        $this->assertSame('#A1B2C3', $restaurant->fresh()->background_color);

        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => $partners, 'background_color' => 'azul'])->assertSessionHasErrors('background_color');

        $this->put(route('restaurants.update', $restaurant), ['name' => 'La Paceña', 'status' => 'ACTIVE', 'partners' => $partners, 'background_color' => ''])->assertSessionHasNoErrors();
        $this->assertNull($restaurant->fresh()->background_color);
    }
}
