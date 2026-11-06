<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil', ['cuenta' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $cuenta = $request->user();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('administradores', 'correo')->ignore($cuenta->id)],
            'password_actual' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [], ['password_actual' => 'contraseña actual', 'password' => 'contraseña nueva']);

        $cuenta->fill(['nombre' => $datos['nombre'], 'correo' => mb_strtolower($datos['correo'])]);

        if (! empty($datos['password'])) {
            $cuenta->password = $datos['password'];
        }

        $cuenta->save();
        Auditoria::registrar('EDITAR', 'perfil', $cuenta->id, 'Actualizó sus datos de cuenta');

        return back()->with('ok', 'Sus datos fueron actualizados.');
    }
}
