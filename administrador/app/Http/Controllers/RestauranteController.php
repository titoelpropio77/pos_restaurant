<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Ciudad;
use App\Models\Empresa;
use App\Services\AprovisionarRestaurante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Restaurantes (tabla empresas). No se eliminan: guardan ventas y datos fiscales, así que
 * se dan de baja. Un restaurante inactivo pierde el acceso al POS en el acto.
 */
class RestauranteController extends Controller
{
    public function index(Request $request): View
    {
        return view('restaurantes.index', [
            'restaurantes' => Empresa::with('matriz')->withCount(['sucursales', 'usuarios'])
                ->when($request->filled('q'), fn ($q) => $q->where('nombre', 'ilike', '%'.$request->query('q').'%'))
                ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
                ->orderBy('nombre')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Empresa $restaurante): View
    {
        return view('restaurantes.show', [
            'restaurante' => $restaurante->load(['sucursales' => fn ($q) => $q->orderByDesc('es_matriz')->orderBy('nombre'), 'usuarios.rol']),
        ]);
    }

    public function create(): View
    {
        return view('restaurantes.form', [
            'restaurante' => new Empresa(['estado' => 'ACTIVO', 'opciones' => Empresa::opcionesIniciales()]),
            'ciudades' => $this->ciudades(),
        ]);
    }

    public function store(Request $request, AprovisionarRestaurante $aprovisionar): RedirectResponse
    {
        $request->merge(['dueno_usuario' => mb_strtoupper(trim((string) $request->input('dueno_usuario')))]);

        $datos = $this->validar($request) + $request->validate([
            'matriz_nombre' => ['required', 'string', 'max:100'],
            'matriz_direccion' => ['nullable', 'string', 'max:250'],
            'matriz_telefono' => ['nullable', 'string', 'max:20'],
            'matriz_ciudad_id' => ['nullable', 'exists:ciudades,id'],
            'dueno_nombres' => ['required', 'string', 'max:100'],
            'dueno_usuario' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_.-]+$/', 'unique:usuarios,usuario'],
            'dueno_password' => ['required', 'string', 'min:4', 'confirmed'],
        ], [
            'dueno_usuario.regex' => 'El usuario sólo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
        ], [
            'matriz_nombre' => 'nombre de la casa matriz',
            'dueno_nombres' => 'nombre del dueño',
            'dueno_usuario' => 'usuario',
            'dueno_password' => 'contraseña',
        ]);

        $restaurante = $aprovisionar->crear(
            $this->datosRestaurante($datos, $request),
            [
                'nombre' => mb_strtoupper($datos['matriz_nombre']),
                'direccion' => $datos['matriz_direccion'] ?? null,
                'telefono' => $datos['matriz_telefono'] ?? null,
                'ciudad_id' => $datos['matriz_ciudad_id'] ?? null,
            ],
            ['nombres' => $datos['dueno_nombres'], 'usuario' => $datos['dueno_usuario'], 'password' => $datos['dueno_password']],
        );

        Auditoria::registrar('CREAR', 'restaurante', $restaurante->id, "Creó el restaurante {$restaurante->nombre} con el usuario {$datos['dueno_usuario']}");

        return redirect()->route('restaurantes.show', $restaurante)
            ->with('ok', 'Restaurante creado. El dueño debe cargar su dosificación en Facturación SIN antes de vender.');
    }

    public function edit(Empresa $restaurante): View
    {
        return view('restaurantes.form', ['restaurante' => $restaurante, 'ciudades' => $this->ciudades()]);
    }

    public function update(Request $request, Empresa $restaurante): RedirectResponse
    {
        $datos = $this->validar($request);
        $restaurante->update($this->datosRestaurante($datos, $request));
        Auditoria::registrar('EDITAR', 'restaurante', $restaurante->id, "Editó el restaurante {$restaurante->nombre}");

        return redirect()->route('restaurantes.show', $restaurante)->with('ok', 'Restaurante actualizado.');
    }

    public function estado(Empresa $restaurante): RedirectResponse
    {
        $restaurante->update(['estado' => $restaurante->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);
        Auditoria::registrar('ESTADO', 'restaurante', $restaurante->id, "Dejó {$restaurante->estado} al restaurante {$restaurante->nombre}");

        return back()->with('ok', $restaurante->estado === 'ACTIVO' ? 'Restaurante reactivado.' : 'Restaurante dado de baja: sus usuarios ya no pueden ingresar.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:20'],
            'propietario' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'actividad' => ['nullable', 'string', 'max:255'],
            'web' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);
    }

    private function datosRestaurante(array $datos, Request $request): array
    {
        $opciones = collect(Empresa::OPCIONES)->keys()
            ->mapWithKeys(fn ($clave) => [$clave => $request->boolean("opciones.{$clave}")])
            ->all();

        return ['nombre' => mb_strtoupper($datos['nombre']), 'opciones' => $opciones]
            + collect($datos)->only(['nit', 'propietario', 'correo', 'telefono', 'direccion', 'actividad', 'web', 'estado'])->all();
    }

    private function ciudades()
    {
        return Ciudad::orderBy('nombre')->pluck('nombre', 'id');
    }
}
