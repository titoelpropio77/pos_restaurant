<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Support\Restaurante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Usuarios del sistema y su punto de venta (sucursal y almacén donde venden).
 * Legacy: frmUsuario.php / usuarioController.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => Usuario::with('puntoVenta.sucursal', 'rol')
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                    ->where('nombres', 'ilike', '%'.$request->query('q').'%')
                    ->orWhere('usuario', 'ilike', '%'.$request->query('q').'%')))
                ->orderBy('usuario')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Usuario(['estado' => 'ACTIVO']));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guardar($request, new Usuario);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(Usuario $usuario): View
    {
        return $this->formulario($usuario->load('puntoVenta'));
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $this->guardar($request, $usuario);

        return redirect()->route('admin.usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function destroy(Request $request, Usuario $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puede darse de baja a sí mismo.');
        }

        $usuario->update(['estado' => 'INACTIVO']);

        return back()->with('ok', "El usuario {$usuario->usuario} fue dado de baja.");
    }

    private function formulario(Usuario $usuario): View
    {
        return view('admin.usuarios.form', [
            'usuario' => $usuario,
            'roles' => $this->rolesAsignables(request()->user(), $usuario)->pluck('nombre', 'id'),
            'sucursales' => Sucursal::orderBy('nombre')->pluck('nombre', 'id'),
            'almacenes' => Almacen::with('sucursal')->orderBy('nombre')->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->nombre.' ('.$a->sucursal?->nombre.')']),
        ]);
    }

    /**
     * Roles que el usuario puede otorgar: nunca uno con más permisos que el suyo.
     * El rol que ya tiene el usuario editado se mantiene disponible.
     */
    private function rolesAsignables(Usuario $actor, Usuario $editado)
    {
        return Rol::delRestaurante()->with('permisos')->orderBy('id')->get()->filter(
            fn (Rol $rol) => $rol->id === $editado->rol_id
                || collect($rol->claves())->every(fn ($clave) => $actor->puede($clave)),
        );
    }

    private function guardar(Request $request, Usuario $usuario): void
    {
        $request->merge(['usuario' => mb_strtoupper(trim((string) $request->input('usuario')))]);

        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'usuario' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_.-]+$/', Rule::unique('usuarios', 'usuario')->ignore($usuario->id)],
            'password' => [$usuario->exists ? 'nullable' : 'required', 'string', 'min:4', 'confirmed'],
            'cargo' => ['nullable', 'string', 'max:30'],
            'rol_id' => ['required', Rule::in($this->rolesAsignables($request->user(), $usuario)->pluck('id'))],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
            'sucursal_id' => ['required', Restaurante::existe('sucursales')],
            'almacen_por_defecto_id' => ['nullable', Restaurante::existe('almacenes')],
            'venta_multialmacen' => ['boolean'],
            'puede_vender' => ['boolean'],
        ], [
            'usuario.regex' => 'El usuario sólo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
        ], [
            'password' => 'contraseña',
            'rol_id' => 'rol',
            'sucursal_id' => 'sucursal',
            'almacen_por_defecto_id' => 'almacén',
        ]);

        $rol = Rol::with('permisos')->findOrFail($datos['rol_id']);
        $administra = in_array('panel.acceder', $rol->claves(), true);

        if ($usuario->is($request->user()) && (! $administra || $datos['estado'] !== 'ACTIVO')) {
            back()->with('error', 'No puede quitarse a sí mismo el acceso de administrador.')->throwResponse();
        }

        DB::transaction(function () use ($usuario, $datos, $request, $rol, $administra) {
            $usuario->fill([
                'nombres' => mb_strtoupper($datos['nombres']),
                'usuario' => $datos['usuario'],
                'cargo' => $datos['cargo'] ?? null,
                'rol_id' => $rol->id,
                'nivel' => $administra ? Usuario::NIVEL_ADMIN : Usuario::NIVEL_CAJERO,
                'estado' => $datos['estado'],
            ]);

            // Igual que el sistema original, la contraseña no distingue mayúsculas
            if (! empty($datos['password'])) {
                $usuario->password = mb_strtoupper($datos['password']);
            }

            $usuario->save();

            $usuario->puntoVenta()->updateOrCreate([], [
                'sucursal_id' => $datos['sucursal_id'],
                'almacen_por_defecto_id' => $datos['almacen_por_defecto_id'] ?? null,
                'venta_multialmacen' => $request->boolean('venta_multialmacen'),
                'puede_vender' => $request->boolean('puede_vender'),
                'fecha_inicio' => $usuario->puntoVenta?->fecha_inicio ?? today(),
            ]);
        });
    }
}
