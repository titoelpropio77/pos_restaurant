<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Catalogs;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Altas, bajas y modificaciones de los catálogos definidos en App\Admin\Catalogos.
 */
class CatalogController extends Controller
{
    public function index(Request $request, string $catalog): View
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'view');
        $query = $def['model']::query();

        if (isset($def['query'])) {
            ($def['query'])($query);
        }

        if ($text = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($def, $text) {
                foreach ($def['search'] as $column) {
                    $q->orWhere($column, 'ilike', '%'.$text.'%');
                }
            });
        }

        if (($def['branch'] ?? false) && $request->filled('branch')) {
            $query->where('branch_id', $request->integer('branch'));
        }

        [$column, $address] = $def['order_number'];

        return view('admin.catalogs.index', [
            'def' => $def,
            'records' => $query->orderBy($column, $address)->orderBy('id')->paginate(25)->withQueryString(),
            'branches' => ($def['branch'] ?? false) ? Branch::orderBy('name')->pluck('name', 'id') : collect(),
        ]);
    }

    public function create(Request $request, string $catalog): View
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'create');
        $record = new $def['model'];

        if ($request->filled('branch')) {
            $record->branch_id = $request->integer('branch');
        }

        return view('admin.catalogs.form', ['def' => $def, 'record' => $record]);
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'create');
        $record = $def['model']::create($this->validate($request, $def));
        $this->after($def, $record, true);

        return redirect()->route('admin.catalogs.index', $catalog)
            ->with('ok', ucfirst($def['singular']).' registrado correctamente.');
    }

    public function edit(string $catalog, int $id): View
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'update');

        return view('admin.catalogs.form', ['def' => $def, 'record' => $def['model']::findOrFail($id)]);
    }

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'update');
        $record = $def['model']::findOrFail($id);
        $data = $this->validate($request, $def);
        $this->before($def, $record, $data);
        $record->update($data);
        $this->after($def, $record, false);

        return redirect()->route('admin.catalogs.index', $catalog)
            ->with('ok', ucfirst($def['singular']).' actualizado correctamente.');
    }

    public function destroy(string $catalog, int $id): RedirectResponse
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'delete');
        $record = $def['model']::findOrFail($id);

        if (str_starts_with($def['delete'], 'status:')) {
            $data = ['status' => substr($def['delete'], 7)];
            $this->before($def, $record, $data);
            $record->update($data);

            return back()->with('ok', ucfirst($def['singular']).' dado de baja.');
        }

        try {
            DB::transaction(fn () => $record->delete());
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar: '.$def['singular'].' tiene registros relacionados.');
        }

        return back()->with('ok', ucfirst($def['singular']).' eliminado.');
    }

    private function authorizeCatalog(string $catalog, string $action): void
    {
        abort_unless(auth()->user()->can("catalogs.{$catalog}.{$action}"), 403, 'Su rol no tiene permiso para esta acción.');
    }

    private function validate(Request $request, array $def): array
    {
        $rules = array_map(fn ($field) => $field[2], $def['fields']);
        $name = array_map(fn ($field) => mb_strtolower($field[0]), $def['fields']);

        return $request->validate($rules, [], $name);
    }

    /** Reglas de negocio del catálogo antes de modificar un registro: lanzan ErrorPos. */
    private function before(array $def, $record, array $data): void
    {
        if (isset($def['before'])) {
            ($def['before'])($record, $data);
        }
    }

    private function after(array $def, $record, bool $is_new): void
    {
        if (isset($def['after'])) {
            ($def['after'])($record, $is_new);
        }
    }
}
