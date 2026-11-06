<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Ciudad;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Services\AprovisionarRestaurante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SucursalController extends Controller
{
    public function index(Request $request): View
    {
        return view('sucursales.index', [
            'sucursales' => Sucursal::with('empresa', 'ciudad')
                ->when($request->filled('restaurante'), fn ($q) => $q->where('empresa_id', $request->integer('restaurante')))
                ->when($request->filled('q'), fn ($q) => $q->where('sucursales.nombre', 'ilike', '%'.$request->query('q').'%'))
                ->join('empresas', 'empresas.id', '=', 'sucursales.empresa_id')
                ->select('sucursales.*')
                ->orderBy('empresas.nombre')->orderByDesc('es_matriz')->orderBy('sucursales.nombre')
                ->paginate(25)
                ->withQueryString(),
            'restaurantes' => Empresa::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario(new Sucursal(['empresa_id' => $request->integer('restaurante') ?: null, 'estado' => 'ACTIVO']));
    }

    public function store(Request $request, AprovisionarRestaurante $aprovisionar): RedirectResponse
    {
        $datos = $this->validar($request) + $request->validate(['empresa_id' => ['required', 'exists:empresas,id']], [], ['empresa_id' => 'restaurante']);

        $empresa = Empresa::findOrFail($datos['empresa_id']);
        unset($datos['empresa_id']);

        $sucursal = $aprovisionar->agregarSucursal($empresa, $datos);
        Auditoria::registrar('CREAR', 'sucursal', $sucursal->id, "Creó la sucursal {$sucursal->nombre} de {$empresa->nombre}");

        return redirect()->route('restaurantes.show', $empresa)
            ->with('ok', 'Sucursal creada con su almacén principal. Falta cargar su dosificación.');
    }

    public function edit(Sucursal $sucursal): View
    {
        return $this->formulario($sucursal);
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update($this->validar($request));
        Auditoria::registrar('EDITAR', 'sucursal', $sucursal->id, "Editó la sucursal {$sucursal->nombre}");

        return redirect()->route('restaurantes.show', $sucursal->empresa_id)->with('ok', 'Sucursal actualizada.');
    }

    public function matriz(Sucursal $sucursal): RedirectResponse
    {
        if ($sucursal->estado !== 'ACTIVO') {
            return back()->with('error', 'Una sucursal inactiva no puede ser la casa matriz.');
        }

        // Primero se libera la matriz anterior: el índice único admite una sola por restaurante
        DB::transaction(function () use ($sucursal) {
            Sucursal::where('empresa_id', $sucursal->empresa_id)->where('es_matriz', true)->update(['es_matriz' => false]);
            $sucursal->update(['es_matriz' => true]);
        });

        Auditoria::registrar('EDITAR', 'sucursal', $sucursal->id, "Definió {$sucursal->nombre} como casa matriz");

        return back()->with('ok', "{$sucursal->nombre} ahora es la casa matriz.");
    }

    public function estado(Sucursal $sucursal): RedirectResponse
    {
        if ($sucursal->es_matriz) {
            return back()->with('error', 'La casa matriz no se puede dar de baja: defina otra primero.');
        }

        $sucursal->update(['estado' => $sucursal->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);
        Auditoria::registrar('ESTADO', 'sucursal', $sucursal->id, "Dejó {$sucursal->estado} la sucursal {$sucursal->nombre}");

        return back()->with('ok', 'Estado de la sucursal actualizado.');
    }

    private function formulario(Sucursal $sucursal): View
    {
        return view('sucursales.form', [
            'sucursal' => $sucursal,
            'restaurantes' => Empresa::orderBy('nombre')->pluck('nombre', 'id'),
            'ciudades' => Ciudad::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'ciudad_id' => ['nullable', 'exists:ciudades,id'],
            'ip_impresion' => ['nullable', 'string', 'max:60'],
        ]);

        return ['nombre' => mb_strtoupper($datos['nombre'])] + $datos;
    }
}
