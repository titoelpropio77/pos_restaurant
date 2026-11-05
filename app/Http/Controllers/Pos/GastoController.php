<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Ingresos y egresos de caja desde el POS (pestaña Ingreso-Egreso). Port de gastosController.
 */
class GastoController extends Controller
{
    public function __construct(private PosContexto $pos) {}

    /**
     * Nombres a los que ya se entregó dinero, para el autocompletado.
     */
    public function entregados(): JsonResponse
    {
        $nombres = Gasto::whereNotNull('entregadoa')
            ->where('entregadoa', '<>', '')
            ->distinct()
            ->orderBy('entregadoa')
            ->pluck('entregadoa');

        return RespuestaPos::ok($nombres);
    }

    public function index(Request $request): JsonResponse
    {
        $gastos = Gasto::where('sucursal_id', $this->pos->sucursalId())
            ->where('fechagasto', Carbon::parse($request->input('fecha', today()))->toDateString())
            ->where('tipotransaccion', $request->input('tipoTrasanccion') === 'INGRESO' ? 1 : 0)
            ->orderBy('id')
            ->get();

        return RespuestaPos::ok(RespuestaPos::mayusculas($gastos, ['sucursal_id' => 'idSucursal']));
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tipoGasto' => ['required', 'in:INGRESO,EGRESO'],
            'fecha' => ['required', 'date'],
            'entregado' => ['nullable', 'string', 'max:60'],
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'turno' => ['nullable', 'string', 'max:6'],
            'fechaFactura' => ['nullable', 'date'],
            'nitProveedor' => ['nullable', 'string', 'max:25'],
            'nroAutorizacion' => ['nullable', 'string', 'max:25'],
            'codigoControl' => ['nullable', 'string', 'max:15'],
            'nroFactura' => ['nullable', 'integer'],
            'ivaCreditoFiscal' => ['nullable', 'numeric'],
            'conCreditoFiscal' => ['nullable', 'in:0,1'],
            'conFactura' => ['nullable', 'in:0,1'],
            'salioCaja' => ['nullable', 'in:0,1'],
        ], [
            'tipoGasto.required' => 'DEBE PRESIONAR EL BOTÓN NUEVO Y ELEGIR EL TIPO DE TRANSACCIÓN',
            'concepto.required' => 'DEBE INDICAR EL CONCEPTO',
            'monto.gt' => 'EL MONTO DEBE SER MAYOR A CERO',
        ]);

        $conFactura = (int) ($datos['conFactura'] ?? 0) === 1;

        $gasto = Gasto::create([
            'sucursal_id' => $this->pos->sucursalId(),
            'nrogasto' => 0,
            'fechagasto' => Carbon::parse($datos['fecha'])->toDateString(),
            'entregadoa' => $datos['entregado'] ?? '',
            'concepto' => $datos['concepto'],
            'horagasto' => now()->format('G:i'),
            'login' => $request->user()->usuario,
            'cam_of' => $this->pos->tipoCambio(),
            'monto' => $datos['monto'],
            'turno' => $datos['turno'] ?? '',
            'estado' => 'ACTIVO',
            'tipotransaccion' => $datos['tipoGasto'] === 'INGRESO' ? 1 : 0,
            'nrofactura' => $conFactura ? ($datos['nroFactura'] ?? null) : null,
            'fecha_factura' => $conFactura ? ($datos['fechaFactura'] ?? null) : null,
            'nit_proveedor' => $conFactura ? ($datos['nitProveedor'] ?? null) : null,
            'nro_autorizacion' => $conFactura ? ($datos['nroAutorizacion'] ?? null) : null,
            'codigo_control' => $conFactura ? ($datos['codigoControl'] ?? null) : null,
            'con_factura' => (int) $conFactura,
            'tipo_cambio' => $this->pos->tipoCambio(),
            'impuesto' => (int) ($datos['conCreditoFiscal'] ?? 0),
            'credito_fiscal' => $conFactura ? ($datos['ivaCreditoFiscal'] ?? null) : null,
            'saliocaja' => (int) ($datos['salioCaja'] ?? 1),
        ]);

        $gasto->update(['nrogasto' => $gasto->id]);

        return RespuestaPos::ok($gasto->id);
    }
}
