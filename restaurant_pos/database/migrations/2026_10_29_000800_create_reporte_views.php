<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vista de cantidades vendidas por grupo de reporte (legacy: vistarelprogrudet).
 *
 * La vista MySQL original agrupaba sólo por NOM_GRUPO y F_VENTA devolviendo columnas
 * no agregadas con valores arbitrarios; en PostgreSQL se agrupa también por login y turno
 * para que los filtros por cajero/turno de los reportes sean correctos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW vista_grupo_producto_ventas AS
            SELECT SUM(d.cantidad * d.unid * gp.factor) AS cantidad,
                   g.nom_grupo, g.cod_grupo, g.id AS grupo_id,
                   MAX(gp.cangrupo) AS cangrupo, MAX(gp.orden) AS orden,
                   MAX(gp.unidad) AS unidad, MAX(gp.factor) AS factor,
                   d.f_venta, d.login, d.turno, v.sucursal_id
            FROM grupo_producto gp
            JOIN grupos g ON g.id = gp.grupo_id
            JOIN venta_detalles d ON d.cod_prod = gp.producto_id
            JOIN ventas v ON v.id = d.venta_id
            WHERE COALESCE(d.anulado, '') <> 'S'
            GROUP BY g.id, g.nom_grupo, g.cod_grupo, d.f_venta, d.login, d.turno, v.sucursal_id
            UNION ALL
            SELECT SUM(t.cantidad * t.unid * gp.factor) AS cantidad,
                   g.nom_grupo, g.cod_grupo, g.id AS grupo_id,
                   MAX(gp.cangrupo), MAX(gp.orden), MAX(gp.unidad), MAX(gp.factor),
                   t.f_venta, t.login, t.turno, t.sucursal_id
            FROM grupo_producto gp
            JOIN grupos g ON g.id = gp.grupo_id
            JOIN tarjeta_detalles t ON t.cod_prod = gp.producto_id
            GROUP BY g.id, g.nom_grupo, g.cod_grupo, t.f_venta, t.login, t.turno, t.sucursal_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vista_grupo_producto_ventas');
    }
};
