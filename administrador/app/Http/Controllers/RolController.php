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
 * Roles de las cuentas del sistema y de los usuarios de restaurante.
 *
 * Los protegidos no se renombran ni eliminan. Superadministrador y Dueño conservan
 * siempre todos los permisos de su ámbito.
 */
class RolController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Rol::withCount(['permisos', 'administradores', 'usuarios'])->orderBy('ambito')->orderBy('id')->get()->groupBy('ambito'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario(new Rol(['ambito' => $request->query('ambito') === Rol::SISTEMA ? Rol::SISTEMA : Rol::RESTAURANTE]));
    }

    public function store(Request $request): RedirectResponse
    {
        $rol = new Rol;
        $this->guardar($request, $rol);
        Auditoria::registrar('CREAR', 'rol', $rol->id, "Creó el rol {$rol->nombre}");

        return redirect()->route('roles.index')->with('ok', 'Rol creado.');
    }

    public function edit(Rol $rol): View
    {
        return $this->formulario($rol->load('permisos'));
    }

    public function update(Request $request, Rol $rol): RedirectResponse
    {
        $this->guardar($request, $rol);
        Auditoria::registrar('EDITAR', 'rol', $rol->id, "Editó el rol {$rol->nombre}");

        return redirect()->route('roles.index')->with('ok', 'Rol actualizado.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        if ($rol->protegido) {
            return back()->with('error', 'Este rol es del sistema y no se puede eliminar.');
        }

        if ($rol->administradores()->exists() || $rol->usuarios()->exists()) {
            return back()->with('error', 'El rol tiene cuentas asignadas: reasígnelas antes de eliminarlo.');
        }

        $rol->delete();
        Auditoria::registrar('ELIMINAR', 'rol', $rol->id, "Eliminó el rol {$rol->nombre}");

        return back()->with('ok', 'Rol eliminado.');
    }

    private function formulario(Rol $rol): View
    {
        return view('roles.form', [
            'rol' => $rol,
            'permisos' => Permiso::orderBy('modulo')->orderBy('id')->get()->groupBy('ambito'),
        ]);
    }

    private function guardar(Request $request, Rol $rol): void
    {
        $ambito = $rol->exists ? $rol->ambito : $request->validate(['ambito' => ['required', Rule::in(array_keys(Rol::AMBITOS))]])['ambito'];

        $datos = $request->validate([
            'nombre' => [$rol->protegido ? 'nullable' : 'required', 'string', 'max:60', Rule::unique('roles', 'nombre')->where('ambito', $ambito)->ignore($rol->id)],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'permisos' => ['array'],
            'permisos.*' => [Rule::exists('permisos', 'id')->where('ambito', $ambito)],
        ]);

        $rol->fill(['descripcion' => $datos['descripcion'] ?? null, 'ambito' => $ambito]);

        if (! $rol->protegido) {
            $rol->nombre = $datos['nombre'];
        }

        $rol->save();

        if (! $rol->esTotal()) {
            $rol->permisos()->sync($datos['permisos'] ?? []);
        }
    }
}
