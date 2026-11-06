<?php

namespace App\Services\CashRegister;

use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

/**
 * Totales del arqueo de caja: lo facturado, desglosado por forma de pago, menos anulado y
 * gastos, más ingresos. Lo usan el cierre del POS y el reporte de caja del panel.
 */
class CashCount
{
    /**
     * @param  string|null  $turno  MAÑANA, NOCHE o null para ambos
     * @return array{facturado: float, credito: float, consumo: float, qr: float, anulado: float, contado: float, gastos: float, ingreso: float, saldo: float}
     */
    public function totals(string $from, string $to, ?int $branchId = null, ?string $shift = null, ?string $cashier = null): array
    {
        $filter = fn (Builder $q, string $date) => $q->whereBetween($date, [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($shift, fn ($q) => $q->where('shift', $shift))
            ->when($cashier, fn ($q) => $q->where('login', $cashier));

        $sales = fn () => $filter(Sale::query(), 'sale_date');
        $active = fn (string $paymentMethod) => (float) $sales()->where('status', 'ACTIVE')->where('payment_method', $paymentMethod)->sum('invoice_total');
        $movements = fn (int $type) => (float) $filter(Expense::query(), 'expense_date')
            ->where('status', 'ACTIVE')->where('from_cash_drawer', 1)->where('transaction_type', $type)->sum('amount');

        $totals = [
            'invoiced' => (float) $sales()->sum('invoice_total'),
            'credit' => $active('CREDITO'),
            'consumption' => $active('CONSUMO'),
            'qr' => $active('QR'),
            'voided' => (float) $sales()->where('status', 'INACTIVE')->sum('invoice_total'),
            'cash' => $active('CONTADO'),
            'expenses' => $movements(0),
            'login' => $movements(1),
        ];

        $totals['balance'] = $totals['invoiced'] - $totals['credit'] - $totals['consumption'] - $totals['qr'] - $totals['voided'] - $totals['expenses'] + $totals['login'];

        return $totals;
    }
}
