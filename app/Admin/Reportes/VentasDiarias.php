<?php

namespace App\Admin\Reportes;

class VentasDiarias extends Reporte
{
    public function titulo(): string
    {
        return 'Ventas por día';
    }

    public function descripcion(): string
    {
        return 'Resumen diario: cantidad de ventas, montos por forma de pago, anulaciones y ticket promedio.';
    }

    public function grupo(): string
    {
        return 'Ventas';
    }

    public function icono(): string
    {
        return 'fa-calendar';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'cajero', 'turno'];
    }

    public function columnas(): array
    {
        return [
            'fecha' => ['Fecha', 'fecha'],
            'ventas' => ['Nro. ventas', 'numero'],
            'contado' => ['Contado', 'moneda'],
            'credito' => ['Crédito', 'moneda'],
            'consumo' => ['Consumo interno', 'moneda'],
            'otros' => ['Otros', 'moneda'],
            'anulado' => ['Anulado', 'moneda'],
            'total' => ['Total neto', 'moneda'],
            'promedio' => ['Ticket promedio', 'moneda'],
        ];
    }

    public function sinTotal(): array
    {
        return ['promedio'];
    }

    public function filas(Filtros $f): iterable
    {
        $activa = "estado = 'ACTIVO'";

        return $this->ventas($f)->groupBy('f_venta')->orderBy('f_venta')
            ->select('f_venta')
            ->selectRaw("count(*) filter (where $activa) as ventas")
            ->selectRaw("coalesce(sum(total_fact) filter (where $activa and formapago = 'CONTADO'), 0) as contado")
            ->selectRaw("coalesce(sum(total_fact) filter (where $activa and formapago = 'CREDITO'), 0) as credito")
            ->selectRaw("coalesce(sum(total_fact) filter (where $activa and formapago = 'CONSUMO'), 0) as consumo")
            ->selectRaw("coalesce(sum(total_fact) filter (where $activa and formapago not in ('CONTADO', 'CREDITO', 'CONSUMO')), 0) as otros")
            ->selectRaw("coalesce(sum(total_fact) filter (where estado <> 'ACTIVO'), 0) as anulado")
            ->selectRaw("coalesce(sum(total_fact) filter (where $activa), 0) as total")
            ->get()
            ->map(fn ($d) => [
                'fecha' => $d->f_venta,
                'ventas' => (int) $d->ventas,
                'contado' => (float) $d->contado,
                'credito' => (float) $d->credito,
                'consumo' => (float) $d->consumo,
                'otros' => (float) $d->otros,
                'anulado' => (float) $d->anulado,
                'total' => (float) $d->total,
                'promedio' => $d->ventas ? $d->total / $d->ventas : 0.0,
            ]);
    }

    public function totales(array $filas): array
    {
        $totales = parent::totales($filas);
        $totales['promedio'] = $totales['ventas'] ? $totales['total'] / $totales['ventas'] : 0;

        return $totales;
    }
}
