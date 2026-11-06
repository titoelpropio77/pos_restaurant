<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Inventario\DescargoInsumos;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tarjetas prepago ("gift"): una venta cuyo número de factura identifica la tarjeta y cuyo
 * campo pre_pago guarda el saldo. Port de deTajetaController.
 */
class TarjetaPrepagoController extends Controller
{
    public function __construct(private PosContexto $pos) {}

    public function buscar(Request $request): JsonResponse
    {
        $tarjeta = Venta::where('factura', $request->integer('tarjeta'))->first();

        if (! $tarjeta) {
            return RespuestaPos::error('NO EXISTE LA TARJETA');
        }

        return RespuestaPos::ok(
            [RespuestaPos::fila($tarjeta, true, ['cliente_id' => 'COD_CLI', 'sucursal_id' => 'idSucursal'])],
            ['result2' => RespuestaPos::mayusculas($tarjeta->consumosTarjeta()->orderBy('id')->get(), ['venta_id' => 'FACTURA'])],
        );
    }

    public function consumir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tarjeta' => ['required', 'integer'],
            'idProducto' => ['required', 'array', 'min:1'],
            'idProducto.*' => ['integer'],
            'cantidad' => ['required', 'array'],
            'cantidad.*' => ['numeric', 'gt:0'],
            'turno' => ['nullable', 'string'],
            'fechaVenta' => ['required', 'date'],
            'nuevoSaldo' => ['required', 'numeric', 'min:0'],
        ]);

        $usuario = $request->user();
        $fecha = Carbon::parse($datos['fechaVenta'])->toDateString();
        $hora = now()->format('G:i');

        DB::transaction(function () use ($datos, $usuario, $fecha, $hora) {
            $tarjeta = Venta::where('factura', $datos['tarjeta'])->lockForUpdate()->firstOrFail();
            $descargo = new DescargoInsumos($this->pos->almacenId(), $usuario->id, $tarjeta->id, $fecha, $hora);

            foreach ($datos['idProducto'] as $i => $productoId) {
                $producto = Producto::paraPos($this->pos->sucursalId())->where('productos.id', $productoId)->firstOrFail();

                $tarjeta->consumosTarjeta()->create([
                    'sucursal_id' => $this->pos->sucursalId(),
                    'orden' => $this->pos->ordenDia(),
                    'item' => $i + 1,
                    'cod_prod' => $producto->id,
                    'cantidad' => $datos['cantidad'][$i],
                    'pre_costo' => 0,
                    'pre_venta' => $producto->pre_venta,
                    'cam_of' => $this->pos->tipoCambio(),
                    'f_venta' => $fecha,
                    'dhoraven' => $hora,
                    'nom_prod' => $producto->nom_prod,
                    'unidad' => '0',
                    'anulado' => '0',
                    'presa' => $producto->presa,
                    'grupo' => (string) $producto->grupo,
                    'turno' => $datos['turno'] ?? '',
                    'unid' => $producto->unid,
                    'login' => $usuario->usuario,
                ]);

                $descargo->descargar($producto->id, (float) $datos['cantidad'][$i]);
            }

            $tarjeta->update(['pre_pago' => $datos['nuevoSaldo']]);
        });

        return RespuestaPos::ok();
    }
}
