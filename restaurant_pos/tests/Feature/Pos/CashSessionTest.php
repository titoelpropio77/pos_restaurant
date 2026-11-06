<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\InvoiceSequence;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashRegister\CashSessions;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CashSessionTest extends PosTestCase
{
    private function sale(array $extra = []): int
    {
        $product = Product::where('product_name', 'FLAN')->firstOrFail();

        return (int) $this->ajax('post', 'pos.sales.store', $extra + [
            'saleDate' => today()->toDateString(),
            'paymentMethod' => 'CONTADO',
            'totalBs' => 10,
            'exitProduct' => 'MESA',
            'shift' => 'NOCHE',
            'idProduct' => [$product->id],
            'quantity' => [1],
            'price' => [10],
            'nameProduct' => ['FLAN'],
            'subTotal' => [10],
            'seller_id' => 0,
        ])->assertJsonPath('error', '')->json('result');
    }

    public function test_the_pos_asks_to_open_cash_only_once(): void
    {
        $this->log_in(openCash: false);
        $this->get(route('pos.index'))->assertSee("$('#ModalOpenCash').modal('show');", false);

        $this->ajax('post', 'pos.cash_register.open', ['openingAmount' => 100])->assertJsonPath('error', '');
        $this->get(route('pos.index'))->assertDontSee("$('#ModalOpenCash').modal('show');", false);

        $this->ajax('post', 'pos.cash_register.open', ['openingAmount' => 50])->assertJsonPath('error', 'LA CAJA YA ESTÁ ABIERTA');
        $this->assertSame(1, CashSession::count());
    }

    public function test_logging_in_again_continues_the_same_cash_session(): void
    {
        $this->log_in();
        $this->post(route('logout'));
        $this->log_in(openCash: false);

        $this->get(route('pos.index'))->assertViewHas('mustOpenCash', false);
    }

    public function test_the_database_rejects_a_second_open_session(): void
    {
        $this->log_in();
        $session = CashSession::firstOrFail();

        $this->expectException(UniqueConstraintViolationException::class);

        $session->replicate()->save();
    }

    public function test_without_open_cash_nothing_that_moves_cash_is_registered(): void
    {
        $this->log_in(openCash: false);

        $this->ajax('post', 'pos.sales.store', [
            'saleDate' => today()->toDateString(), 'paymentMethod' => 'CONTADO', 'totalBs' => 10, 'idProduct' => [Product::first()->id],
            'quantity' => [1], 'price' => [10], 'nameProduct' => ['X'], 'subTotal' => [10],
        ])->assertJsonPath('error', 'DEBE ABRIR CAJA');
        $this->assertSame(0, Sale::count());

        $expense = ['typeExpense' => 'EGRESO', 'date' => today()->toDateString(), 'concept' => 'GAS', 'amount' => 5];
        $this->ajax('post', 'pos.expenses.store', $expense)->assertJsonPath('error', 'DEBE ABRIR CAJA');

        // Lo que no sale del cajón no necesita caja abierta
        $this->ajax('post', 'pos.expenses.store', $expense + ['leftCashRegister' => 0])->assertJsonPath('error', '');
        $this->assertNull(Expense::firstOrFail()->cash_session_id);
    }

    public function test_the_option_off_keeps_the_pos_working_without_cash_sessions(): void
    {
        $restaurant = Restaurant::firstOrFail();
        $restaurant->update(['options' => ['daily_close' => false] + (array) $restaurant->options]);

        $this->log_in(openCash: false);
        $this->get(route('pos.index'))->assertViewHas('mustOpenCash', false);

        $this->assertNull(Sale::findOrFail($this->sale())->cash_session_id);
        $this->assertSame(0, CashSession::count());
    }

    public function test_the_blind_closing_computes_expected_and_difference(): void
    {
        $this->log_in(openCash: false);
        $this->ajax('post', 'pos.cash_register.open', ['openingAmount' => 100]);

        $this->sale();
        $this->sale(['paymentMethod' => 'QR']);
        $this->ajax('post', 'pos.expenses.store', ['typeExpense' => 'EGRESO', 'date' => today()->toDateString(), 'concept' => 'GAS', 'amount' => 4]);
        $this->ajax('post', 'pos.expenses.store', ['typeExpense' => 'INGRESO', 'date' => today()->toDateString(), 'concept' => 'CAMBIO', 'amount' => 6]);

        $this->ajax('post', 'pos.cash_register.closing', ['countedCash' => 110])
            ->assertJsonPath('error', '')
            ->assertJsonPath('result.0.opening', '100.00')
            ->assertJsonPath('result.0.expected', '112.00')
            ->assertJsonPath('result.0.difference', '-2.00');

        $session = CashSession::firstOrFail();
        $this->assertSame(CashSession::CLOSED, $session->status);
        $this->assertSame('112.00', $session->expected_cash);
        $this->assertSame(0, Sale::where('closed', '<>', 'S')->count());

        $this->ajax('post', 'pos.cash_register.note', ['sessionId' => $session->id, 'note' => 'Faltó cambio'])->assertJsonPath('error', '');
        $this->assertSame('Faltó cambio', $session->fresh()->notes);

        $this->ajax('post', 'pos.cash_register.note', ['sessionId' => $session->id, 'note' => 'Otra'])
            ->assertJsonPath('error', 'EL CIERRE NO EXISTE O YA TIENE NOTA');

        $this->get(route('pos.index'))->assertViewHas('mustOpenCash', true);
    }

    public function test_only_cash_prepaid_cards_count_as_cash(): void
    {
        $this->log_in();

        $this->sale(['paymentMethod' => 'PREPAGO', 'selectPrepaid' => 'CONTADO']);
        $this->sale(['paymentMethod' => 'PREPAGO', 'selectPrepaid' => 'TARJETA']);

        $this->ajax('post', 'pos.cash_register.closing', ['countedCash' => 10])
            ->assertJsonPath('result.0.expected', '10.00');
    }

    public function test_a_sale_of_a_closed_cash_session_can_not_be_voided(): void
    {
        $this->log_in();
        $sale = $this->sale();
        $open = $this->sale();

        $this->ajax('post', 'pos.sales.void', ['id' => $open, 'status' => 'ANULAR'])->assertJsonPath('error', '');
        $this->ajax('post', 'pos.cash_register.closing', ['countedCash' => 10]);

        foreach (['ANULAR', 'ACTIVAR'] as $status) {
            $this->ajax('post', 'pos.sales.void', ['id' => $sale, 'status' => $status])
                ->assertJsonPath('error', 'NO SE PUEDE MODIFICAR UNA VENTA DE UNA CAJA CERRADA');
        }

        $this->assertSame('ACTIVE', Sale::findOrFail($sale)->status);
    }

    public function test_the_daily_order_resets_when_the_last_cash_of_the_branch_closes(): void
    {
        $this->log_in();
        $this->sale();
        $user = User::where('email', 'cajero@mesatek.local')->firstOrFail();
        $mine = PointOfSale::where('user_id', $user->id)->firstOrFail();

        $other = User::where('id', '<>', $user->id)->firstOrFail();
        $otherPoint = PointOfSale::where('user_id', $other->id)->first()
            ?? PointOfSale::create(['branch_id' => $mine->branch_id, 'user_id' => $other->id, 'can_sell' => true]);
        $otherSession = CashSession::create([
            'branch_id' => $mine->branch_id, 'point_of_sale_id' => $otherPoint->id, 'opened_by' => $other->id,
            'opened_at' => now(), 'opening_amount' => 0, 'status' => CashSession::OPEN,
        ]);

        $this->ajax('post', 'pos.cash_register.closing', ['countedCash' => 10])->assertJsonPath('error', '');
        $this->assertGreaterThan(0, InvoiceSequence::first()->daily_order);

        app(CashSessions::class)->close($otherSession, 0, null, $other);
        $this->assertSame(0, InvoiceSequence::first()->daily_order);
    }

    public function test_the_branch_does_not_change_with_the_cash_open(): void
    {
        $this->log_in();
        $point = PointOfSale::firstWhere('user_id', User::where('email', 'cajero@mesatek.local')->value('id'));
        $point->update(['multi_branch_sales' => true]);
        $this->post(route('logout'));
        $this->log_in(openCash: false);

        $other = Branch::where('id', '<>', $point->branch_id)->first()
            ?? Branch::create(['name' => 'OTRA', 'status' => 'ACTIVE']);

        $this->ajax('post', 'pos.branches.change', ['idSuc' => $other->id])
            ->assertJsonPath('error', 'CIERRE LA CAJA ANTES DE CAMBIAR DE SUCURSAL');
    }

    public function test_opening_requires_its_permission(): void
    {
        Role::findByName('cashier', 'web')->revokePermissionTo('pos.cash.open');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->log_in(openCash: false);

        $this->ajax('post', 'pos.cash_register.open', ['openingAmount' => 10])
            ->assertJsonPath('error', 'NO TIENE PERMISO PARA ESTA ACCIÓN');
        $this->assertSame(0, DB::table('cash_sessions')->count());
    }
}
