<?php

namespace App\Services\Printing;

use App\Http\Controllers\Admin\ProductController;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\DiningTable;
use App\Models\Sale;
use App\Models\TableOrder;
use App\Services\Tables\TableOrders;
use App\Support\AmountInWords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos de los tickets que el POS imprime en la ticketera USB de la caja desde el navegador.
 *
 * Comandas: una por estación (Cocina, Punto 1...) con los productos que la sucursal envía a esa estación;
 * los productos sin estación no se imprimen. Precuenta: el detalle con precios de una mesa abierta.
 */
class OrderTickets
{
    public function __construct(private TableOrders $tables) {}

    /**
     * Comanda de un pedido de mesa (los productos de una misma "comanda" enviada).
     *
     * @return array{header: array, stations: array<string, Collection>}
     */
    public function tableOrder(DiningTable $table, int $ticket, string $date): array
    {
        $items = TableOrder::where('dining_table_id', $table->id)
            ->where('order_number', $ticket)
            ->where('sale_date', $date)
            ->whereRaw("coalesce(item_deleted, '') = ''")
            ->orderBy('item')
            ->get();

        // Si la mesa ya tenía pedidos abiertos antes de esta comanda, es un adicional
        $additional = TableOrder::where('dining_table_id', $table->id)
            ->where('sale_date', $date)
            ->where('order_number', '<', $ticket)
            ->pending()
            ->exists();

        return [
            'header' => [
                'title' => 'MESA '.$table->table_number,
                'subtitle' => $table->customer_name ?: null,
                'additional' => $additional,
                'number' => 'Comanda N° '.$ticket,
                'waiter' => $items->first()?->staff_name,
                'user' => $items->first()?->login,
                'time' => $items->first()?->sale_time,
                'date' => $date,
            ],
            'stations' => $this->byStation($table->branch_id, $items->map(fn (TableOrder $i) => [
                'product_id' => (int) $i->product_code,
                'quantity' => (float) $i->quantity,
                'name' => $i->product_name,
                'note' => $i->note,
            ])),
        ];
    }

    /**
     * Comanda de una venta directa (para llevar, delivery...).
     *
     * @return array{header: array, stations: array<string, Collection>}
     */
    public function saleOrder(Sale $sale): array
    {
        $service = trim((string) $sale->service_type);

        return [
            'header' => [
                'title' => 'ORDEN N° '.$sale->order_number,
                'subtitle' => $service !== '' ? mb_strtoupper($service) : null,
                'additional' => false,
                'number' => $sale->customer_name && $sale->customer_name !== 'SIN NOMBRE' ? 'Cliente: '.$sale->customer_name : null,
                'waiter' => null,
                'user' => $sale->login,
                'time' => $sale->sale_time,
                'date' => $sale->sale_date->toDateString(),
            ],
            'stations' => $this->byStation($sale->branch_id, $sale->details()->orderBy('item')->get()->map(fn ($d) => [
                'product_id' => (int) $d->product_code,
                'quantity' => (float) $d->quantity,
                'name' => $d->product_name,
                'note' => $d->note,
            ])),
        ];
    }

    /**
     * Precuenta de una mesa: todos los productos abiertos con su precio.
     */
    public function preBill(DiningTable $table, string $date): array
    {
        $items = $this->tables->detail($table, $date)->map(fn (TableOrder $i) => [
            'quantity' => (float) $i->quantity,
            'name' => $i->product_name,
            'note' => $i->note,
            'price' => (float) $i->sale_price,
            'subtotal' => round((float) $i->sale_price * (float) $i->quantity, 2),
        ]);

        return [
            'business' => $this->business($table->branch),
            'table' => 'MESA '.$table->table_number,
            'customer' => $table->customer_name ?: null,
            'people' => $table->people_count ?: null,
            'waiter' => $this->tables->detail($table, $date)->first()?->staff_name,
            'date' => $date,
            'items' => $items,
            'total' => round($items->sum('subtotal'), 2),
        ];
    }

    /**
     * Nota de venta o, si la venta se facturó (tiene código de control), factura con sus datos fiscales y QR.
     */
    public function receipt(Sale $sale): array
    {
        $business = $this->business($sale->branch);
        $total = (float) $sale->invoice_total;
        $invoice = filled($sale->control_code);
        $number = (int) $sale->real_invoice_number ?: (int) $sale->invoice_number;
        $customerTaxId = in_array((string) $sale->customer_tax_id, ['', '0'], true) ? null : (string) $sale->customer_tax_id;

        $fiscal = null;
        if ($invoice) {
            $date = $sale->sale_date->format('d/m/Y');
            // Contenido del QR de la factura (SIN, código de control v7)
            $qr = implode('|', [
                $business['tax_id'] ?? '0', $number, $sale->authorization_number, $date, number_format($total, 2, '.', ''),
                number_format($total, 2, '.', ''), $sale->control_code, $customerTaxId ?? '0', '0', '0', '0', '0',
            ]);
            $fiscal = [
                'number' => $number,
                'authorization' => $sale->authorization_number,
                'control_code' => $sale->control_code,
                'limit_date' => $sale->limit_date ? Carbon::parse($sale->limit_date)->format('d/m/Y') : null,
                'qr' => QrSvg::make($qr),
            ];
        }

        return [
            'business' => $business,
            'title' => $invoice ? 'FACTURA' : 'NOTA DE VENTA',
            'number' => $number,
            'order' => $sale->order_number,
            'service' => trim((string) $sale->service_type) ?: null,
            'date' => $sale->sale_date->format('d/m/Y'),
            'time' => $sale->sale_time,
            'user' => $sale->login,
            'customer' => $sale->customer_name && $sale->customer_name !== 'SIN NOMBRE' ? $sale->customer_name : null,
            'customer_tax_id' => $customerTaxId,
            'items' => $sale->details()->orderBy('item')->get()->map(fn ($d) => [
                'quantity' => (float) $d->quantity,
                'name' => $d->product_name,
                'note' => $d->note,
                'price' => (float) $d->sale_price,
                'subtotal' => round((float) $d->sale_price * (float) $d->quantity, 2),
            ]),
            'total' => $total,
            'words' => AmountInWords::bolivianos($total),
            'payment' => $sale->payment_method,
            'paid' => (float) $sale->paid_bob,
            'change' => (float) $sale->change_bob,
            'voided' => $sale->status !== 'ACTIVE',
            'fiscal' => $fiscal,
        ];
    }

    /** Encabezado del negocio: datos fiscales de la dosificación o, sin ella, los del restaurante y la sucursal. */
    public function business(Branch $branch): array
    {
        $authorization = BillingAuthorization::currentFor($branch->id);

        return [
            'name' => $authorization?->business_name ?: $branch->restaurant?->name,
            'owner' => $authorization?->owner_name,
            'tax_id' => $authorization?->tax_id ?: $branch->restaurant?->tax_id,
            'branch' => $branch->name,
            'address' => $authorization?->business_address ?: $branch->address,
            'phone' => $authorization?->business_phone ?: $branch->phone,
            'city' => $authorization?->city_country,
            'legends' => array_values(array_filter([$authorization?->legend_1, $authorization?->legend_2])),
        ];
    }

    /**
     * Agrupa los productos por estación según la configuración de la sucursal (un producto puede ir a varias).
     *
     * @param  Collection<int, array{product_id: int, quantity: float, name: string, note: ?string}>  $items
     * @return array<string, Collection> nombre de la estación => productos
     */
    private function byStation(int $branchId, Collection $items): array
    {
        $stations = array_keys(ProductController::PRINTERS);
        $config = BranchProduct::where('branch_id', $branchId)
            ->whereIn('product_id', $items->pluck('product_id')->unique())
            ->get(['product_id', ...$stations])
            ->keyBy('product_id');

        $result = [];
        foreach (ProductController::PRINTERS as $column => $label) {
            $forStation = $items->filter(fn ($i) => $config->get($i['product_id'])?->{$column} === 'x')->values();
            if ($forStation->isNotEmpty()) {
                $result[mb_strtoupper($label)] = $forStation;
            }
        }

        return $result;
    }
}
