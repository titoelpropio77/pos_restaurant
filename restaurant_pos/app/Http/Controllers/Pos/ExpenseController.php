<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\CashRegister\CashSessions;
use App\Support\PosContext;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ingresos y egresos de caja desde el POS (pestaña Ingreso-Egreso). Port de gastosController.
 */
class ExpenseController extends Controller
{
    public function __construct(private PosContext $pos, private CashSessions $cashSessions) {}

    /**
     * Nombres a los que ya se entregó dinero, para el autocompletado.
     */
    public function delivered(): JsonResponse
    {
        $name = Expense::whereNotNull('delivered_to')
            ->where('delivered_to', '<>', '')
            ->distinct()
            ->orderBy('delivered_to')
            ->pluck('delivered_to');

        return PosResponse::ok($name);
    }

    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::where('branch_id', $this->pos->branchId())
            ->where('expense_date', Carbon::parse($request->input('date', today()))->toDateString())
            ->where('transaction_type', $request->input('typeTransaction') === 'INGRESO' ? 1 : 0)
            ->orderBy('id')
            ->get();

        return PosResponse::ok(PosResponse::rows($expenses, ['branch_id' => 'idBranch']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'typeExpense' => ['required', 'in:INGRESO,EGRESO'],
            'date' => ['required', 'date'],
            'delivered' => ['nullable', 'string', 'max:60'],
            'concept' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'shift' => ['nullable', 'string', 'max:6'],
            'dateInvoice' => ['nullable', 'date'],
            'taxIdSupplier' => ['nullable', 'string', 'max:25'],
            'numberAuthorization' => ['nullable', 'string', 'max:25'],
            'controlCode' => ['nullable', 'string', 'max:15'],
            'numberInvoice' => ['nullable', 'integer'],
            'vatTaxCredit' => ['nullable', 'numeric'],
            'hasTaxCredit' => ['nullable', 'in:0,1'],
            'hasInvoice' => ['nullable', 'in:0,1'],
            'leftCashRegister' => ['nullable', 'in:0,1'],
        ], [
            'typeExpense.required' => 'DEBE PRESIONAR EL BOTÓN NUEVO Y ELEGIR EL TIPO DE TRANSACCIÓN',
            'concept.required' => 'DEBE INDICAR EL CONCEPTO',
            'amount.gt' => 'EL MONTO DEBE SER MAYOR A CERO',
        ]);

        $hasInvoice = (int) ($data['hasInvoice'] ?? 0) === 1;

        $fromDrawer = (int) ($data['leftCashRegister'] ?? 1);

        $expense = DB::transaction(function () use ($request, $data, $hasInvoice, $fromDrawer) {
            $cashSessionId = $this->cashSessions->forMovement($request->user(), $fromDrawer === 1);

            $expense = Expense::create([
                'branch_id' => $this->pos->branchId(),
                'cash_session_id' => $cashSessionId,
                'expense_number' => 0,
                'expense_date' => Carbon::parse($data['date'])->toDateString(),
                'delivered_to' => $data['delivered'] ?? '',
                'concept' => $data['concept'],
                'expense_time' => now()->format('G:i'),
                'login' => $request->user()->username,
                'exchange_rate' => $this->pos->exchangeRate(),
                'amount' => $data['amount'],
                'shift' => $data['shift'] ?? '',
                'status' => 'ACTIVE',
                'transaction_type' => $data['typeExpense'] === 'INGRESO' ? 1 : 0,
                'supplier_invoice_number' => $hasInvoice ? ($data['numberInvoice'] ?? null) : null,
                'invoice_date' => $hasInvoice ? ($data['dateInvoice'] ?? null) : null,
                'supplier_tax_id' => $hasInvoice ? ($data['taxIdSupplier'] ?? null) : null,
                'supplier_authorization' => $hasInvoice ? ($data['numberAuthorization'] ?? null) : null,
                'supplier_control_code' => $hasInvoice ? ($data['controlCode'] ?? null) : null,
                'has_invoice' => (int) $hasInvoice,
                'invoice_exchange_rate' => $this->pos->exchangeRate(),
                'tax_book' => (int) ($data['hasTaxCredit'] ?? 0),
                'tax_credit' => $hasInvoice ? ($data['vatTaxCredit'] ?? null) : null,
                'from_cash_drawer' => $fromDrawer,
            ]);

            $expense->update(['expense_number' => $expense->id]);

            return $expense;
        });

        return PosResponse::ok($expense->id);
    }
}
