<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $ventas = DB::table('ventas')
            ->where('estado', 'ACTIVO')
            ->where('f_venta', '>=', today()->subDays(29)->toDateString())
            ->groupBy('empresa_id')
            ->selectRaw('empresa_id, count(*) as cantidad, sum(total_fact) as total')
            ->get()
            ->keyBy('empresa_id');

        return view('dashboard', [
            'cifras' => [
                ['Restaurantes activos', Empresa::where('estado', 'ACTIVO')->count(), 'fa-cutlery'],
                ['Sucursales activas', Sucursal::where('estado', 'ACTIVO')->count(), 'fa-building-o'],
                ['Usuarios activos', Usuario::where('estado', 'ACTIVO')->count(), 'fa-users'],
                ['Cuentas del sistema', Administrador::where('estado', 'ACTIVO')->count(), 'fa-shield'],
            ],
            'restaurantes' => Empresa::withCount(['sucursales', 'usuarios'])->orderBy('nombre')->limit(10)->get(),
            'ventas' => $ventas,
        ]);
    }
}
