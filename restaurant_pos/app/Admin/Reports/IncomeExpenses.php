<?php

namespace App\Admin\Reports;

use App\Models\Expense;

class IncomeExpenses extends Report
{
    public function title(): string
    {
        return 'Ingresos y egresos de caja';
    }

    public function description(): string
    {
        return 'Movimientos de dinero registrados en la pestaña Ingreso / Egreso del POS.';
    }

    public function category_group(): string
    {
        return 'Caja';
    }

    public function icon(): string
    {
        return 'fa-exchange';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'cajero', 'shift', 'type'];
    }

    public function types(): array
    {
        return ['EGRESO' => 'Egresos', 'INGRESO' => 'Ingresos'];
    }

    public function columns(): array
    {
        return [
            'date' => ['Fecha', 'date'],
            'time' => ['Hora', 'text'],
            'type' => ['Tipo', 'text'],
            'delivered' => ['Entregado a / recibido de', 'text'],
            'concept' => ['Concepto', 'text'],
            'cajero' => ['Cajero', 'text'],
            'cash_register' => ['¿Salió de caja?', 'text'],
            'login' => ['Ingreso (Bs)', 'currency'],
            'expense' => ['Egreso (Bs)', 'currency'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        return Expense::query()
            ->whereBetween('expense_date', $f->range())
            ->where('status', 'ACTIVE')
            ->when($f->branchId, fn ($q) => $q->where('branch_id', $f->branchId))
            ->when($f->cashier, fn ($q) => $q->where('login', $f->cashier))
            ->when($f->shift, fn ($q) => $q->where('shift', $f->shift))
            ->when($f->type, fn ($q) => $q->where('transaction_type', $f->type === 'INGRESO' ? 1 : 0))
            ->orderBy('expense_date')->orderBy('id')
            ->get()
            ->map(fn ($g) => [
                'date' => $g->expense_date,
                'time' => $g->expense_time,
                'type' => $g->transaction_type ? 'INGRESO' : 'EGRESO',
                'delivered' => $g->delivered_to,
                'concept' => $g->concept,
                'cajero' => $g->login,
                'cash_register' => $g->from_cash_drawer ? 'Sí' : 'No',
                'login' => $g->transaction_type ? (float) $g->amount : 0.0,
                'expense' => $g->transaction_type ? 0.0 : (float) $g->amount,
            ]);
    }
}
