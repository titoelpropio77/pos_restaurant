<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProvisionRestaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Usuarios de los restaurantes. Un cajero pertenece a un restaurante; los partner pueden tener
 * varios y se asignan desde el formulario del restaurante.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('users.index', [
            'users' => User::with('restaurants', 'roles', 'pointsOfSale.branch')
                ->when($request->filled('restaurant'), fn ($q) => $q->whereHas('restaurants', fn ($q) => $q->whereKey($request->integer('restaurant'))))
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'ilike', '%'.$request->query('q').'%')
                    ->orWhere('username', 'ilike', '%'.$request->query('q').'%')
                    ->orWhere('email', 'ilike', '%'.$request->query('q').'%')))
                ->orderBy('username')
                ->paginate(25)
                ->withQueryString(),
            'restaurants' => Restaurant::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new User(['status' => 'ACTIVE']), $request->integer('restaurant') ?: null);
    }

    public function store(Request $request, ProvisionRestaurant $provision): RedirectResponse
    {
        $restaurantId = (int) $request->validate(['restaurant_id' => ['required', 'exists:restaurants,id']], [], ['restaurant_id' => 'restaurante'])['restaurant_id'];

        $user = new User;
        $this->save($request, $user, $restaurantId, $provision);

        AuditLog::register('CREATE', 'user', $user->id, "Creó el usuario {$user->username} ");

        return redirect()->route('users.index', ['restaurant' => $restaurantId])->with('ok', 'Usuario creado.');
    }

    public function edit(Request $request, User $user): View
    {
        return $this->form($user, $this->restaurantOf($user, $request->integer('restaurant')));
    }

    public function update(Request $request, User $user, ProvisionRestaurant $provision): RedirectResponse
    {
        $restaurantId = $this->restaurantOf($user, $request->integer('restaurant_id'));
        $this->save($request, $user, $restaurantId, $provision);
        AuditLog::register('UPDATE', 'user', $user->id, "Editó el usuario {$user->username}");

        return redirect()->route('users.index', ['restaurant' => $restaurantId])->with('ok', 'Usuario actualizado.');
    }

    public function status(User $user): RedirectResponse
    {
        $user->update(['status' => $user->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);
        AuditLog::register('STATUS', 'user', $user->id, "Dejó {$user->status} al usuario {$user->username}");

        return back()->with('ok', 'Estado del usuario actualizado.');
    }

    /** Un partner puede tener varios restaurantes: el punto de venta se edita para uno de ellos. */
    private function restaurantOf(User $user, int $requested): ?int
    {
        $restaurantIds = $user->restaurants()->pluck('restaurants.id')->map(fn ($id) => (int) $id)->all();

        return in_array($requested, $restaurantIds, true) ? $requested : ($restaurantIds[0] ?? null);
    }

    private function form(User $user, ?int $restaurantId): View
    {
        return view('users.form', [
            'user' => $user,
            'restaurantId' => $restaurantId,
            'pointOfSale' => $user->exists && $restaurantId ? $user->pointOfSaleIn($restaurantId) : null,
            'restaurants' => Restaurant::where('status', 'ACTIVE')->orWhere('id', $restaurantId)->orderBy('name')->pluck('name', 'id'),
            'roles' => Role::forGuard(Role::GUARD_RESTAURANT)->pluck('label', 'id'),
            'branches' => Branch::where('status', 'ACTIVE')->orderByDesc('is_main')->orderBy('name')->get(['id', 'name', 'restaurant_id']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

    private function save(Request $request, User $user, ?int $restaurantId, ProvisionRestaurant $provision): void
    {
        $request->merge([
            'username' => mb_strtolower(trim((string) $request->input('username'))),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // El correo es la credencial de ingreso: único en todo el sistema
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => [$user->exists ? 'nullable' : 'required', 'string', 'min:4', 'confirmed'],
            'position' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', Role::GUARD_RESTAURANT)],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('restaurant_id', $restaurantId)],
            'default_warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('branch_id', $request->integer('branch_id'))],
        ], [
            'username.regex' => 'El código sólo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
        ], [
            'password' => 'contraseña', 'email' => 'correo', 'username' => 'código', 'role_id' => 'rol', 'branch_id' => 'branch', 'default_warehouse_id' => 'almacén',
        ]);

        $role = Role::findOrFail($data['role_id']);

        DB::transaction(function () use ($user, $data, $request, $role, $restaurantId, $provision) {
            $user->fill([
                'name' => mb_strtoupper($data['name']),
                'username' => $data['username'],
                'email' => $data['email'],
                'position' => $data['position'] ?? null,
                'status' => $data['status'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles($role);
            $user->restaurants()->syncWithoutDetaching([$restaurantId]);

            $provision->assignPointOfSale($user, $restaurantId, [
                'branch_id' => $data['branch_id'],
                'default_warehouse_id' => $data['default_warehouse_id'] ?? null,
                'multi_branch_sales' => $request->boolean('multi_branch_sales'),
                'can_sell' => $request->boolean('can_sell'),
            ]);
        });
    }
}
