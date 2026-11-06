<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Permisos. Los protegidos son los que el código consulta: su clave no cambia y no se eliminan.
 * Uno nuevo sólo tiene efecto cuando una ruta de las aplicaciones lo exige.
 */
class PermisoController extends Controller
{
    public function index(): View
    {
        return view('permisos.index', [
            'permisos' => Permiso::withCount('roles')->orderBy('ambito')->orderBy('modulo')->orderBy('id')->get()->groupBy('ambito'),
        ]);
    }

    public function create(): View
    {
        return view('permisos.form', ['permiso' => new Permiso(['ambito' => Rol::RESTAURANTE])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $permiso = new Permiso;
        $this->guardar($request, $permiso);

        // Los roles totales tienen siempre todos los permisos de su ámbito
        Rol::delAmbito($permiso->ambito)->where('protegido', true)->whereIn('nombre', Rol::TOTALES)->first()?->permisos()->attach($permiso);
        Auditoria::registrar('CREAR', 'permiso', $permiso->id, "Creó el permiso {$permiso->clave}");

        return redirect()->route('permisos.index')->with('ok', 'Permiso creado.');
    }

    public function edit(Permiso $permiso): View
    {
        return view('permisos.form', ['permiso' => $permiso]);
    }

    public function update(Request $request, Permiso $permiso): RedirectResponse
    {
        $this->guardar($request, $permiso);
        Auditoria::registrar('EDITAR', 'permiso', $permiso->id, "Editó el permiso {$permiso->clave}");

        return redirect()->route('permisos.index')->with('ok', 'Permiso actualizado.');
    }

    public function destroy(Permiso $permiso): RedirectResponse
    {
        if ($permiso->protegido) {
            return back()->with('error', 'Este permiso lo usa el sistema y no se puede eliminar.');
        }

        $permiso->delete();
        Auditoria::registrar('ELIMINAR', 'permiso', $permiso->id, "Eliminó el permiso {$permiso->clave}");

        return back()->with('ok', 'Permiso eliminado.');
    }

    private function guardar(Request $request, Permiso $permiso): void
    {
        $reglas = [
            'nombre' => ['required', 'string', 'max:120'],
            'modulo' => ['required', 'string', 'max:60'],
        ];

        if (! $permiso->protegido) {
            $reglas['clave'] = ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', Rule::unique('permisos', 'clave')->ignore($permiso->id)];
        }

        if (! $permiso->exists) {
            $reglas['ambito'] = ['required', Rule::in(array_keys(Rol::AMBITOS))];
        }

        $permiso->fill($request->validate($reglas, ['clave.regex' => 'Use el formato modulo.accion, en minúsculas.']))->save();
    }
}
