<?php

namespace App\Services\Sales;

use App\Exceptions\PosException;
use App\Models\InvoiceSequence;

/**
 * Asigna el número de factura / recibo y la orden del día a una venta.
 *
 * Port de la lógica de correlativos de facturarController.php:
 *  - folio:  número de factura fiscal.
 *  - folior: número "real" (recibo). Una venta sin NIT y que no es a crédito sólo advances
 *            el folior; cada `mult` recibos se consume un folio (cuando mult > 0).
 *  - imprimir: si está definido, toda venta advances el folio y no se usa folior.
 *
 * Debe ejecutarse dentro de una transacción: bloquea la fila del correlativo.
 */
class InvoiceNumbering
{
    /**
     * @return array{folio:int, folior:int, orden:int, factura_impresa:int}
     */
    public function next(int $branchId, string $tax_id, string $paymentMethod): array
    {
        $sequence = InvoiceSequence::where('table_name', config('pos.sales_sequence'))
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            throw new PosException('NO EXISTE CORRELATIVO DE VENTAS PARA ESTA SUCURSAL');
        }

        $folio = $sequence->folio;
        $real_folio = $sequence->real_folio;
        $order_number = $sequence->daily_order + 1;
        $multiple = (int) $sequence->receipt_multiplier;

        if (! $sequence->print_flag) {
            if ($this->isReceipt($tax_id, $paymentMethod)) {
                $real_folio++;
                if ($multiple > 0) {
                    $counter = (int) $sequence->receipt_counter + 1;
                    $sequence->receipt_counter = $counter;
                    if ($counter === $multiple) {
                        $folio++;
                        $real_folio = $folio;
                        $sequence->receipt_counter = 0;
                        $sequence->folio = $folio;
                    }
                }
                $sequence->real_folio = $real_folio;
            } else {
                $folio++;
                $real_folio = $folio;
                $sequence->folio = $folio;
                $sequence->real_folio = $folio;
            }
            $invoicePrinted = $real_folio;
        } else {
            $folio++;
            $real_folio = 0;
            $sequence->folio = $folio;
            $invoicePrinted = $folio;
        }

        $sequence->daily_order = $order_number;
        $sequence->save();

        return [
            'folio' => $folio,
            'real_folio' => $real_folio,
            'order_number' => $order_number,
            'invoice_printed' => $invoicePrinted,
        ];
    }

    /**
     * En el sistema original una venta sin NIT (y que no es a crédito) se registra como recibo.
     */
    private function isReceipt(string $tax_id, string $paymentMethod): bool
    {
        return trim($tax_id) === '' && $paymentMethod !== 'CREDITO';
    }
}
