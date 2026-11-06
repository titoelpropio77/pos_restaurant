<?php

namespace App\Services\Sales;

use App\Exceptions\PosException;
use App\Models\BillingAuthorization;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Billing\ControlCode;
use App\Services\StockMovement\SupplyConsumption;
use App\Support\PosContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registra una venta del POS: cliente, numeración, código de control SIN, detalle
 * y descargo de insumos. Port de facturarController.php (proceso "guardar").
 */
class RegisterSale
{
    public const CONSUMPTION_CARD = 'Consumo Adicional de Tarjeta';

    public function __construct(
        private InvoiceNumbering $numbering,
        private ControlCode $controlCode,
        private PosContext $pos,
    ) {}

    /**
     * @param  array  $datos  Datos validados del formulario de facturación
     * @return array{venta: Venta, orden: int, factura: int}
     */
    public function run(array $data, User $user): array
    {
        $branchId = $this->pos->branchId();
        $date = Carbon::parse($data['saleDate'])->toDateString();
        $time = now()->format('G:i');
        $tax_id = trim((string) ($data['customerTaxId'] ?? ''));
        $totalBs = (float) $data['totalBs'];

        $authorization = BillingAuthorization::currentFor($branchId);
        if (! $authorization || $authorization->daysUntilExpiry($date) < 0) {
            throw new PosException('SU DOSIFICACIÓN YA SE VENCIÓ, NO PODRÁ VENDER');
        }

        return DB::transaction(function () use ($data, $user, $branchId, $date, $time, $tax_id, $totalBs, $authorization) {
            $customer = $this->customer(
                $data['nameCustomer'] ?? 'SIN NOMBRE',
                $tax_id === '' ? '0' : $tax_id,
                $data['phoneCustomer'] ?? '',
                $data['addressCustomer'] ?? '',
            );

            $number = $this->numbering->next($branchId, $tax_id, $data['paymentMethod']);

            $controlCode = $this->controlCode->generate(
                (string) $authorization->authorization_number,
                (string) $number['invoice_printed'],
                $tax_id === '' ? '0' : $tax_id,
                str_replace('-', '', $date),
                (string) $totalBs,
                (string) $authorization->auth_key,
            );

            $authorization->update(['current_invoice' => $number['folio'], 'current_real_invoice' => $number['invoice_printed']]);

            $prepaid = (string) ($data['selectPrepaid'] ?? '');

            $sale = Sale::create([
                'branch_id' => $branchId,
                'order_number' => $number['order_number'],
                'sale_date' => $date,
                'login' => $user->username,
                'payment_method' => $data['paymentMethod'],
                'status' => 'ACTIVE',
                'service_type' => $data['exitProduct'] ?? '',
                'shift' => $data['shift'] ?? '',
                'customer_id' => $customer->id,
                'seller_id' => (int) ($data['seller_id'] ?? 0),
                'sale_time' => $time,
                'exchange_rate' => $this->pos->exchangeRate(),
                'invoice_total' => $totalBs,
                'debt_total' => 0,
                'payable' => '0',
                'pending' => 'P',
                'invoice_number' => $number['folio'],
                'real_invoice_number' => $number['real_folio'],
                'prepaid_ref' => $prepaid === '' ? '' : 'P'.$prepaid,
                'paid_usd' => (float) ($data['totalUsd'] ?? 0),
                'change_usd' => (float) ($data['changeUsd'] ?? 0),
                'paid_bob' => (float) ($data['totalInvoiceBs'] ?? 0),
                'change_bob' => (float) ($data['changeBs'] ?? 0),
                'staff_code' => '0',
                'customer_tax_id' => $tax_id === '' ? '0' : $tax_id,
                'customer_id_number' => '0',
                'customer_name' => $data['nameCustomer'] ?? 'SIN NOMBRE',
                'auth_key' => $authorization->auth_key,
                'authorization_number' => $authorization->authorization_number,
                'limit_date' => $authorization->limit_date,
                'control_code' => $controlCode,
                'prepaid_balance' => $prepaid === '0' || $prepaid === '' ? null : $totalBs,
                'orders_ref' => '0',
                'delivered' => '0',
            ]);
            $sale->update(['invoice_ref' => $sale->id]);

            $consumption = new SupplyConsumption($this->pos->warehouseId(), $user->id, $sale->id, $date, $time);

            foreach ($data['idProduct'] as $i => $productId) {
                $sale->details()->create(array_merge(
                    $this->dataProduct($data, $i, (int) $productId),
                    [
                        'order_number' => $number['order_number'],
                        'item' => $i + 1,
                        'product_code' => (int) $productId,
                        'quantity' => (float) $data['quantity'][$i],
                        'cost_price' => 0,
                        'exchange_rate' => $this->pos->exchangeRate(),
                        'sale_date' => $date,
                        'sale_time' => $time,
                        'unit' => 'duni',
                        'shift' => $data['shift'] ?? '',
                        'family' => $data['paymentMethod'],
                        'service_type' => $data['exitProduct'] ?? '',
                        'invoice_number' => $number['folio'],
                        'real_invoice_number' => $number['real_folio'],
                        'factor' => 0,
                        'subtotal' => $data['subTotal'][$i] ?? 0,
                        'qr_code' => $data['qr'][$i] ?? '',
                        'login' => $user->username,
                        'orders_ref' => '0',
                        'note' => $data['note'][$i] ?? '',
                        'index_number' => 2,
                        'is_consumption' => 's',
                    ],
                ));

                $consumption->download((int) $productId, (float) $data['quantity'][$i]);
            }

            $this->pos->setDailyOrder($number['order_number']);

            return ['sale' => $sale, 'order_number' => $number['order_number'], 'invoice_ref' => $number['invoice_printed']];
        });
    }

    /**
     * Datos del producto que se copian al detalle. Contempla los ítems especiales
     * "Consumo Adicional de Tarjeta" y la "Atención" de billar, que no existen como producto.
     */
    private function dataProduct(array $data, int $i, int $productId): array
    {
        $name = $data['nameProduct'][$i] ?? '';
        $price = (float) ($data['price'][$i] ?? 0);

        if ($i === 0 && $name === self::CONSUMPTION_CARD) {
            return ['product_name' => self::CONSUMPTION_CARD, 'sale_price' => $price, 'category_group' => '0', 'options_mode' => '0', 'unit_factor' => 0, 'barcode' => '0', 'group_code' => '0'];
        }

        if (($data['billiards'] ?? '') === 'billiards' && $productId === config('pos.product_service_billiards')) {
            return ['product_name' => 'Atencion', 'sale_price' => $price, 'group_field' => (string) $price, 'category_group' => '0', 'options_mode' => '0', 'unit_factor' => 0, 'barcode' => '0', 'group_code' => '0'];
        }

        $product = Product::forPos($this->pos->branchId())->where('products.id', $productId)->first();
        if (! $product) {
            throw new PosException("EL PRODUCTO {$productId} NO ESTÁ HABILITADO EN ESTA SUCURSAL");
        }

        return [
            'product_name' => $product->product_name,
            'sale_price' => $product->sale_price,
            'category_group' => (string) $product->category_group,
            'options_mode' => $product->options_mode,
            'unit_factor' => $product->unit_factor,
            'barcode' => $product->barcode,
            'group_code' => $product->group_code,
        ];
    }

    /**
     * Busca el cliente por nombre y NIT; si no existe lo crea. Si viene teléfono y dirección
     * (envío a domicilio) los actualiza. Legacy: verificarCliente().
     */
    private function customer(string $name, string $tax_id, string $phone, string $address): Customer
    {
        $customer = Customer::where('customer_name', $name)->where('customer_tax_id', $tax_id)->first();

        if (! $customer) {
            return Customer::create(['customer_code' => '0', 'customer_name' => $name, 'customer_tax_id' => $tax_id, 'customer_phone' => $phone, 'customer_address' => $address]);
        }

        if ($phone !== '' && $address !== '') {
            $customer->update(['customer_phone' => $phone, 'customer_address' => $address]);
        }

        return $customer;
    }
}
