<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Catalogos;
use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Altas, bajas y modificaciones de los catálogos definidos en App\Admin\Catalogos.
 */
class CatalogoController extends Controller
{
    public function index(Request $request, string $catalogo): View
    {
        $def = Catalogos::obtener($catalogo);
        $consulta = $def['modelo']::query();

        if (isset($def['consulta'])) {
            ($def['consulta'])($consulta);
        }

        if ($texto = trim((string) $request->query('q'))) {
            $consulta->where(function ($q) use ($def, $texto) {
                foreach ($def['buscar'] as $columna) {
                    $q->orWhere($columna, 'ilike', '%'.$texto.'%');
                }
            });
        }

        if (($def['sucursal'] ?? false) && $request->filled('sucursal')) {
            $consulta->where('sucursal_id', $request->integer('sucursal'));
        }

        [$columna, $direccion] = $def['orden'];

        return view('admin.catalogos.index', [
            'def' => $def,
            'registros' => $consulta->orderBy($columna, $direccion)->orderBy('id')->paginate(25)->withQueryString(),
            'sucursales' => ($def['sucursal'] ?? false) ? Sucursal::orderBy('nombre')->pluck('nombre', 'id') : collect(),
        ]);
    }

    public function create(Request $request, string $catalogo): View
    {
        $def = Catalogos::obtener($catalogo);
        $registro = new $def['modelo'];

        if ($request->filled('sucursal')) {
            $registro->sucursal_id = $request->integer('sucursal');
        }

        return view('admin.catalogos.form', ['def' => $def, 'registro' => $registro]);
    }

    public function store(Request $request, string $catalogo): RedirectResponse
    {
        $def = Catalogos::obtener($catalogo);
        $registro = $def['modelo']::create($this->validar($request, $def));
        $this->despues($def, $registro, true);

        return redirect()->route('admin.catalogos.index', $catalogo)
            ->with('ok', ucfirst($def['singular']).' registrado correctamente.');
    }

    public function edit(string $catalogo, int $id): View
    {
        $def = Catalogos::obtener($catalogo);

        return view('admin.catalogos.form', ['def' => $def, 'registro' => $def['modelo']::findOrFail($id)]);
    }

    public function update(Request $request, string $catalogo, int $id): RedirectResponse
    {
        $def = Catalogos::obtener($catalogo);
        $registro = $def['modelo']::findOrFail($id);
        $registro->update($this->validar($request, $def));
        $this->despues($def, $registro, false);

        return redirect()->route('admin.catalogos.index', $catalogo)
            ->with('ok', ucfirst($def['singular']).' actualizado correctamente.');
    }

    public function destroy(string $catalogo, int $id): RedirectResponse
    {
        $def = Catalogos::obtener($catalogo);
        $registro = $def['modelo']::findOrFail($id);

        if (str_starts_with($def['eliminar'], 'estado:')) {
            $registro->update(['estado' => substr($def['eliminar'], 7)]);

            return back()->with('ok', ucfirst($def['singular']).' dado de baja.');
        }

        try {
            DB::transaction(fn () => $registro->delete());
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar: '.$def['singular'].' tiene registros relacionados.');
        }

        return back()->with('ok', ucfirst($def['singular']).' eliminado.');
    }

    private function validar(Request $request, array $def): array
    {
        $reglas = array_map(fn ($campo) => $campo[2], $def['campos']);
        $nombres = array_map(fn ($campo) => mb_strtolower($campo[0]), $def['campos']);

        return $request->validate($reglas, [], $nombres);
    }

    private function despues(array $def, $registro, bool $nuevo): void
    {
        if (isset($def['despues'])) {
            ($def['despues'])($registro, $nuevo);
        }
    }
}
