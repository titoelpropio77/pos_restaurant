<?php

namespace App\Services\Inventario;

use App\Exceptions\ErrorPos;
use App\Models\Insumo;
use App\Models\InsumoAlmacen;
use App\Models\Inventario;
use App\Models\KardexInsumo;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos manuales de inventario con su kardex.
 *
 *  - COMPRA:   ingreso al almacén; recalcula el costo promedio ponderado (PPM) del insumo.
 *  - SALIDA:   merma, consumo interno o baja; no permite dejar stock negativo.
 *  - TRASPASO: del almacén origen al destino (dos registros de kardex).
 *  - AJUSTE:   la cantidad indicada es el stock contado; se registra la diferencia.
 */
class MovimientoInventario
{
    public const TIPOS = [
        'COMPRA' => 'Compra / ingreso',
        'SALIDA' => 'Salida / merma',
        'TRASPASO' => 'Traspaso entre almacenes',
        'AJUSTE' => 'Ajuste por conteo físico',
    ];

    /**
     * @param  array{tipo: string, fecha: string, almacen_id: int, almacen_destino_id?: int|null, motivo_movimiento_id?: int|null, glosa?: string|null, lineas: array<int, array{insumo_id: int, cantidad: float, costo?: float|null}>}  $datos
     */
    public function registrar(array $datos, Usuario $usuario): Inventario
    {
        return DB::transaction(function () use ($datos, $usuario) {
            $tipo = $datos['tipo'];

            $movimiento = Inventario::create([
                'fecha' => $datos['fecha'],
                'hora' => now()->format('H:i'),
                'importe' => 0,
                'tipo' => $tipo,
                'glosa' => $datos['glosa'] ?? null,
                'almacen_id' => $datos['almacen_id'],
                'almacen_destino_id' => $tipo === 'TRASPASO' ? $datos['almacen_destino_id'] : null,
                'estado' => 'ACTIVO',
                'motivo_movimiento_id' => $datos['motivo_movimiento_id'] ?? null,
                'usuario_id' => $usuario->id,
            ]);

            $importe = 0;
            foreach ($datos['lineas'] as $linea) {
                $insumo = Insumo::lockForUpdate()->findOrFail($linea['insumo_id']);
                $cantidad = (float) $linea['cantidad'];

                $importe += match ($tipo) {
                    'COMPRA' => $this->compra($movimiento, $insumo, $cantidad, (float) ($linea['costo'] ?? 0)),
                    'SALIDA' => $this->salida($movimiento, $insumo, $datos['almacen_id'], $cantidad, 'SALIDA'),
                    'TRASPASO' => $this->traspaso($movimiento, $insumo, $datos['almacen_id'], $datos['almacen_destino_id'], $cantidad),
                    'AJUSTE' => $this->ajuste($movimiento, $insumo, $cantidad),
                };
            }

            $movimiento->update(['importe' => round($importe, 4)]);

            return $movimiento;
        });
    }

    private function compra(Inventario $movimiento, Insumo $insumo, float $cantidad, float $costo): float
    {
        $existencia = (float) InsumoAlmacen::where('insumo_id', $insumo->id)->sum('stock_actual');
        $ppmAnterior = (float) ($insumo->ppm ?? $insumo->pre_costo ?? 0);
        $ppm = $existencia + $cantidad > 0 && $existencia > 0
            ? (($existencia * $ppmAnterior) + ($cantidad * $costo)) / ($existencia + $cantidad)
            : $costo;

        $insumo->update(['ppm' => round($ppm, 4), 'pre_costo' => $costo, 'precio_ultima_compra' => $costo]);

        $saldo = $this->mover($insumo->id, $movimiento->almacen_id, $cantidad);
        $this->kardex($movimiento, $insumo, $movimiento->almacen_id, $cantidad, 0, $saldo, 'COMPRA', $costo, $ppm);

        return $cantidad * $costo;
    }

    private function salida(Inventario $movimiento, Insumo $insumo, int $almacenId, float $cantidad, string $concepto): float
    {
        $saldo = $this->mover($insumo->id, $almacenId, -$cantidad);
        $this->kardex($movimiento, $insumo, $almacenId, 0, $cantidad, $saldo, $concepto);

        return $cantidad * $this->costo($insumo);
    }

    private function traspaso(Inventario $movimiento, Insumo $insumo, int $origen, int $destino, float $cantidad): float
    {
        $this->salida($movimiento, $insumo, $origen, $cantidad, 'TRASP-SAL');
        $saldo = $this->mover($insumo->id, $destino, $cantidad);
        $this->kardex($movimiento, $insumo, $destino, $cantidad, 0, $saldo, 'TRASP-ENT');

        return $cantidad * $this->costo($insumo);
    }

    private function ajuste(Inventario $movimiento, Insumo $insumo, float $contado): float
    {
        $actual = (float) InsumoAlmacen::where(['insumo_id' => $insumo->id, 'almacen_id' => $movimiento->almacen_id])->value('stock_actual');
        $diferencia = $contado - $actual;

        if ($diferencia == 0) {
            return 0;
        }

        $saldo = $this->mover($insumo->id, $movimiento->almacen_id, $diferencia, permitirNegativo: true);
        $this->kardex($movimiento, $insumo, $movimiento->almacen_id, max($diferencia, 0), max(-$diferencia, 0), $saldo, 'AJUSTE');

        return abs($diferencia) * $this->costo($insumo);
    }

    /**
     * Suma (o resta) stock en el almacén y devuelve el saldo resultante.
     */
    private function mover(int $insumoId, int $almacenId, float $cantidad, bool $permitirNegativo = false): float
    {
        $stock = InsumoAlmacen::lockForUpdate()->firstOrNew(['insumo_id' => $insumoId, 'almacen_id' => $almacenId]);
        $nuevo = (float) $stock->stock_actual + $cantidad;

        if ($nuevo < 0 && ! $permitirNegativo) {
            $nombre = Insumo::find($insumoId)?->nom_insumo;
            throw new ErrorPos("Stock insuficiente de {$nombre}: hay ".rtrim(rtrim(number_format((float) $stock->stock_actual, 3), '0'), '.').' y se quiere sacar '.abs($cantidad).'.');
        }

        $stock->stock_actual = $nuevo;
        $stock->save();

        return $nuevo;
    }

    private function kardex(Inventario $movimiento, Insumo $insumo, int $almacenId, float $entrada, float $salida, float $saldo, string $concepto, ?float $costo = null, ?float $ppm = null): void
    {
        KardexInsumo::create([
            'inventario_id' => $movimiento->id,
            'insumo_id' => $insumo->id,
            'almacen_id' => $almacenId,
            'nom_insumo' => $insumo->nom_insumo,
            'cantidad' => $entrada ?: $salida,
            'fecha_mov' => $movimiento->fecha,
            'pormenores' => mb_substr((string) $movimiento->glosa, 0, 60),
            'entrada' => $entrada,
            'salida' => $salida,
            'saldo' => $saldo,
            'movimiento' => $concepto,
            'pre_costo' => $costo ?? $this->costo($insumo),
            'ppm' => $ppm ?? $insumo->ppm,
            'unidad' => $insumo->medida,
        ]);
    }

    private function costo(Insumo $insumo): float
    {
        return (float) ($insumo->ppm ?? $insumo->pre_costo ?? 0);
    }
}
