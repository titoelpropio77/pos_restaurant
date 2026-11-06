<?php

namespace Tests\Feature\Admin;

use App\Models\CashSession;
use App\Models\PointOfSale;
use App\Models\Sale;

class CashSessionTest extends AdminTestCase
{
    private function openSession(): CashSession
    {
        $point = PointOfSale::firstOrFail();

        return CashSession::create([
            'branch_id' => $point->branch_id, 'point_of_sale_id' => $point->id, 'opened_by' => $point->user_id,
            'opened_at' => now(), 'opening_amount' => 50, 'status' => CashSession::OPEN,
        ]);
    }

    public function test_lists_open_and_closed_sessions(): void
    {
        $this->openSession();

        $this->get(route('admin.cash_sessions.index'))->assertOk()->assertSee('50.00');
    }

    public function test_closes_a_forgotten_session_with_a_reason(): void
    {
        $session = $this->openSession();

        $this->post(route('admin.cash_sessions.close', $session), ['counted_cash' => 40])->assertSessionHasErrors('notes');

        $this->post(route('admin.cash_sessions.close', $session), ['counted_cash' => 40, 'notes' => 'Olvidada'])
            ->assertSessionHas('ok');

        $session->refresh();
        $this->assertSame(CashSession::CLOSED, $session->status);
        $this->assertSame('-10.00', $session->difference);
        $this->assertSame($this->admin->id, $session->closed_by);

        $this->post(route('admin.cash_sessions.close', $session), ['counted_cash' => 40, 'notes' => 'Otra vez'])
            ->assertSessionHas('error', 'LA CAJA YA ESTÁ CERRADA');

        $this->get(route('admin.cash_sessions.index'))->assertOk()->assertSee('Olvidada');
    }

    public function test_the_panel_does_not_void_a_sale_of_a_closed_session(): void
    {
        $session = $this->openSession();
        $session->update(['status' => CashSession::CLOSED, 'closed_at' => now()]);
        $sale = Sale::create(['branch_id' => $session->branch_id, 'cash_session_id' => $session->id, 'sale_date' => today(), 'login' => 'CAJERO', 'status' => 'ACTIVE', 'payment_method' => 'CONTADO', 'invoice_total' => 10]);

        $this->post(route('admin.sales.void', $sale))->assertSessionHas('error', 'NO SE PUEDE MODIFICAR UNA VENTA DE UNA CAJA CERRADA');
        $this->assertSame('ACTIVE', $sale->fresh()->status);
    }
}
