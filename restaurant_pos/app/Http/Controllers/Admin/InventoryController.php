<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MovementReason;
use App\Models\StockLedgerEntry;
use App\Models\StockMovement;
use App\Models\Supply;
use App\Models\Warehouse;
use App\Services\StockMovement\StockMovementService;
use App\Support\CsvExporter;
use App\Support\Tenant;
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
class InventoryController extends Controller
{
    public function stock(Request $request): View|Response
    {
        $warehouses = Warehouse::where('status', 'ACTIVE')
            ->when($request->filled('warehouse'), fn ($q) => $q->whereKey($request->integer('warehouse')))
            ->with('branch')->orderBy('name')->get();

        $supplies = Supply::with('stocks')
            ->where('status', 'ACTIVE')
            ->when($request->filled('q'), fn ($q) => $q->where('supply_name', 'ilike', '%'.$request->query('q').'%'))
            ->orderBy('supply_name')
            ->get()
            ->map(function (Supply $supply) use ($warehouses) {
                $porWarehouse = $warehouses->mapWithKeys(fn ($a) => [$a->id => (float) $supply->stocks->firstWhere('warehouse_id', $a->id)?->current_stock]);
                $total = $porWarehouse->sum();
                $cost = (float) ($supply->average_cost ?? $supply->cost_price ?? 0);

                return (object) [
                    'supply' => $supply,
                    'porWarehouse' => $porWarehouse,
                    'total' => $total,
                    'cost' => $cost,
                    'value' => $total * $cost,
                    'low' => (float) $supply->min_stock > 0 && $total <= (float) $supply->min_stock,
                ];
            })
            ->when($request->boolean('low'), fn ($c) => $c->where('low', true))
            ->values();

        if ($request->query('export') === 'csv') {
            return CsvExporter::download('stock',
                ['Insumo', 'Unidad', ...$warehouses->pluck('name'), 'Total', 'Stock mínimo', 'Costo prom.', 'Valor (Bs)'],
                $supplies->map(fn ($f) => [$f->supply->supply_name, $f->supply->measure, ...$f->porWarehouse->values()->all(), $f->total, (float) $f->supply->min_stock, $f->cost, $f->value]),
            );
        }

        return view('admin.inventory.stock', [
            'rows' => $supplies,
            'warehouses' => $warehouses,
            'allWarehouses' => Warehouse::where('status', 'ACTIVE')->orderBy('name')->pluck('name', 'id'),
            'valueTotal' => $supplies->sum('value'),
        ]);
    }

    public function movements(Request $request): View
    {
        return view('admin.inventory.movements', [
            'movements' => StockMovement::with(['warehouse', 'destinationWarehouse', 'movementReason', 'user'])
                ->withCount('kardex')
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
                ->when($request->filled('warehouse'), fn ($q) => $q->where(fn ($q) => $q
                    ->where('warehouse_id', $request->integer('warehouse'))->orWhere('destination_warehouse_id', $request->integer('warehouse'))))
                ->when($request->filled('from'), fn ($q) => $q->where('date', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($q) => $q->where('date', '<=', $request->date('to')))
                ->latest('date')->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
            'types' => StockMovementService::TYPES + ['VENTA' => 'Venta (automático)'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.inventory.create', [
            'type' => array_key_exists($request->query('type'), StockMovementService::TYPES) ? $request->query('type') : 'COMPRA',
            'types' => StockMovementService::TYPES,
            'warehouses' => Warehouse::where('status', 'ACTIVE')->with('branch')->orderBy('name')->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->name.' ('.$a->branch?->name.')']),
            'reasons' => MovementReason::where('status', 'ACTIVE')->where('name', '<>', 'VENTA')->orderBy('name')->pluck('name', 'id'),
            'supplies' => Supply::where('status', 'ACTIVE')->orderBy('supply_name')
                ->get(['id', 'supply_name', 'measure', 'average_cost', 'cost_price'])
                ->map(fn ($i) => ['id' => $i->id, 'name' => $i->supply_name, 'measure' => $i->measure, 'cost' => (float) ($i->average_cost ?? $i->cost_price ?? 0)]),
        ]);
    }

    public function store(Request $request, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(StockMovementService::TYPES))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'warehouse_id' => ['required', Tenant::exists('warehouses')],
            'destination_warehouse_id' => ['required_if:tipo,TRASPASO', 'nullable', Tenant::exists('warehouses'), 'different:warehouse_id'],
            'movement_reason_id' => ['nullable', Tenant::exists('movement_reasons')],
            'description' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.supply_id' => ['required', 'distinct', Tenant::exists('supplies')],
            'lines.*.quantity' => ['required', 'numeric', $request->input('type') === 'AJUSTE' ? 'min:0' : 'gt:0'],
            'lines.*.cost' => [Rule::requiredIf($request->input('type') === 'COMPRA'), 'nullable', 'numeric', 'min:0'],
        ], [
            'lines.required' => 'Agregue al menos un insumo.',
            'destination_warehouse_id.required_if' => 'Indique el almacén de destino del traspaso.',
            'destination_warehouse_id.different' => 'El almacén de destino debe ser distinto al de origen.',
        ], [
            'warehouse_id' => 'almacén', 'lines.*.supply_id' => 'supply', 'lines.*.quantity' => 'quantity', 'lines.*.cost' => 'costo unitario',
        ]);

        $movement_type = $service->registrar($data, $request->user());

        return redirect()->route('admin.inventory.show', $movement_type)->with('ok', 'Movimiento registrado.');
    }

    public function show(StockMovement $inventory): View
    {
        return view('admin.inventory.show', [
            'movement_type' => $inventory->load(['warehouse', 'destinationWarehouse', 'movementReason', 'user', 'kardex.warehouse']),
        ]);
    }

    public function kardex(Request $request): View|Response
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        $supply = $request->filled('supply') ? Supply::find($request->integer('supply')) : null;

        $movements = collect();
        if ($supply) {
            $movements = StockLedgerEntry::with(['warehouse', 'inventory'])
                ->where('supply_id', $supply->id)
                ->when($request->filled('warehouse'), fn ($q) => $q->where('warehouse_id', $request->integer('warehouse')))
                ->whereBetween('movement_date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('movement_date')->orderBy('id')
                ->get();
        }

        if ($supply && $request->query('export') === 'csv') {
            return CsvExporter::download('kardex-'.str($supply->supply_name)->slug(),
                ['Fecha', 'Almacén', 'Movimiento', 'Detalle', 'Entrada', 'Salida', 'Saldo', 'Costo unit.'],
                $movements->map(fn ($k) => [$k->movement_date->format('d/m/Y'), $k->warehouse?->name, $k->movement_type, $k->details ?: $k->inventory?->description, (float) $k->qty_in, (float) $k->qty_out, (float) $k->balance, (float) $k->cost_price]),
            );
        }

        return view('admin.inventory.kardex', [
            'supply' => $supply,
            'movements' => $movements,
            'from' => Carbon::parse($from),
            'to' => Carbon::parse($to),
            'supplies' => Supply::orderBy('supply_name')->pluck('supply_name', 'id'),
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
