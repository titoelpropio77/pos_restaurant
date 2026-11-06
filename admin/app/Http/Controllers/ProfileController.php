<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile', ['account' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $account = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('admins', 'email')->ignore($account->id)],
            'password_current' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [], ['password_current' => 'contraseña actual', 'password' => 'contraseña nueva']);

        $account->fill(['name' => $data['name'], 'email' => mb_strtolower($data['email'])]);

        if (! empty($data['password'])) {
            $account->password = $data['password'];
        }

        $account->save();
        AuditLog::register('UPDATE', 'profile', $account->id, 'Actualizó sus datos de cuenta');

        return back()->with('ok', 'Sus datos fueron actualizados.');
    }
}
