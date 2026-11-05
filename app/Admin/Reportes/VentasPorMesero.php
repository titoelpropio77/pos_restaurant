<?php

namespace App\Admin\Reportes;

use App\Models\Personal;

class VentasPorMesero extends Reporte
{
    public function titulo(): string
    {
        return 'Ventas por mesero';
    }

    public function descripcion(): string
    {
        return 'Ventas asignadas a cada mesero/vendedor al facturar, con su comisión según el porcentaje configurado.';
    }

    public function grupo(): string
    {
        return 'Ventas';
    }

    public function icono(): string
    {
        return 'fa-id-badge';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'turno'];
    }

    public function columnas(): array
    {
        return [
            'mesero' => ['Mesero / vendedor', 'texto'],
            'ventas' => ['Nro. ventas', 'numero'],
            'total' => ['Total vendido (Bs)', 'moneda'],
            'promedio' => ['Ticket promedio', 'moneda'],
            'porcentaje' => ['Comisión %', 'numero'],
            'comision' => ['Comisión (Bs)', 'moneda'],
        ];
    }

    public function sinTotal(): array
    {
        return ['promedio', 'porcentaje'];
    }

    public function filas(Filtros $f): iterable
    {
        $personal = Personal::all()->keyBy('id');

        return $this->ventas($f)->where('estado', 'ACTIVO')
            ->groupBy('vendedor')
            ->selectRaw('vendedor, count(*) as ventas, sum(total_fact) as total')
            ->orderByDesc('total')
            ->get()
            ->map(function ($v) use ($personal) {
                $p = $personal->get($v->vendedor);
                $porcentaje = (float) ($p?->porcentaje ?? 0);

                return [
                    'mesero' => $p?->nom_empl ?? 'Sin mesero asignado',
                    'ventas' => (int) $v->ventas,
                    'total' => (float) $v->total,
                    'promedio' => $v->total / max(1, $v->ventas),
                    'porcentaje' => $porcentaje,
                    'comision' => $v->total * $porcentaje / 100,
                ];
            });
    }
}
