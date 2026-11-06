<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\AprovisionarRestaurante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Usuarios de los restaurantes. Un usuario no cambia de restaurante: sus ventas e historial
 * pertenecen al restaurante donde se creó.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('usuarios.index', [
            'usuarios' => Usuario::with('empresa', 'rol', 'puntoVenta.sucursal')
                ->when($request->filled('restaurante'), fn ($q) => $q->where('empresa_id', $request->integer('restaurante')))
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                    ->where('nombres', 'ilike', '%'.$request->query('q').'%')
                    ->orWhere('usuario', 'ilike', '%'.$request->query('q').'%')))
                ->orderBy('usuario')
                ->paginate(25)
                ->withQueryString(),
            'restaurantes' => Empresa::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario(new Usuario(['estado' => 'ACTIVO']), $request->integer('restaurante') ?: null);
    }

    public function store(Request $request, AprovisionarRestaurante $aprovisionar): RedirectResponse
    {
        $empresaId = (int) $request->validate(['empresa_id' => ['required', 'exists:empresas,id']], [], ['empresa_id' => 'restaurante'])['empresa_id'];

        $usuario = new Usuario;
        $usuario->empresa_id = $empresaId;
        $this->guardar($request, $usuario, $aprovisionar);

        Auditoria::registrar('CREAR', 'usuario', $usuario->id, "Creó el usuario {$usuario->usuario} de {$usuario->empresa->nombre}");

        return redirect()->route('usuarios.index', ['restaurante' => $empresaId])->with('ok', 'Usuario creado.');
    }

    public function edit(Usuario $usuario): View
    {
        return $this->formulario($usuario->load('puntoVenta'), $usuario->empresa_id);
    }

    public function update(Request $request, Usuario $usuario, AprovisionarRestaurante $aprovisionar): RedirectResponse
    {
        $this->guardar($request, $usuario, $aprovisionar);
        Auditoria::registrar('EDITAR', 'usuario', $usuario->id, "Editó el usuario {$usuario->usuario}");

        return redirect()->route('usuarios.index', ['restaurante' => $usuario->empresa_id])->with('ok', 'Usuario actualizado.');
    }

    public function estado(Usuario $usuario): RedirectResponse
    {
        $usuario->update(['estado' => $usuario->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);
        Auditoria::registrar('ESTADO', 'usuario', $usuario->id, "Dejó {$usuario->estado} al usuario {$usuario->usuario}");

        return back()->with('ok', 'Estado del usuario actualizado.');
    }

    private function formulario(Usuario $usuario, ?int $empresaId): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'empresaId' => $empresaId,
            'restaurantes' => Empresa::where('estado', 'ACTIVO')->orWhere('id', $empresaId)->orderBy('nombre')->pluck('nombre', 'id'),
            'roles' => Rol::delAmbito(Rol::RESTAURANTE)->pluck('nombre', 'id'),
            'sucursales' => Sucursal::where('estado', 'ACTIVO')->orderByDesc('es_matriz')->orderBy('nombre')->get(['id', 'nombre', 'empresa_id']),
            'almacenes' => Almacen::orderBy('nombre')->get(['id', 'nombre', 'sucursal_id']),
        ]);
    }

    private function guardar(Request $request, Usuario $usuario, AprovisionarRestaurante $aprovisionar): void
    {
        $request->merge(['usuario' => mb_strtoupper(trim((string) $request->input('usuario')))]);

        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            // El login es único en todo el sistema porque identifica al restaurante al ingresar
            'usuario' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_.-]+$/', Rule::unique('usuarios', 'usuario')->ignore($usuario->id)],
            'password' => [$usuario->exists ? 'nullable' : 'required', 'string', 'min:4', 'confirmed'],
            'cargo' => ['nullable', 'string', 'max:30'],
            'rol_id' => ['required', Rule::exists('roles', 'id')->where('ambito', Rol::RESTAURANTE)],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'sucursal_id' => ['required', Rule::exists('sucursales', 'id')->where('empresa_id', $usuario->empresa_id)],
            'almacen_por_defecto_id' => ['nullable', Rule::exists('almacenes', 'id')->where('sucursal_id', $request->integer('sucursal_id'))],
        ], [
            'usuario.regex' => 'El usuario sólo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
        ], [
            'password' => 'contraseña', 'rol_id' => 'rol', 'sucursal_id' => 'sucursal', 'almacen_por_defecto_id' => 'almacén',
        ]);

        $rol = Rol::with('permisos')->findOrFail($datos['rol_id']);

        DB::transaction(function () use ($usuario, $datos, $request, $rol, $aprovisionar) {
            $usuario->fill([
                'nombres' => mb_strtoupper($datos['nombres']),
                'usuario' => $datos['usuario'],
                'cargo' => $datos['cargo'] ?? null,
                'estado' => $datos['estado'],
                'rol_id' => $rol->id,
                'nivel' => Usuario::nivelDe($rol),
            ]);

            if (! empty($datos['password'])) {
                $usuario->password = $datos['password'];
            }

            $usuario->save();

            $aprovisionar->asignarPuntoVenta($usuario, [
                'sucursal_id' => $datos['sucursal_id'],
                'almacen_por_defecto_id' => $datos['almacen_por_defecto_id'] ?? null,
                'venta_multialmacen' => $request->boolean('venta_multialmacen'),
                'puede_vender' => $request->boolean('puede_vender'),
            ]);
        });
    }
}
