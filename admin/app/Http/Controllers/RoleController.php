<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Roles de las cuentas del sistema y de los usuarios de restaurante.
 *
 * Los protegidos no se renombran ni eliminan. Superadministrador y Dueño conservan
 * siempre todos los permisos de su guard.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::withCount(['permissions', 'admins', 'restaurantUsers'])->orderBy('guard_name')->orderBy('id')->get()->groupBy('guard_name'),
        ]);
    }

    public function create(Request $request): View
    {
        $guard = $request->query('guard') === Role::GUARD_ADMIN ? Role::GUARD_ADMIN : Role::GUARD_RESTAURANT;

        return $this->form((new Role)->forceFill(['guard_name' => $guard]));
    }

    public function store(Request $request): RedirectResponse
    {
        $role = new Role;
        $this->save($request, $role);
        AuditLog::register('CREATE', 'role', $role->id, "Creó el rol {$role->label}");

        return redirect()->route('roles.index')->with('ok', 'Rol creado.');
    }

    public function edit(Role $role): View
    {
        return $this->form($role->load('permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->save($request, $role);
        AuditLog::register('UPDATE', 'role', $role->id, "Editó el rol {$role->label}");

        return redirect()->route('roles.index')->with('ok', 'Rol actualizado.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_protected) {
            return back()->with('error', 'Este rol es del sistema y no se puede eliminar.');
        }

        if ($role->admins()->exists() || $role->restaurantUsers()->exists()) {
            return back()->with('error', 'El rol tiene cuentas asignadas: reasígnelas antes de eliminarlo.');
        }

        $role->delete();
        AuditLog::register('DELETE', 'role', $role->id, "Eliminó el rol {$role->label}");

        return back()->with('ok', 'Rol eliminado.');
    }

    private function form(Role $role): View
    {
        return view('roles.form', [
            'role' => $role,
            'permissions' => Permission::orderBy('module')->orderBy('id')->get()->groupBy('guard_name'),
        ]);
    }

    private function save(Request $request, Role $role): void
    {
        $guard = $role->exists ? $role->guard_name : $request->validate(['guard_name' => ['required', Rule::in(array_keys(Role::GUARDS))]])['guard_name'];

        $data = $request->validate([
            'label' => [$role->is_protected ? 'nullable' : 'required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:200'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::exists('permissions', 'id')->where('guard_name', $guard)],
        ], [], ['label' => 'nombre']);

        $role->description = $data['description'] ?? null;

        if (! $role->is_protected) {
            $name = Str::slug($data['label']);

            validator(['label' => $name], ['label' => [Rule::unique('roles', 'name')->where('guard_name', $guard)->ignore($role->id)]], [], ['label' => 'nombre'])->validate();

            $role->forceFill(['name' => $name, 'label' => $data['label'], 'guard_name' => $guard]);
        }

        $role->save();

        if (! $role->isFull()) {
            $role->syncPermissions(Permission::whereIn('id', $data['permissions'] ?? [])->get());
        }
    }
}
