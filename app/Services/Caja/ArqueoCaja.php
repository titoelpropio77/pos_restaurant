<?php

namespace App\Services\Caja;

use App\Models\Gasto;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;

/**
 * Totales del arqueo de caja: lo facturado, desglosado por forma de pago, menos anulado y
 * gastos, más ingresos. Lo usan el cierre del POS y el reporte de caja del panel.
 */
class ArqueoCaja
{
    /**
     * @param  string|null  $turno  MAÑANA, NOCHE o null para ambos
     * @return array{facturado: float, credito: float, consumo: float, anulado: float, contado: float, gastos: float, ingreso: float, saldo: float}
     */
    public function totales(string $desde, string $hasta, ?int $sucursalId = null, ?string $turno = null, ?string $cajero = null): array
    {
        $filtrar = fn (Builder $q, string $fecha) => $q->whereBetween($fecha, [$desde, $hasta])
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->when($turno, fn ($q) => $q->where('turno', $turno))
            ->when($cajero, fn ($q) => $q->where('login', $cajero));

        $ventas = fn () => $filtrar(Venta::query(), 'f_venta');
        $activas = fn (string $formaPago) => (float) $ventas()->where('estado', 'ACTIVO')->where('formapago', $formaPago)->sum('total_fact');
        $movimientos = fn (int $tipo) => (float) $filtrar(Gasto::query(), 'fechagasto')
            ->where('estado', 'ACTIVO')->where('saliocaja', 1)->where('tipotransaccion', $tipo)->sum('monto');

        $totales = [
            'facturado' => (float) $ventas()->sum('total_fact'),
            'credito' => $activas('CREDITO'),
            'consumo' => $activas('CONSUMO'),
            'anulado' => (float) $ventas()->where('estado', 'INACTIVO')->sum('total_fact'),
            'contado' => $activas('CONTADO'),
            'gastos' => $movimientos(0),
            'ingreso' => $movimientos(1),
        ];

        $totales['saldo'] = $totales['facturado'] - $totales['credito'] - $totales['consumo'] - $totales['anulado'] - $totales['gastos'] + $totales['ingreso'];

        return $totales;
    }
}
