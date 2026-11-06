<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('auditoria', [
            'registros' => Auditoria::with('administrador')
                ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->query('accion')))
                ->when($request->filled('entidad'), fn ($q) => $q->where('entidad', $request->query('entidad')))
                ->when($request->filled('q'), fn ($q) => $q->where('descripcion', 'ilike', '%'.$request->query('q').'%'))
                ->latest('id')
                ->paginate(40)
                ->withQueryString(),
            'acciones' => Auditoria::distinct()->orderBy('accion')->pluck('accion'),
            'entidades' => Auditoria::distinct()->orderBy('entidad')->pluck('entidad'),
        ]);
    }
}
