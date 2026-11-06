<?php

namespace App\Http\Controllers;

use App\Admin\Catalogs;
use App\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, string $catalog): View
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'view');

        return view('catalogs.index', [
            'def' => $def,
            'records' => $def['model']::query()
                ->when($request->filled('q'), fn ($q) => $q->where('name', 'ilike', '%'.$request->query('q').'%'))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(string $catalog): View
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'create');

        return view('catalogs.form', ['def' => $def, 'record' => new $def['model'](['status' => 'ACTIVE'])]);
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'create');
        $record = $def['model']::create($this->validate($request, $def));
        AuditLog::register('CREATE', $catalog, $record->id, "Creó {$def['singular']} {$record->name}");

        return redirect()->route('catalogs.index', $catalog)->with('ok', ucfirst($def['singular']).' registrado.');
    }

    public function edit(string $catalog, int $id): View
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'update');

        return view('catalogs.form', ['def' => $def, 'record' => $def['model']::findOrFail($id)]);
    }

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'update');
        $record = $def['model']::findOrFail($id);
        $record->update($this->validate($request, $def));
        AuditLog::register('UPDATE', $catalog, $record->id, "Editó {$def['singular']} {$record->name}");

        return redirect()->route('catalogs.index', $catalog)->with('ok', ucfirst($def['singular']).' actualizado.');
    }

    public function destroy(string $catalog, int $id): RedirectResponse
    {
        $def = Catalogs::get($catalog);
        $this->authorizeCatalog($catalog, 'delete');
        $record = $def['model']::findOrFail($id);

        try {
            $record->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar: hay registros que lo usan.');
        }

        AuditLog::register('DELETE', $catalog, $id, "Eliminó {$def['singular']} {$record->name}");

        return back()->with('ok', ucfirst($def['singular']).' eliminado.');
    }

    private function authorizeCatalog(string $catalog, string $action): void
    {
        abort_unless(auth()->user()->can("catalogs.{$catalog}.{$action}"), 403, 'Su rol no tiene permiso para esta acción.');
    }

    private function validate(Request $request, array $def): array
    {
        return $request->validate(
            array_map(fn ($field) => $field[2], $def['fields']),
            [],
            array_map(fn ($field) => mb_strtolower($field[0]), $def['fields']),
        );
    }
}
