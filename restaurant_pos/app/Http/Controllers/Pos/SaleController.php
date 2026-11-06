<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\InvoiceRequest;
use App\Models\BillingAuthorization;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CashRegister\CashSessions;
use App\Services\Sales\RegisterSale;
use App\Support\PosContext;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ventas del POS: cobro, histórico del día, anulación y datos de impresión.
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
    public function void(Request $request, CashSessions $cashSessions): JsonResponse
    {
        $sale = Sale::where('branch_id', $this->pos->branchId())->findOrFail($request->integer('id'));
        $void = $request->input('status') === 'ANULAR';

        DB::transaction(function () use ($sale, $void, $cashSessions) {
            $cashSessions->ensureSaleEditable($sale);
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
     * Venta con los nombres de campo legacy y la fecha en formato d/m/Y (VEN_GRAL_MYSQL::buscarXID).
     */
    private function saleForPrint(Sale $sale): array
    {
        $row = PosResponse::row($sale, self::ALIAS_SALE);
        $row['sale_date'] = $sale->sale_date->format('d/m/Y');

        return $row;
    }
}
