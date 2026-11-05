<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Dosificacion;
use App\Models\Personal;
use App\Models\Sucursal;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Datos de la sucursal de trabajo: cambio de sucursal, impresora, dosificación y meseros.
 * Port de sucursalController, printersettingController, docificacionController y meseroController.
 */
class SucursalController extends Controller
{
    public function __construct(private PosContexto $pos) {}

    public function index(): JsonResponse
    {
        return RespuestaPos::ok(RespuestaPos::minusculas(Sucursal::where('estado', 'ACTIVO')->orderBy('nombre')->get()));
    }

    public function cambiar(Request $request): JsonResponse
    {
        if (! $this->pos->ventaMultialmacen()) {
            return RespuestaPos::error('EL USUARIO NO PUEDE VENDER EN OTRAS SUCURSALES');
        }

        $this->pos->cambiarSucursal(Sucursal::findOrFail($request->integer('idSuc')));

        return RespuestaPos::ok($this->pos->ordenDia());
    }

    /**
     * IP del agente local de impresión de la sucursal.
     */
    public function impresion(): JsonResponse
    {
        $sucursal = Sucursal::findOrFail($this->pos->sucursalId());

        return RespuestaPos::ok([[
            'nombreSucursal' => $sucursal->nombre,
            'ipImpresion' => $sucursal->ip_impresion ?? '',
        ]]);
    }

    public function dosificacion(): JsonResponse
    {
        $dosificacion = Dosificacion::vigenteDe($this->pos->sucursalId());

        return RespuestaPos::ok($dosificacion ? [RespuestaPos::fila($dosificacion)] : '');
    }

    /**
     * Aviso de vencimiento de la dosificación (7 días antes) o de dosificación vencida.
     */
    public function verificarDosificacion(Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fecha', today()));
        $dosificacion = Dosificacion::vigenteDe($this->pos->sucursalId());
        $dias = $dosificacion?->diasParaVencer($fecha);

        if ($dias === null) {
            return RespuestaPos::ok('');
        }

        $limite = $dosificacion->fecha_lim->format('d/m/Y');
        $aviso = match (true) {
            $dias < 0 => 'SU DOSIFICACIÓN YA SE VENCIÓ, NO PODRÁ VENDER',
            $dias > 0 && $dias <= config('posgourmet.dias_aviso_dosificacion') => "LE QUEDAN {$dias} DÍAS PARA QUE SU DOSIFICACIÓN SE VENZA, SE RECOMIENDA DOSIFICAR ANTES DE LA FECHA: {$limite}",
            default => '',
        };

        return RespuestaPos::ok($aviso);
    }

    public function meseros(): JsonResponse
    {
        $meseros = Personal::where(fn ($q) => $q->where('sucursal_id', $this->pos->sucursalId())->orWhereNull('sucursal_id'))
            ->orderBy('nom_empl')
            ->get()
            ->makeHidden('codigo');

        return RespuestaPos::ok(RespuestaPos::mayusculas($meseros, ['sucursal_id' => 'idSucursal']));
    }
}
