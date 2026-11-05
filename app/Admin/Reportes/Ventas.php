<?php

namespace App\Admin\Reportes;

class Ventas extends Reporte
{
    public function titulo(): string
    {
        return 'Listado de ventas';
    }

    public function descripcion(): string
    {
        return 'Todas las ventas con su número de factura o recibo, cliente, cajero y forma de pago.';
    }

    public function grupo(): string
    {
        return 'Ventas';
    }

    public function icono(): string
    {
        return 'fa-file-text-o';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'cajero', 'formapago', 'turno', 'estado'];
    }

    public function columnas(): array
    {
        return [
            'fecha' => ['Fecha', 'fecha'],
            'hora' => ['Hora', 'texto'],
            'orden' => ['Orden', 'texto'],
            'documento' => ['Factura / recibo', 'texto'],
            'cliente' => ['Cliente', 'texto'],
            'nit' => ['NIT', 'texto'],
            'cajero' => ['Cajero', 'texto'],
            'turno' => ['Turno', 'texto'],
            'formapago' => ['Forma de pago', 'texto'],
            'estado' => ['Estado', 'texto'],
            'total' => ['Total (Bs)', 'moneda'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        return $this->ventas($f)->orderBy('f_venta')->orderBy('id')->get()->map(fn ($v) => [
            '_venta' => $v->id,
            'fecha' => $v->f_venta,
            'hora' => $v->dhoraven,
            'orden' => '#'.$v->orden,
            'documento' => $v->fortax > 0 && ($v->fortax === $v->tot_des || ! $v->tot_des) ? 'F-'.$v->fortax : 'R-'.$v->tot_des,
            'cliente' => $v->nom_cli,
            'nit' => $v->nit_cli,
            'cajero' => $v->login,
            'turno' => $v->turno,
            'formapago' => $v->formapago,
            'estado' => $v->estado === 'ACTIVO' ? 'ACTIVA' : 'ANULADA',
            // Las anuladas se listan pero no suman
            'total' => $v->estado === 'ACTIVO' ? (float) $v->total_fact : 0.0,
        ]);
    }
}
