<?php

namespace App\Admin\Reportes;

/**
 * Reportes disponibles en el panel, en el orden en que se listan.
 */
class Registro
{
    public const REPORTES = [
        'ventas' => Ventas::class,
        'ventas-diarias' => VentasDiarias::class,
        'productos' => ProductosVendidos::class,
        'grupos' => GruposReporte::class,
        'meseros' => VentasPorMesero::class,
        'clientes' => MejoresClientes::class,
        'caja' => ArqueoDeCaja::class,
        'movimientos-caja' => IngresosEgresos::class,
        'anulaciones' => Anulaciones::class,
        'libro-ventas' => LibroVentas::class,
        'libro-compras' => LibroCompras::class,
    ];

    public static function obtener(string $slug): Reporte
    {
        abort_unless(isset(self::REPORTES[$slug]), 404);

        return app(self::REPORTES[$slug]);
    }

    /** @return array<string, array<string, Reporte>> agrupados por grupo() */
    public static function agrupados(): array
    {
        $grupos = [];
        foreach (self::REPORTES as $slug => $clase) {
            $reporte = app($clase);
            $grupos[$reporte->grupo()][$slug] = $reporte;
        }

        return $grupos;
    }
}
