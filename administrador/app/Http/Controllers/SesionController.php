<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ingreso de las cuentas del sistema: separado del login de los restaurantes.
 */
class SesionController extends Controller
{
    public function create(): View
    {
        return view('sesion.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate(['correo' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt(['correo' => mb_strtolower($datos['correo']), 'password' => $datos['password'], 'estado' => 'ACTIVO'], $request->boolean('recordar'))) {
            return back()->withInput($request->only('correo'))->with('error', 'Correo o contraseña incorrectos.');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['ultimo_ingreso' => now()])->save();
        Auditoria::registrar('INGRESO', 'sesion', $request->user()->id, $request->user()->correo.' ingresó al sistema');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
