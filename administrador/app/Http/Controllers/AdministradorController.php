<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\Auditoria;
use App\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cuentas del sistema administrador. Siempre debe quedar un superadministrador activo.
 */
class AdministradorController extends Controller
{
    public function index(): View
    {
        return view('administradores.index', ['cuentas' => Administrador::with('rol')->orderBy('nombre')->paginate(25)]);
    }

    public function create(): View
    {
        return $this->formulario(new Administrador(['estado' => 'ACTIVO']));
    }

    public function store(Request $request): RedirectResponse
    {
        $cuenta = new Administrador;
        $this->guardar($request, $cuenta);
        Auditoria::registrar('CREAR', 'administrador', $cuenta->id, "Creó la cuenta {$cuenta->correo}");

        return redirect()->route('administradores.index')->with('ok', 'Cuenta creada.');
    }

    public function edit(Administrador $administrador): View
    {
        return $this->formulario($administrador);
    }

    public function update(Request $request, Administrador $administrador): RedirectResponse
    {
        $this->guardar($request, $administrador);
        Auditoria::registrar('EDITAR', 'administrador', $administrador->id, "Editó la cuenta {$administrador->correo}");

        return redirect()->route('administradores.index')->with('ok', 'Cuenta actualizada.');
    }

    public function destroy(Request $request, Administrador $administrador): RedirectResponse
    {
        if ($administrador->is($request->user())) {
            return back()->with('error', 'No puede darse de baja a sí mismo.');
        }

        if ($this->esUltimoSuperadministrador($administrador)) {
            return back()->with('error', 'Debe quedar al menos un superadministrador activo.');
        }

        $administrador->update(['estado' => 'INACTIVO']);
        Auditoria::registrar('ESTADO', 'administrador', $administrador->id, "Dio de baja la cuenta {$administrador->correo}");

        return back()->with('ok', 'Cuenta dada de baja.');
    }

    private function formulario(Administrador $cuenta): View
    {
        return view('administradores.form', [
            'cuenta' => $cuenta,
            'roles' => Rol::delAmbito(Rol::SISTEMA)->pluck('nombre', 'id'),
        ]);
    }

    private function guardar(Request $request, Administrador $cuenta): void
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('administradores', 'correo')->ignore($cuenta->id)],
            'password' => [$cuenta->exists ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'rol_id' => ['required', Rule::exists('roles', 'id')->where('ambito', Rol::SISTEMA)],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ], [], ['password' => 'contraseña', 'rol_id' => 'rol']);

        $sigueSiendoTotal = Rol::findOrFail($datos['rol_id'])->esTotal() && $datos['estado'] === 'ACTIVO';

        if ($cuenta->exists && ! $sigueSiendoTotal && $this->esUltimoSuperadministrador($cuenta)) {
            back()->withInput()->with('error', 'Debe quedar al menos un superadministrador activo.')->throwResponse();
        }

        $cuenta->fill([
            'nombre' => $datos['nombre'],
            'correo' => mb_strtolower($datos['correo']),
            'rol_id' => $datos['rol_id'],
            'estado' => $datos['estado'],
        ]);

        if (! empty($datos['password'])) {
            $cuenta->password = $datos['password'];
        }

        $cuenta->save();
    }

    private function esUltimoSuperadministrador(Administrador $cuenta): bool
    {
        return $cuenta->estaActivo() && $cuenta->esSuperadministrador()
            && ! Administrador::where('estado', 'ACTIVO')->whereKeyNot($cuenta->id)->where('rol_id', $cuenta->rol_id)->exists();
    }
}
