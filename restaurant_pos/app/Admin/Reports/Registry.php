<?php

namespace App\Admin\Reports;

/**
 * Reportes disponibles en el panel, en el orden en que se listan.
 */
class Registry
{
    public const REPORTS = [
        'sales' => Sales::class,
        'sales-daily' => DailySales::class,
        'products' => ProductsSold::class,
        'report_groups' => ReportGroups::class,
        'waiters' => SalesByWaiter::class,
        'customers' => TopCustomers::class,
        'cash_register' => CashCountReport::class,
        'movements-cash_register' => IncomeExpenses::class,
        'voids' => VoidedSales::class,
        'book-sales' => BookSales::class,
        'book-purchases' => BookPurchases::class,
    ];

    public static function get(string $slug): Report
    {
        abort_unless(isset(self::REPORTS[$slug]), 404);

        return app(self::REPORTS[$slug]);
    }

    /** @return array<string, array<string, Reporte>> agrupados por grupo() */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::REPORTS as $slug => $class) {
            $report = app($class);
            $groups[$report->category_group()][$slug] = $report;
        }

        return $groups;
    }
}
