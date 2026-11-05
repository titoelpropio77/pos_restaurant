<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosificacion;
use App\Models\Gasto;
use App\Models\Insumo;
use App\Models\Mesa;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Inicio del panel: indicadores de ventas, gráfico, productos más vendidos y alertas.
 */
class DashboardController extends Controller
{
    public const PERIODOS = ['hoy' => 'Hoy', '7' => 'Últimos 7 días', '30' => 'Últimos 30 días', 'mes' => 'Este mes'];

    public function __invoke(Request $request): View
    {
        $periodo = array_key_exists($request->query('periodo'), self::PERIODOS) ? $request->query('periodo') : '7';
        $sucursalId = $request->integer('sucursal') ?: null;

        [$desde, $hasta] = match ($periodo) {
            'hoy' => [today(), today()],
            'mes' => [today()->startOfMonth(), today()],
            default => [today()->subDays((int) $periodo - 1), today()],
        };

        $ventas = fn () => Venta::whereBetween('f_venta', [$desde->toDateString(), $hasta->toDateString()])
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId));
        $activas = fn () => $ventas()->where('estado', 'ACTIVO');

        $total = (float) $activas()->sum('total_fact');
        $cantidad = $activas()->count();

        $indicadores = [
            ['Ventas', 'Bs '.number_format($total, 2), 'fa-money', 'Ventas activas del período'],
            ['Nro. de ventas', number_format($cantidad), 'fa-file-text-o', 'Tickets emitidos'],
            ['Ticket promedio', 'Bs '.number_format($cantidad ? $total / $cantidad : 0, 2), 'fa-calculator', 'Venta promedio'],
            ['Anulado', 'Bs '.number_format((float) $ventas()->where('estado', 'INACTIVO')->sum('total_fact'), 2), 'fa-ban', 'Ventas anuladas'],
            ['Gastos de caja', 'Bs '.number_format((float) Gasto::whereBetween('fechagasto', [$desde->toDateString(), $hasta->toDateString()])
                ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
                ->where('estado', 'ACTIVO')->where('tipotransaccion', 0)->sum('monto'), 2), 'fa-arrow-circle-down', 'Egresos registrados'],
        ];

        return view('admin.dashboard', [
            'periodo' => $periodo,
            'sucursalId' => $sucursalId,
            'sucursales' => Sucursal::orderBy('nombre')->pluck('nombre', 'id'),
            'indicadores' => $indicadores,
            'serie' => $periodo === 'hoy' ? $this->porHora($activas()) : $this->porDia($activas(), $desde, $hasta),
            'tituloSerie' => $periodo === 'hoy' ? 'Ventas de hoy por hora (Bs)' : 'Ventas por día (Bs)',
            'topProductos' => VentaDetalle::query()
                ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                ->whereIn('ventas.id', $activas()->select('id'))
                ->groupBy('venta_detalles.nom_prod')
                ->selectRaw('venta_detalles.nom_prod, sum(venta_detalles.cantidad) as cantidad, sum(cast(nullif(venta_detalles.agrupa1, \'\') as numeric)) as importe')
                ->orderByDesc('cantidad')
                ->limit(8)
                ->get(),
            'formasPago' => $activas()->groupBy('formapago')
                ->selectRaw('formapago, count(*) as ventas, sum(total_fact) as total')
                ->orderByDesc('total')->get(),
            'ultimasVentas' => $ventas()->with('sucursal')->latest('id')->limit(8)->get(),
            'stockBajo' => Insumo::where('estado', 'ACTIVO')
                ->withSum('stocks as stock_total', 'stock_actual')
                ->get()
                ->filter(fn ($i) => (float) $i->stock_total <= (float) $i->stock_min && (float) $i->stock_min > 0)
                ->take(8),
            'dosificaciones' => Sucursal::where('estado', 'ACTIVO')->get()
                ->map(fn ($s) => [$s, Dosificacion::vigenteDe($s->id)?->diasParaVencer(today())])
                ->filter(fn ($d) => $d[1] === null || $d[1] <= config('posgourmet.dias_aviso_dosificacion')),
            'mesasOcupadas' => Mesa::where('uso', 'S')->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))->count(),
        ]);
    }

    /** @return array<int, array{etiqueta: string, detalle: string, valor: float}> */
    private function porDia($consulta, Carbon $desde, Carbon $hasta): array
    {
        $totales = $consulta->groupBy('f_venta')->selectRaw('f_venta, sum(total_fact) as total')->pluck('total', 'f_venta');
        $serie = [];

        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            $serie[] = [
                'etiqueta' => $dia->format('d/m'),
                'detalle' => $dia->locale('es')->isoFormat('dddd D [de] MMMM'),
                'valor' => (float) ($totales[$dia->toDateString()] ?? 0),
            ];
        }

        return $serie;
    }

    private function porHora($consulta): array
    {
        $totales = $consulta->groupBy(DB::raw("split_part(dhoraven, ':', 1)"))
            ->selectRaw("cast(split_part(dhoraven, ':', 1) as int) as hora, sum(total_fact) as total")
            ->pluck('total', 'hora');

        $horas = $totales->keys()->whenEmpty(fn ($c) => $c->push(12));
        $serie = [];

        for ($h = min(8, $horas->min()); $h <= max(22, $horas->max()); $h++) {
            $serie[] = ['etiqueta' => sprintf('%02d', $h), 'detalle' => sprintf('%02d:00 a %02d:59', $h, $h), 'valor' => (float) ($totales[$h] ?? 0)];
        }

        return $serie;
    }
}
