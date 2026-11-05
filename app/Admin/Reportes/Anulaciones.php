<?php

namespace App\Admin\Reportes;

use App\Models\MesaPedido;

class Anulaciones extends Reporte
{
    public function titulo(): string
    {
        return 'Ítems eliminados y mesas anuladas';
    }

    public function descripcion(): string
    {
        return 'Control de lo que se quitó de las mesas antes de cobrar: ítems eliminados y mesas anuladas completas.';
    }

    public function grupo(): string
    {
        return 'Caja';
    }

    public function icono(): string
    {
        return 'fa-ban';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'tipo'];
    }

    public function tipos(): array
    {
        return ['ITEM' => 'Ítems eliminados', 'MESA' => 'Mesas anuladas'];
    }

    public function columnas(): array
    {
        return [
            'fecha' => ['Fecha', 'fecha'],
            'hora' => ['Hora', 'texto'],
            'tipo' => ['Tipo', 'texto'],
            'mesa' => ['Mesa', 'texto'],
            'producto' => ['Producto', 'texto'],
            'cantidad' => ['Cantidad', 'numero'],
            'importe' => ['Importe (Bs)', 'moneda'],
            'mesero' => ['Mesero', 'texto'],
            'cajero' => ['Usuario', 'texto'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        return MesaPedido::query()
            ->with('mesa')
            ->whereBetween('f_venta', $f->rango())
            ->when($f->sucursalId, fn ($q) => $q->where('sucursal_id', $f->sucursalId))
            ->where(fn ($q) => $q
                ->when($f->tipo !== 'MESA', fn ($q) => $q->orWhereRaw("coalesce(item_eli, '') <> ''"))
                ->when($f->tipo !== 'ITEM', fn ($q) => $q->orWhere('anulado', 'S')))
            ->orderBy('f_venta')->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'fecha' => $p->f_venta,
                'hora' => $p->dhoraven,
                'tipo' => $p->anulado === 'S' ? 'Mesa anulada' : 'Ítem eliminado',
                'mesa' => $p->mesa?->nro_mesa,
                'producto' => $p->nom_prod,
                'cantidad' => (float) $p->cantidad,
                'importe' => (float) $p->cantidad * (float) $p->pre_venta,
                'mesero' => $p->nom_empl,
                'cajero' => $p->login,
            ]);
    }
}
