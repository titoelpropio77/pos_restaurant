<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Permisos. Los protegidos son los que el código consulta: su clave no cambia y no se eliminan.
 * Uno nuevo sólo tiene efecto cuando una ruta de las aplicaciones lo exige.
 */
class PermissionController extends Controller
{
    public function index(): View
    {
        return view('permissions.index', [
            'permissions' => Permission::withCount('roles')->orderBy('guard_name')->orderBy('module')->orderBy('id')->get()->groupBy('guard_name'),
        ]);
    }

    public function create(): View
    {
        return view('permissions.form', ['permission' => (new Permission)->forceFill(['guard_name' => Role::GUARD_RESTAURANT])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $permission = new Permission;
        $this->save($request, $permission);

        // Los roles totales tienen siempre todos los permisos de su guard
        Role::forGuard($permission->guard_name)->whereIn('name', Role::FULL)->first()?->givePermissionTo($permission);
        AuditLog::register('CREATE', 'permission', $permission->id, "Creó el permiso {$permission->name}");

        return redirect()->route('permissions.index')->with('ok', 'Permiso creado.');
    }

    public function edit(Permission $permission): View
    {
        return view('permissions.form', ['permission' => $permission]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $this->save($request, $permission);
        AuditLog::register('UPDATE', 'permission', $permission->id, "Editó el permiso {$permission->name}");

        return redirect()->route('permissions.index')->with('ok', 'Permiso actualizado.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->is_protected) {
            return back()->with('error', 'Este permiso lo usa el sistema y no se puede eliminar.');
        }

        $permission->delete();
        AuditLog::register('DELETE', 'permission', $permission->id, "Eliminó el permiso {$permission->name}");

        return back()->with('ok', 'Permiso eliminado.');
    }

    private function save(Request $request, Permission $permission): void
    {
        $rules = [
            'label' => ['required', 'string', 'max:120'],
            'module' => ['required', 'string', 'max:60'],
        ];

        if (! $permission->exists) {
            $rules['guard_name'] = ['required', Rule::in(array_keys(Role::GUARDS))];
        }

        if (! $permission->is_protected) {
            $rules['name'] = ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', Rule::unique('permissions', 'name')
                ->where('guard_name', $request->input('guard_name', $permission->guard_name))->ignore($permission->id)];
        }

        $data = $request->validate($rules, ['name.regex' => 'Use el formato modulo.accion, en minúsculas.'], ['name' => 'clave', 'label' => 'nombre', 'module' => 'módulo']);

        $permission->forceFill($data)->save();
    }
}
