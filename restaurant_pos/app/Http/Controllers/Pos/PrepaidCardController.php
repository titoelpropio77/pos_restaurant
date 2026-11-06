<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Services\StockMovement\SupplyConsumption;
use App\Support\PosContext;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tarjetas prepago ("gift"): una venta cuyo número de factura identifica la tarjeta y cuyo
 * campo pre_pago guarda el saldo. Port de deTajetaController.
 */
class PrepaidCardController extends Controller
{
    public function __construct(private PosContext $pos) {}

    public function search(Request $request): JsonResponse
    {
        $card = Sale::where('invoice_ref', $request->integer('card'))->first();

        if (! $card) {
            return PosResponse::error('NO EXISTE LA TARJETA');
        }

        return PosResponse::ok(
            [PosResponse::row($card, ['customer_id' => 'customer_code', 'branch_id' => 'idBranch'])],
            ['result2' => PosResponse::rows($card->consumptionsCard()->orderBy('id')->get(), ['sale_id' => 'FACTURA'])],
        );
    }

    public function consume(Request $request): JsonResponse
    {
        $data = $request->validate([
            'card' => ['required', 'integer'],
            'idProduct' => ['required', 'array', 'min:1'],
            'idProduct.*' => ['integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['numeric', 'gt:0'],
            'shift' => ['nullable', 'string'],
            'saleDate' => ['required', 'date'],
            'newBalance' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $date = Carbon::parse($data['saleDate'])->toDateString();
        $time = now()->format('G:i');

        DB::transaction(function () use ($data, $user, $date, $time) {
            $card = Sale::where('invoice_ref', $data['card'])->lockForUpdate()->firstOrFail();
            $consumption = new SupplyConsumption($this->pos->warehouseId(), $user->id, $card->id, $date, $time);

            foreach ($data['idProduct'] as $i => $productId) {
                $product = Product::forPos($this->pos->branchId())->where('products.id', $productId)->firstOrFail();

                $card->consumptionsCard()->create([
                    'branch_id' => $this->pos->branchId(),
                    'order_number' => $this->pos->dailyOrder(),
                    'item' => $i + 1,
                    'product_code' => $product->id,
                    'quantity' => $data['quantity'][$i],
                    'cost_price' => 0,
                    'sale_price' => $product->sale_price,
                    'exchange_rate' => $this->pos->exchangeRate(),
                    'sale_date' => $date,
                    'sale_time' => $time,
                    'product_name' => $product->product_name,
                    'unit' => '0',
                    'voided' => '0',
                    'options_mode' => $product->options_mode,
                    'category_group' => (string) $product->category_group,
                    'shift' => $data['shift'] ?? '',
                    'unit_factor' => $product->unit_factor,
                    'login' => $user->username,
                ]);

                $consumption->download($product->id, (float) $data['quantity'][$i]);
            }

            $card->update(['prepaid_balance' => $data['newBalance']]);
        });

        return PosResponse::ok();
    }
}
