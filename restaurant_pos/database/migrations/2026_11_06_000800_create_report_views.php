<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vista de cantidades vendidas por grupo de reporte.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW report_group_sales_view AS
            SELECT SUM(d.quantity * d.unit_factor * gp.factor) AS quantity,
                   g.group_name, g.group_code, g.id AS report_group_id,
                   MAX(gp.order_number) AS order_number,
                   MAX(gp.unit) AS unit, MAX(gp.factor) AS factor,
                   d.sale_date, d.login, d.shift, v.branch_id, g.restaurant_id
            FROM product_report_group gp
            JOIN report_groups g ON g.id = gp.report_group_id
            JOIN sale_items d ON d.product_code = gp.product_id
            JOIN sales v ON v.id = d.sale_id
            WHERE COALESCE(d.voided, '') <> 'S'
            GROUP BY g.id, g.group_name, g.group_code, d.sale_date, d.login, d.shift, v.branch_id, g.restaurant_id
            UNION ALL
            SELECT SUM(t.quantity * t.unit_factor * gp.factor) AS quantity,
                   g.group_name, g.group_code, g.id AS report_group_id,
                   MAX(gp.order_number), MAX(gp.unit), MAX(gp.factor),
                   t.sale_date, t.login, t.shift, t.branch_id, g.restaurant_id
            FROM product_report_group gp
            JOIN report_groups g ON g.id = gp.report_group_id
            JOIN prepaid_card_items t ON t.product_code = gp.product_id
            GROUP BY g.id, g.group_name, g.group_code, t.sale_date, t.login, t.shift, t.branch_id, g.restaurant_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS report_group_sales_view');
    }
};
