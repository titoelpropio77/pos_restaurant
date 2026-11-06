<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\MesaPedido;
use App\Services\Mesas\PedidosMesa;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mesas del salón y sus pedidos. Port de mesasController y productoMesaController.
 */
class MesaController extends Controller
{
    private const ALIAS_PEDIDO = ['mesa_id' => 'NRO_MESA', 'sucursal_id' => 'idSucursal'];

    public function __construct(private PosContexto $pos, private PedidosMesa $pedidos) {}

    public function index(): JsonResponse
    {
        $mesas = Mesa::where('sucursal_id', $this->pos->sucursalId())
            ->where('estado', '<>', 'ELIMINADO')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return RespuestaPos::ok(RespuestaPos::mayusculas($mesas, ['sucursal_id' => 'idSucursal']));
    }

    /**
     * Bloquea la mesa mientras se cobra en caja.
     */
    public function procesando(Request $request): JsonResponse
    {
        $mesa = $this->mesa($request->integer('idMesa'));
        $mesa->update(['procesando' => 'S']);
        $this->pos->setMesaProcesando($mesa->id);

        return RespuestaPos::ok(true);
    }

    public function liberarProcesando(Request $request): JsonResponse
    {
        $this->mesa($request->integer('idMesa'))->update(['procesando' => '']);
        $this->pos->setMesaProcesando(null);

        return RespuestaPos::ok();
    }

    public function abrir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'idMesa' => ['required', 'integer'],
            'idMesero' => ['required'],
            'idProducto' => ['required', 'array', 'min:1'],
            'idProducto.*' => ['integer'],
            'cantidad' => ['required', 'array'],
            'cantidad.*' => ['numeric', 'gt:0'],
            'fechaVenta' => ['required', 'date'],
            'turno' => ['nullable', 'string'],
            'nota' => ['nullable', 'array'],
            'subtotal' => ['nullable', 'array'],
            'cantCli' => ['nullable', 'string', 'max:60'],
            'salon' => ['nullable', 'string'],
            'meserosc' => ['nullable', 'string'],
            'billar' => ['nullable', 'string'],
        ]);
        $datos['fechaVenta'] = Carbon::parse($datos['fechaVenta'])->toDateString();

        return RespuestaPos::ok($this->pedidos->abrir($this->mesa($datos['idMesa']), $datos, $request->user()));
    }

    public function agregar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'idMesa' => ['required', 'integer'],
            'idProducto' => ['required', 'array', 'min:1'],
            'idProducto.*' => ['integer'],
            'cantidad' => ['required', 'array'],
            'cantidad.*' => ['numeric', 'gt:0'],
            'fechaVenta' => ['required', 'date'],
            'nroMesa' => ['nullable'],
            'notaProducto' => ['nullable', 'array'],
            'tipoProducto' => ['nullable', 'string'],
        ]);
        $datos['fechaVenta'] = Carbon::parse($datos['fechaVenta'])->toDateString();

        return RespuestaPos::ok($this->pedidos->agregar($this->mesa($datos['idMesa']), $datos, $request->user()));
    }

    public function detalle(Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fechaVenta', today()))->toDateString();
        $items = $this->pedidos->detalle($this->mesa($request->integer('idMesa')), $fecha);

        return RespuestaPos::ok($items->isEmpty() ? '' : RespuestaPos::mayusculas($items, self::ALIAS_PEDIDO));
    }

    /**
     * Después de cobrar: opción 1 descuenta los ítems pagados, opción 2 cierra la mesa.
     */
    public function actualizarTrasCobro(Request $request): JsonResponse
    {
        $this->pedidos->actualizarTrasCobro(
            $this->mesa($request->integer('idMesa')),
            $request->integer('opcionItemMesa'),
            (array) $request->input('idDetalle', []),
            (array) $request->input('cantidad', []),
        );

        return RespuestaPos::ok();
    }

    public function traspasar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'idMesaDisminuir' => ['required', 'integer'],
            'idMesaTraspaso' => ['required', 'integer', 'different:idMesaDisminuir'],
            'idDet' => ['required', 'array'],
            'cantidad' => ['required', 'array'],
            'fecha' => ['required', 'date'],
            'nroMesaTraspaso' => ['nullable', 'string'],
            'habilitarMesa' => ['nullable'],
            'estadoMesa' => ['nullable', 'string'],
            'salon' => ['nullable', 'string'],
        ], ['idMesaTraspaso.different' => 'NO PUEDE TRASPASAR A LA MISMA MESA']);
        $datos['fecha'] = Carbon::parse($datos['fecha'])->toDateString();

        $this->pedidos->traspasar(
            $this->mesa($datos['idMesaDisminuir']),
            $this->mesa($datos['idMesaTraspaso']),
            $datos,
            $request->user(),
        );

        return RespuestaPos::ok();
    }

    public function anular(Request $request): JsonResponse
    {
        $this->pedidos->anular(
            $this->mesa($request->integer('idMesa')),
            Carbon::parse($request->input('fechaVenta', today()))->toDateString(),
        );

        return RespuestaPos::ok();
    }

    public function eliminarItem(Request $request): JsonResponse
    {
        $mesa = $this->mesa($request->integer('idMesa'));
        $item = MesaPedido::where('mesa_id', $mesa->id)->findOrFail($request->integer('idDetalle'));

        $this->pedidos->eliminarItem($mesa, $item, $request->input('totalDetalle') === 'abrirmesa');

        return RespuestaPos::ok();
    }

    public function eliminarCantidades(Request $request): JsonResponse
    {
        $this->pedidos->eliminarCantidades(
            $this->mesa($request->integer('idMesa')),
            (array) $request->input('idDetalle', []),
            (array) $request->input('cantidad', []),
        );

        return RespuestaPos::ok();
    }

    /**
     * Detiene el cronómetro de un producto cobrado por tiempo (billar).
     */
    public function pararTiempo(Request $request): JsonResponse
    {
        MesaPedido::whereKey($request->integer('idDetalle'))
            ->where('sucursal_id', $this->pos->sucursalId())
            ->update(['tiempo_fin' => (string) $request->input('horaA', now()->format('G:i'))]);

        return RespuestaPos::ok();
    }

    /**
     * Ítems eliminados de mesas en la fecha, agrupados por producto y mesa (impresión del cierre).
     */
    public function itemsEliminados(Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fecha', today()))->toDateString();

        $items = MesaPedido::query()
            ->join('mesas', 'mesas.id', '=', 'mesa_pedidos.mesa_id')
            ->where('mesa_pedidos.sucursal_id', $this->pos->sucursalId())
            ->where('mesa_pedidos.f_venta', $fecha)
            ->whereRaw("coalesce(mesa_pedidos.item_eli, '') <> ''")
            ->groupBy('mesa_pedidos.nom_prod', 'mesas.nro_mesa')
            ->selectRaw('mesa_pedidos.nom_prod, mesas.nro_mesa, sum(mesa_pedidos.cantidad) as cantidad, max(mesa_pedidos.pre_venta) as pre_venta, max(mesa_pedidos.nom_empl) as nom_empl, max(mesa_pedidos.login) as login')
            ->get();

        return RespuestaPos::ok($items->isEmpty() ? '' : RespuestaPos::mayusculas($items));
    }

    private function mesa(int $id): Mesa
    {
        return Mesa::where('sucursal_id', $this->pos->sucursalId())->findOrFail($id);
    }
}
