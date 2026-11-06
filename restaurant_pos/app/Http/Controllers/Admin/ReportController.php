<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Reports\Filters;
use App\Admin\Reports\Registry;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Sale;
use App\Support\CsvExporter;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportes del panel (definidos en App\Admin\Reportes).
 */
class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', ['groups' => Registry::grouped()]);
    }

    public function show(Request $request, string $report): View|Response
    {
        $definition = Registry::get($report);
        $filters = Filters::from($request);
        $rows = collect($definition->rows($filters))->map(fn ($row) => (array) $row)->values()->all();
        $totals = $definition->totals($rows);

        if ($request->query('export') === 'csv') {
            abort_unless($request->user()->can('reports.export'), 403, 'Su rol no tiene permiso para exportar reportes.');

            $columns = $definition->columns();

            return CsvExporter::download($report,
                array_map(fn ($c) => $c[0], $columns),
                array_map(fn ($row) => array_map(fn ($key) => $this->valueFlat($row[$key] ?? null, $columns[$key][1]), array_keys($columns)), $rows),
            );
        }

        return view('admin.reports.show', [
            'slug' => $report,
            'report' => $definition,
            'filters' => $filters,
            'rows' => $rows,
            'totals' => $totals,
            'options' => $this->optionsFilters($definition->filters()),
        ]);
    }

    private function optionsFilters(array $used): array
    {
        return [
            'branch' => in_array('branch', $used) ? Branch::orderBy('name')->pluck('name', 'id') : collect(),
            'cajero' => in_array('cajero', $used) ? Sale::distinct()->orderBy('login')->pluck('login', 'login') : collect(),
            'payment_method' => in_array('payment_method', $used) ? Sale::distinct()->orderBy('payment_method')->pluck('payment_method', 'payment_method') : collect(),
            'category' => in_array('category', $used) ? Category::orderBy('order_number')->pluck('group_name', 'id') : collect(),
        ];
    }

    private function valueFlat(mixed $value, string $format): mixed
    {
        return match (true) {
            $value === null => '',
            $format === 'date' => ($value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->format('d/m/Y'),
            in_array($format, ['currency', 'number', 'percent'], true) => (float) $value,
            default => $value,
        };
    }
}
