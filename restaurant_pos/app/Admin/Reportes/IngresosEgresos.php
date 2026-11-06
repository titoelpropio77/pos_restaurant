<?php

namespace App\Admin\Reportes;

use App\Models\Gasto;

class IngresosEgresos extends Reporte
{
    public function titulo(): string
    {
        return 'Ingresos y egresos de caja';
    }

    public function descripcion(): string
    {
        return 'Movimientos de dinero registrados en la pestaña Ingreso / Egreso del POS.';
    }

    public function grupo(): string
    {
        return 'Caja';
    }

    public function icono(): string
    {
        return 'fa-exchange';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'cajero', 'turno', 'tipo'];
    }

    public function tipos(): array
    {
        return ['EGRESO' => 'Egresos', 'INGRESO' => 'Ingresos'];
    }

    public function columnas(): array
    {
        return [
            'fecha' => ['Fecha', 'fecha'],
            'hora' => ['Hora', 'texto'],
            'tipo' => ['Tipo', 'texto'],
            'entregado' => ['Entregado a / recibido de', 'texto'],
            'concepto' => ['Concepto', 'texto'],
            'cajero' => ['Cajero', 'texto'],
            'caja' => ['¿Salió de caja?', 'texto'],
            'ingreso' => ['Ingreso (Bs)', 'moneda'],
            'egreso' => ['Egreso (Bs)', 'moneda'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        return Gasto::query()
            ->whereBetween('fechagasto', $f->rango())
            ->where('estado', 'ACTIVO')
            ->when($f->sucursalId, fn ($q) => $q->where('sucursal_id', $f->sucursalId))
            ->when($f->cajero, fn ($q) => $q->where('login', $f->cajero))
            ->when($f->turno, fn ($q) => $q->where('turno', $f->turno))
            ->when($f->tipo, fn ($q) => $q->where('tipotransaccion', $f->tipo === 'INGRESO' ? 1 : 0))
            ->orderBy('fechagasto')->orderBy('id')
            ->get()
            ->map(fn ($g) => [
                'fecha' => $g->fechagasto,
                'hora' => $g->horagasto,
                'tipo' => $g->tipotransaccion ? 'INGRESO' : 'EGRESO',
                'entregado' => $g->entregadoa,
                'concepto' => $g->concepto,
                'cajero' => $g->login,
                'caja' => $g->saliocaja ? 'Sí' : 'No',
                'ingreso' => $g->tipotransaccion ? (float) $g->monto : 0.0,
                'egreso' => $g->tipotransaccion ? 0.0 : (float) $g->monto,
            ]);
    }
}
