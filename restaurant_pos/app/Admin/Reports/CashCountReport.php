<?php

namespace App\Admin\Reports;

use App\Models\Expense;
use App\Services\CashRegister\CashCount;

class CashCountReport extends Report
{
    public function __construct(private CashCount $cash_count) {}

    public function title(): string
    {
        return 'Arqueo de caja';
    }

    public function description(): string
    {
        return 'Por día y cajero: lo facturado, desglose por forma de pago, anulaciones, gastos, ingresos y el efectivo que debe haber en caja. Mismo cálculo que el cierre del POS.';
    }

    public function category_group(): string
    {
        return 'Caja';
    }

    public function icon(): string
    {
        return 'fa-money';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'cajero', 'shift'];
    }

    public function columns(): array
    {
        return [
            'date' => ['Fecha', 'date'],
            'cajero' => ['Cajero', 'text'],
            'invoiced' => ['Facturado', 'currency'],
            'cash' => ['Contado', 'currency'],
            'credit' => ['Crédito', 'currency'],
            'consumption' => ['Consumo', 'currency'],
            'qr' => ['QR', 'currency'],
            'voided' => ['Anulado', 'currency'],
            'expenses' => ['Gastos', 'currency'],
            'login' => ['Ingresos', 'currency'],
            'balance' => ['Saldo en caja', 'currency'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        $shifts = $this->sales($f)->select('sale_date as date', 'login')
            ->union(Expense::query()
                ->whereBetween('expense_date', $f->range())
                ->when($f->branchId, fn ($q) => $q->where('branch_id', $f->branchId))
                ->when($f->cashier, fn ($q) => $q->where('login', $f->cashier))
                ->when($f->shift, fn ($q) => $q->where('shift', $f->shift))
                ->select('expense_date as date', 'login'))
            ->toBase()
            ->orderBy('date')->orderBy('login')
            ->get();

        return $shifts->map(function ($t) use ($f) {
            $date = substr((string) $t->date, 0, 10);

            return ['date' => $date, 'cajero' => $t->login]
                + $this->cash_count->totals($date, $date, $f->branchId, $f->shift, $t->login);
        });
    }
}
