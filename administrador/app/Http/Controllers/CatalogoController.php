<?php

namespace App\Http\Controllers;

use App\Admin\Catalogos;
use App\Models\Auditoria;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(Request $request, string $catalogo): View
    {
        $def = Catalogos::obtener($catalogo);

        return view('catalogos.index', [
            'def' => $def,
            'registros' => $def['modelo']::query()
                ->when($request->filled('q'), fn ($q) => $q->where('nombre', 'ilike', '%'.$request->query('q').'%'))
                ->orderBy('nombre')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(string $catalogo): View
    {
        $def = Catalogos::obtener($catalogo);

        return view('catalogos.form', ['def' => $def, 'registro' => new $def['modelo'](['estado' => 'ACTIVO'])]);
    }

    public function store(Request $request, string $catalogo): RedirectResponse
    {
        $def = Catalogos::obtener($catalogo);
        $registro = $def['modelo']::create($this->validar($request, $def));
        Auditoria::registrar('CREAR', $catalogo, $registro->id, "Creó {$def['singular']} {$registro->nombre}");

        return redirect()->route('catalogos.index', $catalogo)->with('ok', ucfirst($def['singular']).' registrado.');
    }

    public function edit(string $catalogo, int $id): View
    {
        $def = Catalogos::obtener($catalogo);

        return view('catalogos.form', ['def' => $def, 'registro' => $def['modelo']::findOrFail($id)]);
    }

    public function update(Request $request, string $catalogo, int $id): RedirectResponse
    {
        $def = Catalogos::obtener($catalogo);
        $registro = $def['modelo']::findOrFail($id);
        $registro->update($this->validar($request, $def));
        Auditoria::registrar('EDITAR', $catalogo, $registro->id, "Editó {$def['singular']} {$registro->nombre}");

        return redirect()->route('catalogos.index', $catalogo)->with('ok', ucfirst($def['singular']).' actualizado.');
    }

    public function destroy(string $catalogo, int $id): RedirectResponse
    {
        $def = Catalogos::obtener($catalogo);
        $registro = $def['modelo']::findOrFail($id);

        try {
            $registro->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar: hay registros que lo usan.');
        }

        Auditoria::registrar('ELIMINAR', $catalogo, $id, "Eliminó {$def['singular']} {$registro->nombre}");

        return back()->with('ok', ucfirst($def['singular']).' eliminado.');
    }

    private function validar(Request $request, array $def): array
    {
        return $request->validate(
            array_map(fn ($campo) => $campo[2], $def['campos']),
            [],
            array_map(fn ($campo) => mb_strtolower($campo[0]), $def['campos']),
        );
    }
}
