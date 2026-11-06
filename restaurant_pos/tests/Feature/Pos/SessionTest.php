<?php

namespace Tests\Feature\Pos;

use App\Models\Role;
use App\Models\User;
use App\Models\UserLogin;

class SessionTest extends PosTestCase
{
    public function test_shows_the_modal_of_login_without_session(): void
    {
        $this->get('/')->assertOk()->assertSee('ModalLogin')->assertDontSee('js/pos/pos.js');
    }

    public function test_the_cashier_logs_in_and_registers_the_type_of_change(): void
    {
        $this->log_in();

        $this->get('/')->assertOk()->assertSee('idBranch = ', false)->assertSee('js/pos/pos.js');
        $this->assertDatabaseHas('user_logins', ['username' => 'cajero', 'exchange_rate' => 6.96]);
    }

    public function test_email_ignores_case_and_password_does_not(): void
    {
        $this->log_in('CaJeRo@Mesatek.LOCAL', 'cajero');
        $this->post('/logout');

        $this->post('/login', ['email' => 'cajero@mesatek.local', 'password' => 'CAJERO', 'exchangeRate' => '6.96'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_username_no_longer_logs_in(): void
    {
        $this->post('/login', ['email' => 'cajero', 'password' => 'cajero', 'exchangeRate' => '6.96'])->assertSessionHasErrors('email');
        $this->post('/login', ['username' => 'cajero', 'password' => 'cajero', 'exchangeRate' => '6.96'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_denied_action_follows_the_pos_json_contract(): void
    {
        $this->log_in();
        Role::findByName('cashier', 'web')->revokePermissionTo('pos.sales.void');
        // Cada petición real relee el usuario y su rol
        auth()->forgetGuards();

        $this->ajax('post', 'pos.sales.void', ['id' => 1, 'status' => 'ANULAR'])
            ->assertOk()->assertJson(['error' => 'NO TIENE PERMISO PARA ESTA ACCIÓN']);
    }

    public function test_rejects_credentials_invalid(): void
    {
        $this->post('/login', ['email' => 'cajero@mesatek.local', 'password' => 'other', 'exchangeRate' => '6.96'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rejects_user_without_point_of_sale(): void
    {
        User::create(['username' => 'sinpv', 'email' => 'sinpv@mesatek.local', 'name' => 'SIN PV', 'password' => 'clave']);

        $this->post('/login', ['email' => 'sinpv@mesatek.local', 'password' => 'clave', 'exchangeRate' => '6.96'])
            ->assertSessionHas('error_login', 'EL USUARIO NO TIENE UN PUNTO DE VENTA ASIGNADO');

        $this->assertGuest();
    }

    public function test_rejects_login_if_the_date_of_device_is_previous_to_last_login(): void
    {
        UserLogin::create(['username' => 'admin', 'login_date' => today()->addDay(), 'exchange_rate' => 6.96]);

        $this->post('/login', ['email' => 'cajero@mesatek.local', 'password' => 'cajero', 'exchangeRate' => '6.96'])
            ->assertSessionHas('error_login', 'LA FECHA NO ES LA ACTUAL, POR FAVOR COLOQUE LA FECHA CORRECTAMENTE');
    }

    public function test_the_requests_ajax_without_session_receive_error_session(): void
    {
        $this->ajax('get', 'pos.categories.index')->assertOk()->assertJson(['error' => 'Error Session']);
    }

    public function test_only_a_admin_authorizes_change_the_type_of_change(): void
    {
        $this->postJson(route('pos.exchange-rate.authorize'), ['email' => 'admin@mesatek.local', 'password' => 'admin'])
            ->assertJson(['result' => 'enabled']);
        $this->postJson(route('pos.exchange-rate.authorize'), ['email' => 'cajero@mesatek.local', 'password' => 'cajero'])
            ->assertJson(['result' => 'disabled']);
    }
}
