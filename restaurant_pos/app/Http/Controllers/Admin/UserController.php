<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Usuarios del sistema y su punto de venta (sucursal y almacén donde venden).
 * Legacy: frmUsuario.php / usuarioController.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::with('pointOfSale.branch', 'roles')
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'ilike', '%'.$request->query('q').'%')
                    ->orWhere('username', 'ilike', '%'.$request->query('q').'%')
                    ->orWhere('email', 'ilike', '%'.$request->query('q').'%')))
                ->orderBy('username')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new User(['status' => 'ACTIVE']));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new User);

        return redirect()->route('admin.users.index')->with('ok', 'Usuario creado.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureCanManage($request->user(), $user);

        return $this->form($user->load('pointOfSale'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($request->user(), $user);
        $this->save($request, $user);

        return redirect()->route('admin.users.index')->with('ok', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'No puede darse de baja a sí mismo.');
        }

        $this->ensureCanManage($request->user(), $user);

        $user->update(['status' => 'INACTIVE']);

        return back()->with('ok', "El usuario {$user->username} fue dado de baja.");
    }

    private function form(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->rolesAssignable(request()->user(), $user)->pluck('label', 'id'),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::with('branch')->orderBy('name')->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->name.' ('.$a->branch?->name.')']),
        ]);
    }

    /** Los partner comparten restaurante: editar a otro permitiría cambiarle la contraseña y tomar su cuenta. */
    private function ensureCanManage(User $actor, User $target): void
    {
        abort_if($target->hasRole('partner') && ! $target->is($actor), 403, 'Los partner se administran desde el sistema administrador.');
    }

    /**
     * Roles que el usuario puede otorgar: nunca uno con más permisos que el suyo.
     * Los partner se crean desde la app admin: aquí sólo se conserva el rol de quien ya lo tiene.
     */
    private function rolesAssignable(User $actor, User $edited)
    {
        return Role::forRestaurants()->with('permissions')->orderBy('id')->get()->filter(
            fn (Role $role) => $edited->hasRole($role)
                || ($role->name !== 'partner' && $role->permissions->every(fn ($permission) => $actor->can($permission->name))),
        );
    }

    private function save(Request $request, User $user): void
    {
        $request->merge([
            'username' => User::normalize((string) $request->input('username')),
            'email' => User::normalize((string) $request->input('email')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [$user->exists ? 'nullable' : 'required', 'string', 'min:4', 'confirmed'],
            'position' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', Rule::in($this->rolesAssignable($request->user(), $user)->pluck('id'))],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'branch_id' => ['required', Tenant::exists('branches')],
            'default_warehouse_id' => ['nullable', Tenant::exists('warehouses')],
            'multi_branch_sales' => ['boolean'],
            'can_sell' => ['boolean'],
        ], [
            'username.regex' => 'El código sólo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
        ], [
            'password' => 'contraseña',
            'email' => 'correo',
            'username' => 'código',
            'role_id' => 'rol',
            'branch_id' => 'branch',
            'default_warehouse_id' => 'almacén',
        ]);

        $role = Role::with('permissions')->findOrFail($data['role_id']);
        $manages = $role->permissions->contains('name', 'panel.access');

        if ($user->is($request->user()) && (! $manages || $data['status'] !== 'ACTIVE')) {
            back()->with('error', 'No puede quitarse a sí mismo el acceso de administrador.')->throwResponse();
        }

        DB::transaction(function () use ($user, $data, $request, $role) {
            $user->fill([
                'name' => mb_strtoupper($data['name']),
                'username' => $data['username'],
                'email' => $data['email'],
                'position' => $data['position'] ?? null,
                'status' => $data['status'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles($role);

            $user->pointOfSale()->updateOrCreate([], [
                'branch_id' => $data['branch_id'],
                'default_warehouse_id' => $data['default_warehouse_id'] ?? null,
                'multi_branch_sales' => $request->boolean('multi_branch_sales'),
                'can_sell' => $request->boolean('can_sell'),
                'start_date' => $user->pointOfSale?->start_date ?? today(),
            ]);
        });
    }
}
