<?php

namespace App\Services\Inventario;

use App\Exceptions\ErrorPos;
use App\Models\InsumoAlmacen;
use App\Models\InsumoProducto;
use App\Models\Inventario;
use App\Models\KardexInsumo;
use App\Models\MotivoMovimiento;

/**
 * Descuenta del almacén los insumos de la receta de los productos vendidos y registra
 * el kardex (legacy: bloque de inventario en facturarController / deTajetaController).
 *
 * Se crea un único movimiento de inventario por documento, sólo si algún producto tiene receta.
 */
class DescargoInsumos
{
    private ?Inventario $movimiento = null;

    public function __construct(
        private ?int $almacenId,
        private int $usuarioId,
        private int $documentoId,
        private string $fecha,
        private string $hora,
    ) {}

    public function descargar(int $productoId, float $cantidadVendida): void
    {
        $receta = InsumoProducto::with('insumo')->where('producto_id', $productoId)->get();

        if ($receta->isEmpty()) {
            return;
        }

        if (! $this->almacenId) {
            throw new ErrorPos('EL PUNTO DE VENTA NO TIENE ALMACÉN POR DEFECTO');
        }

        $movimiento = $this->movimiento();

        foreach ($receta as $item) {
            $cantidad = $cantidadVendida * (float) $item->cantidad;

            $stock = InsumoAlmacen::lockForUpdate()->firstOrNew([
                'insumo_id' => $item->insumo_id,
                'almacen_id' => $this->almacenId,
            ]);
            $stock->stock_actual = (float) $stock->stock_actual - $cantidad;
            $stock->save();

            KardexInsumo::create([
                'inventario_id' => $movimiento->id,
                'venta_id' => $this->documentoId,
                'insumo_id' => $item->insumo_id,
                'almacen_id' => $this->almacenId,
                'nom_insumo' => $item->insumo->nom_insumo,
                'cantidad' => $cantidad,
                'fecha_mov' => $this->fecha,
                'entrada' => 0,
                'salida' => $cantidad,
                'saldo' => $stock->stock_actual,
                'movimiento' => 'VENTA',
                'unidad' => $item->insumo->medida,
            ]);
        }
    }

    private function movimiento(): Inventario
    {
        return $this->movimiento ??= Inventario::create([
            'fecha' => $this->fecha,
            'hora' => $this->hora,
            'importe' => 0,
            'tipo' => 'VENTA',
            'glosa' => 'MOVIMIENTO GENERADO POR VENTA '.$this->documentoId,
            'almacen_id' => $this->almacenId,
            'estado' => '0',
            'motivo_movimiento_id' => MotivoMovimiento::where('nombre', config('posgourmet.motivo_movimiento_venta'))->value('id'),
            'usuario_id' => $this->usuarioId,
        ]);
    }
}
