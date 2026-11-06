<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\City;
use App\Models\Restaurant;
use App\Services\ProvisionRestaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        return view('branches.index', [
            'branches' => Branch::with('restaurant', 'city')
                ->when($request->filled('restaurant'), fn ($q) => $q->where('restaurant_id', $request->integer('restaurant')))
                ->when($request->filled('q'), fn ($q) => $q->where('branches.name', 'ilike', '%'.$request->query('q').'%'))
                ->join('restaurants', 'restaurants.id', '=', 'branches.restaurant_id')
                ->select('branches.*')
                ->orderBy('restaurants.name')->orderByDesc('is_main')->orderBy('branches.name')
                ->paginate(25)
                ->withQueryString(),
            'restaurants' => Restaurant::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Branch(['restaurant_id' => $request->integer('restaurant') ?: null, 'status' => 'ACTIVE']));
    }

    public function store(Request $request, ProvisionRestaurant $provision): RedirectResponse
    {
        $data = $this->validate($request) + $request->validate(['restaurant_id' => ['required', 'exists:restaurants,id']], [], ['restaurant_id' => 'restaurant']);

        $restaurant = Restaurant::findOrFail($data['restaurant_id']);
        unset($data['restaurant_id']);

        $branch = $provision->addBranch($restaurant, $data);
        AuditLog::register('CREATE', 'branch', $branch->id, "Creó la sucursal {$branch->name} de {$restaurant->name}");

        return redirect()->route('restaurants.show', $restaurant)
            ->with('ok', 'Sucursal creada con su almacén principal. Falta cargar su dosificación.');
    }

    public function edit(Branch $branch): View
    {
        return $this->form($branch);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validate($request));
        AuditLog::register('UPDATE', 'branch', $branch->id, "Editó la sucursal {$branch->name}");

        return redirect()->route('restaurants.show', $branch->restaurant_id)->with('ok', 'Sucursal actualizada.');
    }

    public function main(Branch $branch): RedirectResponse
    {
        if ($branch->status !== 'ACTIVE') {
            return back()->with('error', 'Una sucursal inactiva no puede ser la casa matriz.');
        }

        // Primero se libera la matriz anterior: el índice único admite una sola por restaurante
        DB::transaction(function () use ($branch) {
            Branch::where('restaurant_id', $branch->restaurant_id)->where('is_main', true)->update(['is_main' => false]);
            $branch->update(['is_main' => true]);
        });

        AuditLog::register('UPDATE', 'branch', $branch->id, "Definió {$branch->name} como casa matriz");

        return back()->with('ok', "{$branch->name} ahora es la casa matriz.");
    }

    public function status(Branch $branch): RedirectResponse
    {
        if ($branch->is_main) {
            return back()->with('error', 'La casa matriz no se puede dar de baja: defina otra primero.');
        }

        $branch->update(['status' => $branch->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);
        AuditLog::register('STATUS', 'branch', $branch->id, "Dejó {$branch->status} la sucursal {$branch->name}");

        return back()->with('ok', 'Estado de la sucursal actualizado.');
    }

    private function form(Branch $branch): View
    {
        return view('branches.form', [
            'branch' => $branch,
            'restaurants' => Restaurant::orderBy('name')->pluck('name', 'id'),
            'cities' => City::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    private function validate(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:250'],
            'phone' => ['nullable', 'string', 'max:20'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'print_ip' => ['nullable', 'string', 'max:60'],
        ]);

        return ['name' => mb_strtoupper($data['name'])] + $data;
    }
}
