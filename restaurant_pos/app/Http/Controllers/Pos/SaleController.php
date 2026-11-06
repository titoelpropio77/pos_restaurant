<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\InvoiceRequest;
use App\Models\BillingAuthorization;
use App\Models\InvoiceSequence;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CashRegister\CashCount;
use App\Services\Sales\RegisterSale;
use App\Support\PosContext;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ventas del POS: cobro, histórico del día, anulación, datos de impresión y cierre de caja.
 * Port de facturarController, venGralController, detalleVentaController y REPORTEPHP/prueba5.php.
 */
class SaleController extends Controller
{
    /** Nombres de columna legacy que cambiaron en el nuevo esquema. */
    private const ALIAS_SALE = ['customer_id' => 'customer_code', 'branch_id' => 'idBranch'];

    private const ALIAS_DETAIL = ['sale_id' => 'invoice_ref'];

    public function __construct(private PosContext $pos) {}

    public function store(InvoiceRequest $request, RegisterSale $registrar): JsonResponse
    {
        $result = $registrar->run($request->validated(), $request->user());

        return PosResponse::ok($result['sale']->id, [
            'result2' => $result['order_number'],
            'numberInvoice' => $result['invoice_ref'],
            'productPrint' => '',
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->input('dateHistory', today()))->toDateString();

        $sales = Sale::where('branch_id', $this->pos->branchId())
            ->where('sale_date', $date)
            ->orderBy('id')
            ->get();

        return PosResponse::ok(PosResponse::rows($sales, self::ALIAS_SALE));
    }

    public function detail(Request $request): JsonResponse
    {
        $sale = Sale::where('branch_id', $this->pos->branchId())->findOrFail($request->integer('id'));

        return PosResponse::ok(
            PosResponse::rows($sale->details()->orderBy('item')->get(), self::ALIAS_DETAIL),
            ['result2' => [$this->saleForPrint($sale)]],
        );
    }

    /**
     * Anula o reactiva una venta (botones ANULAR / ACTIVAR del histórico).
     */
    public function void(Request $request): JsonResponse
    {
        $sale = Sale::where('branch_id', $this->pos->branchId())->findOrFail($request->integer('id'));
        $void = $request->input('status') === 'ANULAR';

        DB::transaction(function () use ($sale, $void) {
            $sale->update(['status' => $void ? 'INACTIVE' : 'ACTIVE', 'voided' => $void ? 'S' : '']);
            $sale->details()->update(['voided' => $void ? 'S' : '']);
        });

        return PosResponse::ok();
    }

    /**
     * Datos que el agente de impresión necesita para la factura y la comanda de cocina.
     * Legacy: REPORTEPHP/prueba5.php. Responde sin el sobre {error, result}: el frontend
     * imprime cuando `error` no viene definido, igual que con el script original.
     */
    public function print(Request $request): JsonResponse
    {
        $sale = Sale::where('branch_id', $this->pos->branchId())->findOrFail($request->integer('idSale'));
        $authorization = BillingAuthorization::currentFor($this->pos->branchId());

        $detail = $sale->details()->orderBy('item')->get()->map(fn (SaleItem $d) => [
            'invoice_ref' => $d->sale_id,
            'order_number' => $d->order_number,
            'quantity' => $d->quantity,
            'sale_price' => $d->sale_price,
            'sale_date' => $sale->sale_date->format('Y-m-d'),
            'product_name' => $d->product_name,
            'shift' => $d->shift ?? '',
            'family' => $d->family ?? '',
            'service_type' => $d->service_type ?? '',
            'login' => $d->login ?? '',
            'note' => $d->note ?? '',
            'subtotal' => $d->subtotal ?? '',
            'qr_code' => $d->qr_code ?? '',
        ]);

        return response()->json([
            'listSale' => [$this->saleForPrint($sale)],
            'listRestaurant' => $authorization ? [PosResponse::row($authorization)] : [],
            'listDSale' => $detail,
            'control_qr' => $sale->control_code ?? '',
        ]);
    }

    /**
     * Cajeros que registraron ventas en la fecha (para el cierre de caja).
     */
    public function cashiers(Request $request): JsonResponse
    {
        $logins = Sale::where('branch_id', $this->pos->branchId())
            ->where('sale_date', Carbon::parse($request->input('date', today()))->toDateString())
            ->distinct()
            ->orderBy('login')
            ->pluck('login')
            ->map(fn ($login) => ['login' => $login]);

        return PosResponse::ok($logins);
    }

    /**
     * Totales del arqueo de caja: facturado, crédito, consumo, anulado, contado, gastos e ingresos.
     */
    public function totalsClosing(Request $request, CashCount $cash_count): JsonResponse
    {
        $date = Carbon::parse($request->input('date', today()))->toDateString();
        $shift = (string) $request->input('shift', 'MAÑANA Y NOCHE');
        $allBranches = (int) $request->input('multiSuc', 0) !== 0;

        $totals = $cash_count->totals(
            $date,
            $date,
            $allBranches ? null : $this->pos->branchId(),
            in_array($shift, ['MAÑANA', 'NOCHE'], true) ? $shift : null,
            $request->input('employee') ?: null,
        );

        return PosResponse::ok([array_map(fn ($value) => number_format($value, 2, '.', ''), $totals)]);
    }

    /**
     * Cierre diario: marca como cerradas las ventas del turno/cajero y reinicia la orden del día.
     */
    public function closing(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->input('date', today()))->toDateString();
        $cashier = (string) $request->input('employee', '');
        $shift = match ($request->input('entry')) {
            'TURNOMAÑANA' => 'MAÑANA',
            'TURNONOCHE' => 'NOCHE',
            default => null,
        };

        DB::transaction(function () use ($date, $cashier, $shift) {
            $sales = Sale::where('branch_id', $this->pos->branchId())
                ->where('sale_date', $date)
                ->when($shift, fn ($q) => $q->where('shift', $shift))
                ->when($cashier !== '', fn ($q) => $q->where('login', $cashier));

            SaleItem::whereIn('sale_id', (clone $sales)->select('id'))->update(['closed' => 'S']);
            $sales->update(['closed' => 'S']);

            InvoiceSequence::where('branch_id', $this->pos->branchId())->update(['daily_order' => 0]);
        });

        $this->pos->setDailyOrder(0);

        return PosResponse::ok();
    }

    /**
     * Venta con los nombres de campo legacy y la fecha en formato d/m/Y (VEN_GRAL_MYSQL::buscarXID).
     */
    private function saleForPrint(Sale $sale): array
    {
        $row = PosResponse::row($sale, self::ALIAS_SALE);
        $row['sale_date'] = $sale->sale_date->format('d/m/Y');

        return $row;
    }
}
