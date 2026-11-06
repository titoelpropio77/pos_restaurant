<?php

namespace App\Admin\Reportes;

use App\Models\Gasto;
use App\Services\Caja\ArqueoCaja;

class ArqueoDeCaja extends Reporte
{
    public function __construct(private ArqueoCaja $arqueo) {}

    public function titulo(): string
    {
        return 'Arqueo de caja';
    }

    public function descripcion(): string
    {
        return 'Por día y cajero: lo facturado, desglose por forma de pago, anulaciones, gastos, ingresos y el efectivo que debe haber en caja. Mismo cálculo que el cierre del POS.';
    }

    public function grupo(): string
    {
        return 'Caja';
    }

    public function icono(): string
    {
        return 'fa-money';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'cajero', 'turno'];
    }

    public function columnas(): array
    {
        return [
            'fecha' => ['Fecha', 'fecha'],
            'cajero' => ['Cajero', 'texto'],
            'facturado' => ['Facturado', 'moneda'],
            'contado' => ['Contado', 'moneda'],
            'credito' => ['Crédito', 'moneda'],
            'consumo' => ['Consumo', 'moneda'],
            'anulado' => ['Anulado', 'moneda'],
            'gastos' => ['Gastos', 'moneda'],
            'ingreso' => ['Ingresos', 'moneda'],
            'saldo' => ['Saldo en caja', 'moneda'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        $turnos = $this->ventas($f)->select('f_venta as fecha', 'login')
            ->union(Gasto::query()
                ->whereBetween('fechagasto', $f->rango())
                ->when($f->sucursalId, fn ($q) => $q->where('sucursal_id', $f->sucursalId))
                ->when($f->cajero, fn ($q) => $q->where('login', $f->cajero))
                ->when($f->turno, fn ($q) => $q->where('turno', $f->turno))
                ->select('fechagasto as fecha', 'login'))
            ->toBase()
            ->orderBy('fecha')->orderBy('login')
            ->get();

        return $turnos->map(function ($t) use ($f) {
            $fecha = substr((string) $t->fecha, 0, 10);

            return ['fecha' => $fecha, 'cajero' => $t->login]
                + $this->arqueo->totales($fecha, $fecha, $f->sucursalId, $f->turno, $t->login);
        });
    }
}
