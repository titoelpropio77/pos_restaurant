<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\FacturarRequest;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\Caja\ArqueoCaja;
use App\Services\Ventas\RegistrarVenta;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ventas del POS: cobro, histórico del día, anulación, datos de impresión y cierre de caja.
 * Port de facturarController, venGralController, detalleVentaController y REPORTEPHP/prueba5.php.
 */
class VentaController extends Controller
{
    /** Nombres de columna legacy que cambiaron en el nuevo esquema. */
    private const ALIAS_VENTA = ['cliente_id' => 'COD_CLI', 'sucursal_id' => 'idSucursal'];

    private const ALIAS_DETALLE = ['venta_id' => 'FACTURA'];

    public function __construct(private PosContexto $pos) {}

    public function store(FacturarRequest $request, RegistrarVenta $registrar): JsonResponse
    {
        $resultado = $registrar->ejecutar($request->validated(), $request->user());

        return RespuestaPos::ok($resultado['venta']->id, [
            'result2' => $resultado['orden'],
            'nroFac' => $resultado['factura'],
            'productoImp' => '',
        ]);
    }

    public function historico(Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fechaHistorico', today()))->toDateString();

        $ventas = Venta::where('sucursal_id', $this->pos->sucursalId())
            ->where('f_venta', $fecha)
            ->orderBy('id')
            ->get();

        return RespuestaPos::ok(RespuestaPos::mayusculas($ventas, self::ALIAS_VENTA));
    }

    public function detalle(Request $request): JsonResponse
    {
        $venta = Venta::where('sucursal_id', $this->pos->sucursalId())->findOrFail($request->integer('id'));

        return RespuestaPos::ok(
            RespuestaPos::mayusculas($venta->detalles()->orderBy('item')->get(), self::ALIAS_DETALLE),
            ['result2' => [$this->ventaLegacy($venta)]],
        );
    }

    /**
     * Anula o reactiva una venta (botones ANULAR / ACTIVAR del histórico).
     */
    public function anular(Request $request): JsonResponse
    {
        $venta = Venta::where('sucursal_id', $this->pos->sucursalId())->findOrFail($request->integer('id'));
        $anular = $request->input('estado') === 'ANULAR';

        DB::transaction(function () use ($venta, $anular) {
            $venta->update(['estado' => $anular ? 'INACTIVO' : 'ACTIVO', 'anulado' => $anular ? 'S' : '']);
            $venta->detalles()->update(['anulado' => $anular ? 'S' : '']);
        });

        return RespuestaPos::ok();
    }

    /**
     * Datos que el agente de impresión necesita para la factura y la comanda de cocina.
     * Legacy: REPORTEPHP/prueba5.php. Responde sin el sobre {error, result}: el frontend
     * imprime cuando `error` no viene definido, igual que con el script original.
     */
    public function impresion(Request $request): JsonResponse
    {
        $venta = Venta::where('sucursal_id', $this->pos->sucursalId())->findOrFail($request->integer('idVenta'));
        $dosificacion = Dosificacion::vigenteDe($this->pos->sucursalId());

        $detalle = $venta->detalles()->orderBy('item')->get()->map(fn (VentaDetalle $d) => [
            'FACTURA' => $d->venta_id,
            'ORDEN' => $d->orden,
            'CANTIDAD' => $d->cantidad,
            'PRE_VENTA' => $d->pre_venta,
            'F_VENTA' => $venta->f_venta->format('Y-m-d'),
            'NOM_PROD' => $d->nom_prod,
            'TURNO' => $d->turno ?? '',
            'FAMILIA' => $d->familia ?? '',
            'SALIDA' => $d->salida ?? '',
            'LOGIN' => $d->login ?? '',
            'NOTA' => $d->nota ?? '',
            'AGRUPA1' => $d->agrupa1 ?? '',
            'AGRUPA3' => $d->agrupa3 ?? '',
        ]);

        return response()->json([
            'listaVenta' => [$this->ventaLegacy($venta)],
            'listaEmpresa' => $dosificacion ? [RespuestaPos::fila($dosificacion)] : [],
            'listaDVenta' => $detalle,
            'codigoqr' => $venta->cifrado ?? '',
        ]);
    }

    /**
     * Cajeros que registraron ventas en la fecha (para el cierre de caja).
     */
    public function cajeros(Request $request): JsonResponse
    {
        $logins = Venta::where('sucursal_id', $this->pos->sucursalId())
            ->where('f_venta', Carbon::parse($request->input('fecha', today()))->toDateString())
            ->distinct()
            ->orderBy('login')
            ->pluck('login')
            ->map(fn ($login) => ['LOGIN' => $login]);

        return RespuestaPos::ok($logins);
    }

    /**
     * Totales del arqueo de caja: facturado, crédito, consumo, anulado, contado, gastos e ingresos.
     */
    public function totalesCierre(Request $request, ArqueoCaja $arqueo): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fecha', today()))->toDateString();
        $turno = (string) $request->input('turno', 'MAÑANA Y NOCHE');
        $todasSucursales = (int) $request->input('multiSuc', 0) !== 0;

        $totales = $arqueo->totales(
            $fecha,
            $fecha,
            $todasSucursales ? null : $this->pos->sucursalId(),
            in_array($turno, ['MAÑANA', 'NOCHE'], true) ? $turno : null,
            $request->input('empleado') ?: null,
        );

        return RespuestaPos::ok([array_map(fn ($valor) => number_format($valor, 2, '.', ''), $totales)]);
    }

    /**
     * Cierre diario: marca como cerradas las ventas del turno/cajero y reinicia la orden del día.
     */
    public function cierre(Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fecha', today()))->toDateString();
        $cajero = (string) $request->input('empleado', '');
        $turno = match ($request->input('entrada')) {
            'TURNOMAÑANA' => 'MAÑANA',
            'TURNONOCHE' => 'NOCHE',
            default => null,
        };

        DB::transaction(function () use ($fecha, $cajero, $turno) {
            $ventas = Venta::where('sucursal_id', $this->pos->sucursalId())
                ->where('f_venta', $fecha)
                ->when($turno, fn ($q) => $q->where('turno', $turno))
                ->when($cajero !== '', fn ($q) => $q->where('login', $cajero));

            VentaDetalle::whereIn('venta_id', (clone $ventas)->select('id'))->update(['cerrado' => 'S']);
            $ventas->update(['cerrado' => 'S']);

            Correlativo::where('sucursal_id', $this->pos->sucursalId())->update(['ordendia' => 0]);
        });

        $this->pos->setOrdenDia(0);

        return RespuestaPos::ok();
    }

    /**
     * Venta con los nombres de campo legacy y la fecha en formato d/m/Y (VEN_GRAL_MYSQL::buscarXID).
     */
    private function ventaLegacy(Venta $venta): array
    {
        $fila = RespuestaPos::fila($venta, true, self::ALIAS_VENTA);
        $fila['F_VENTA'] = $venta->f_venta->format('d/m/Y');

        return $fila;
    }
}
