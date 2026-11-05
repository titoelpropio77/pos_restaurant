<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Insumo;
use App\Models\Inventario;
use App\Models\KardexInsumo;
use App\Models\MotivoMovimiento;
use App\Services\Inventario\MovimientoInventario;
use App\Support\ExportadorCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stock por almacén, movimientos de inventario y kardex.
 * Legacy: inventario.php / inventarioController / kardexController.
 */
class InventarioController extends Controller
{
    public function stock(Request $request): View|Response
    {
        $almacenes = Almacen::where('estado', 'ACTIVO')
            ->when($request->filled('almacen'), fn ($q) => $q->whereKey($request->integer('almacen')))
            ->with('sucursal')->orderBy('nombre')->get();

        $insumos = Insumo::with('stocks')
            ->where('estado', 'ACTIVO')
            ->when($request->filled('q'), fn ($q) => $q->where('nom_insumo', 'ilike', '%'.$request->query('q').'%'))
            ->orderBy('nom_insumo')
            ->get()
            ->map(function (Insumo $insumo) use ($almacenes) {
                $porAlmacen = $almacenes->mapWithKeys(fn ($a) => [$a->id => (float) $insumo->stocks->firstWhere('almacen_id', $a->id)?->stock_actual]);
                $total = $porAlmacen->sum();
                $costo = (float) ($insumo->ppm ?? $insumo->pre_costo ?? 0);

                return (object) [
                    'insumo' => $insumo,
                    'porAlmacen' => $porAlmacen,
                    'total' => $total,
                    'costo' => $costo,
                    'valor' => $total * $costo,
                    'bajo' => (float) $insumo->stock_min > 0 && $total <= (float) $insumo->stock_min,
                ];
            })
            ->when($request->boolean('bajos'), fn ($c) => $c->where('bajo', true))
            ->values();

        if ($request->query('exportar') === 'csv') {
            return ExportadorCsv::descargar('stock',
                ['Insumo', 'Unidad', ...$almacenes->pluck('nombre'), 'Total', 'Stock mínimo', 'Costo prom.', 'Valor (Bs)'],
                $insumos->map(fn ($f) => [$f->insumo->nom_insumo, $f->insumo->medida, ...$f->porAlmacen->values()->all(), $f->total, (float) $f->insumo->stock_min, $f->costo, $f->valor]),
            );
        }

        return view('admin.inventario.stock', [
            'filas' => $insumos,
            'almacenes' => $almacenes,
            'todosAlmacenes' => Almacen::where('estado', 'ACTIVO')->orderBy('nombre')->pluck('nombre', 'id'),
            'valorTotal' => $insumos->sum('valor'),
        ]);
    }

    public function movimientos(Request $request): View
    {
        return view('admin.inventario.movimientos', [
            'movimientos' => Inventario::with(['almacen', 'almacenDestino', 'motivoMovimiento', 'usuario'])
                ->withCount('kardex')
                ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->query('tipo')))
                ->when($request->filled('almacen'), fn ($q) => $q->where(fn ($q) => $q
                    ->where('almacen_id', $request->integer('almacen'))->orWhere('almacen_destino_id', $request->integer('almacen'))))
                ->when($request->filled('desde'), fn ($q) => $q->where('fecha', '>=', $request->date('desde')))
                ->when($request->filled('hasta'), fn ($q) => $q->where('fecha', '<=', $request->date('hasta')))
                ->latest('fecha')->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'almacenes' => Almacen::orderBy('nombre')->pluck('nombre', 'id'),
            'tipos' => MovimientoInventario::TIPOS + ['VENTA' => 'Venta (automático)'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.inventario.create', [
            'tipo' => array_key_exists($request->query('tipo'), MovimientoInventario::TIPOS) ? $request->query('tipo') : 'COMPRA',
            'tipos' => MovimientoInventario::TIPOS,
            'almacenes' => Almacen::where('estado', 'ACTIVO')->with('sucursal')->orderBy('nombre')->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->nombre.' ('.$a->sucursal?->nombre.')']),
            'motivos' => MotivoMovimiento::where('estado', 'ACTIVO')->where('nombre', '<>', 'VENTA')->orderBy('nombre')->pluck('nombre', 'id'),
            'insumos' => Insumo::where('estado', 'ACTIVO')->orderBy('nom_insumo')
                ->get(['id', 'nom_insumo', 'medida', 'ppm', 'pre_costo'])
                ->map(fn ($i) => ['id' => $i->id, 'nombre' => $i->nom_insumo, 'medida' => $i->medida, 'costo' => (float) ($i->ppm ?? $i->pre_costo ?? 0)]),
        ]);
    }

    public function store(Request $request, MovimientoInventario $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(MovimientoInventario::TIPOS))],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'almacen_destino_id' => ['required_if:tipo,TRASPASO', 'nullable', 'exists:almacenes,id', 'different:almacen_id'],
            'motivo_movimiento_id' => ['nullable', 'exists:motivos_movimiento,id'],
            'glosa' => ['nullable', 'string', 'max:500'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.insumo_id' => ['required', 'distinct', 'exists:insumos,id'],
            'lineas.*.cantidad' => ['required', 'numeric', $request->input('tipo') === 'AJUSTE' ? 'min:0' : 'gt:0'],
            'lineas.*.costo' => [Rule::requiredIf($request->input('tipo') === 'COMPRA'), 'nullable', 'numeric', 'min:0'],
        ], [
            'lineas.required' => 'Agregue al menos un insumo.',
            'almacen_destino_id.required_if' => 'Indique el almacén de destino del traspaso.',
            'almacen_destino_id.different' => 'El almacén de destino debe ser distinto al de origen.',
        ], [
            'almacen_id' => 'almacén', 'lineas.*.insumo_id' => 'insumo', 'lineas.*.cantidad' => 'cantidad', 'lineas.*.costo' => 'costo unitario',
        ]);

        $movimiento = $servicio->registrar($datos, $request->user());

        return redirect()->route('admin.inventario.show', $movimiento)->with('ok', 'Movimiento registrado.');
    }

    public function show(Inventario $inventario): View
    {
        return view('admin.inventario.show', [
            'movimiento' => $inventario->load(['almacen', 'almacenDestino', 'motivoMovimiento', 'usuario', 'kardex.almacen']),
        ]);
    }

    public function kardex(Request $request): View|Response
    {
        $desde = $request->date('desde') ?? today()->startOfMonth();
        $hasta = $request->date('hasta') ?? today();
        $insumo = $request->filled('insumo') ? Insumo::find($request->integer('insumo')) : null;

        $movimientos = collect();
        if ($insumo) {
            $movimientos = KardexInsumo::with(['almacen', 'inventario'])
                ->where('insumo_id', $insumo->id)
                ->when($request->filled('almacen'), fn ($q) => $q->where('almacen_id', $request->integer('almacen')))
                ->whereBetween('fecha_mov', [$desde->toDateString(), $hasta->toDateString()])
                ->orderBy('fecha_mov')->orderBy('id')
                ->get();
        }

        if ($insumo && $request->query('exportar') === 'csv') {
            return ExportadorCsv::descargar('kardex-'.str($insumo->nom_insumo)->slug(),
                ['Fecha', 'Almacén', 'Movimiento', 'Detalle', 'Entrada', 'Salida', 'Saldo', 'Costo unit.'],
                $movimientos->map(fn ($k) => [$k->fecha_mov->format('d/m/Y'), $k->almacen?->nombre, $k->movimiento, $k->pormenores ?: $k->inventario?->glosa, (float) $k->entrada, (float) $k->salida, (float) $k->saldo, (float) $k->pre_costo]),
            );
        }

        return view('admin.inventario.kardex', [
            'insumo' => $insumo,
            'movimientos' => $movimientos,
            'desde' => Carbon::parse($desde),
            'hasta' => Carbon::parse($hasta),
            'insumos' => Insumo::orderBy('nom_insumo')->pluck('nom_insumo', 'id'),
            'almacenes' => Almacen::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
