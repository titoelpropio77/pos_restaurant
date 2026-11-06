<?php

namespace App\Services\Ventas;

use App\Exceptions\ErrorPos;
use App\Models\Correlativo;

/**
 * Asigna el número de factura / recibo y la orden del día a una venta.
 *
 * Port de la lógica de correlativos de facturarController.php:
 *  - folio:  número de factura fiscal.
 *  - folior: número "real" (recibo). Una venta sin NIT y que no es a crédito sólo avanza
 *            el folior; cada `mult` recibos se consume un folio (cuando mult > 0).
 *  - imprimir: si está definido, toda venta avanza el folio y no se usa folior.
 *
 * Debe ejecutarse dentro de una transacción: bloquea la fila del correlativo.
 */
class NumeracionFactura
{
    /**
     * @return array{folio:int, folior:int, orden:int, factura_impresa:int}
     */
    public function siguiente(int $sucursalId, string $nit, string $formaPago): array
    {
        $correlativo = Correlativo::where('tabla', config('posgourmet.correlativo_ventas'))
            ->where('sucursal_id', $sucursalId)
            ->lockForUpdate()
            ->first();

        if (! $correlativo) {
            throw new ErrorPos('NO EXISTE CORRELATIVO DE VENTAS PARA ESTA SUCURSAL');
        }

        $folio = $correlativo->folio;
        $folior = $correlativo->folior;
        $orden = $correlativo->ordendia + 1;
        $multiplo = (int) $correlativo->mult;

        if (! $correlativo->imprimir) {
            if ($this->esRecibo($nit, $formaPago)) {
                $folior++;
                if ($multiplo > 0) {
                    $contador = (int) $correlativo->multcon + 1;
                    $correlativo->multcon = $contador;
                    if ($contador === $multiplo) {
                        $folio++;
                        $folior = $folio;
                        $correlativo->multcon = 0;
                        $correlativo->folio = $folio;
                    }
                }
                $correlativo->folior = $folior;
            } else {
                $folio++;
                $folior = $folio;
                $correlativo->folio = $folio;
                $correlativo->folior = $folio;
            }
            $facturaImpresa = $folior;
        } else {
            $folio++;
            $folior = 0;
            $correlativo->folio = $folio;
            $facturaImpresa = $folio;
        }

        $correlativo->ordendia = $orden;
        $correlativo->save();

        return [
            'folio' => $folio,
            'folior' => $folior,
            'orden' => $orden,
            'factura_impresa' => $facturaImpresa,
        ];
    }

    /**
     * En el sistema original una venta sin NIT (y que no es a crédito) se registra como recibo.
     */
    private function esRecibo(string $nit, string $formaPago): bool
    {
        return trim($nit) === '' && $formaPago !== 'CREDITO';
    }
}
