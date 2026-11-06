<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cuentas del sistema administrador. Siempre debe quedar un superadministrador activo.
 */
class AdminController extends Controller
{
    public function index(): View
    {
        return view('admins.index', ['accounts' => Admin::with('roles')->orderBy('name')->paginate(25)]);
    }

    public function create(): View
    {
        return $this->form(new Admin(['status' => 'ACTIVE']));
    }

    public function store(Request $request): RedirectResponse
    {
        $account = new Admin;
        $this->save($request, $account);
        AuditLog::register('CREATE', 'admin', $account->id, "Creó la cuenta {$account->email}");

        return redirect()->route('admins.index')->with('ok', 'Cuenta creada.');
    }

    public function edit(Admin $admin): View
    {
        return $this->form($admin);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->save($request, $admin);
        AuditLog::register('UPDATE', 'admin', $admin->id, "Editó la cuenta {$admin->email}");

        return redirect()->route('admins.index')->with('ok', 'Cuenta actualizada.');
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->is($request->user())) {
            return back()->with('error', 'No puede darse de baja a sí mismo.');
        }

        if ($this->isLastSuperAdmin($admin)) {
            return back()->with('error', 'Debe quedar al menos un superadministrador activo.');
        }

        $admin->update(['status' => 'INACTIVE']);
        AuditLog::register('STATUS', 'admin', $admin->id, "Dio de baja la cuenta {$admin->email}");

        return back()->with('ok', 'Cuenta dada de baja.');
    }

    private function form(Admin $account): View
    {
        return view('admins.form', [
            'account' => $account,
            'roles' => Role::forGuard(Role::GUARD_ADMIN)->pluck('label', 'id'),
        ]);
    }

    private function save(Request $request, Admin $account): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('admins', 'email')->ignore($account->id)],
            'password' => [$account->exists ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', Role::GUARD_ADMIN)],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ], [], ['password' => 'contraseña', 'role_id' => 'rol']);

        $role = Role::findOrFail($data['role_id']);
        $keepsBeingTotal = $role->isFull() && $data['status'] === 'ACTIVE';

        if ($account->exists && ! $keepsBeingTotal && $this->isLastSuperAdmin($account)) {
            back()->withInput()->with('error', 'Debe quedar al menos un superadministrador activo.')->throwResponse();
        }

        $account->fill([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $account->password = $data['password'];
        }

        $account->save();
        $account->syncRoles($role);
    }

    private function isLastSuperAdmin(Admin $account): bool
    {
        return $account->isActive() && $account->isSuperAdmin()
            && ! Admin::role(Role::SUPER_ADMIN)->where('status', 'ACTIVE')->whereKeyNot($account->id)->exists();
    }
}
