<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sales = DB::table('sales')
            ->where('status', 'ACTIVE')
            ->where('sale_date', '>=', today()->subDays(29)->toDateString())
            ->groupBy('restaurant_id')
            ->selectRaw('restaurant_id, count(*) as cantidad, sum(invoice_total) as total')
            ->get()
            ->keyBy('restaurant_id');

        return view('dashboard', [
            'stats' => [
                ['Restaurantes activos', Restaurant::where('status', 'ACTIVE')->count(), 'fa-cutlery'],
                ['Sucursales activas', Branch::where('status', 'ACTIVE')->count(), 'fa-building-o'],
                ['Usuarios activos', User::where('status', 'ACTIVE')->count(), 'fa-users'],
                ['Cuentas del sistema', Admin::where('status', 'ACTIVE')->count(), 'fa-shield'],
            ],
            'restaurants' => Restaurant::withCount(['branches', 'users'])->orderBy('name')->limit(10)->get(),
            'sales' => $sales,
        ]);
    }
}
