<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\City;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\ProvisionRestaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Restaurantes (tabla empresas). No se eliminan: guardan ventas y datos fiscales, así que
 * se dan de baja. Un restaurante inactivo pierde el acceso al POS en el acto.
 */
class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        return view('restaurants.index', [
            'restaurants' => Restaurant::with('main', 'partners')->withCount(['branches', 'users'])
                ->when($request->filled('q'), fn ($q) => $q->where('name', 'ilike', '%'.$request->query('q').'%'))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Restaurant $restaurant): View
    {
        return view('restaurants.show', [
            'restaurant' => $restaurant->load(['branches' => fn ($q) => $q->orderByDesc('is_main')->orderBy('name'), 'users.roles']),
        ]);
    }

    public function create(): View
    {
        return view('restaurants.form', [
            'restaurant' => new Restaurant(['status' => 'ACTIVE', 'options' => Restaurant::optionsInitial()]),
            'cities' => $this->cities(),
            'partners' => $this->partnerOptions(),
        ]);
    }

    public function store(Request $request, ProvisionRestaurant $provision): RedirectResponse
    {
        $request->merge([
            'owner_user' => mb_strtolower(trim((string) $request->input('owner_user'))),
            'owner_email' => mb_strtolower(trim((string) $request->input('owner_email'))),
        ]);

        $data = $this->validate($request) + $request->validate([
            'main_name' => ['required', 'string', 'max:100'],
            'main_address' => ['nullable', 'string', 'max:250'],
            'main_phone' => ['nullable', 'string', 'max:20'],
            'main_city_id' => ['nullable', 'exists:cities,id'],
            'partners' => ['nullable', 'array'],
            'partners.*' => [$this->partnerRule()],
            'owner_names' => ['required_without:partners', 'nullable', 'string', 'max:100'],
            'owner_email' => ['required_without:partners', 'nullable', 'email', 'max:150', 'unique:users,email'],
            'owner_user' => ['required_without:partners', 'nullable', 'string', 'max:20', 'regex:/^[a-z0-9_.-]+$/', 'unique:users,username'],
            'owner_password' => ['required_without:partners', 'nullable', 'string', 'min:4', 'confirmed'],
        ], [
            'owner_user.regex' => 'El código sólo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
        ], [
            'main_name' => 'nombre de la casa matriz',
            'owner_names' => 'nombre del dueño',
            'partners' => 'propietarios',
            'owner_email' => 'correo',
            'owner_user' => 'código',
            'owner_password' => 'contraseña',
        ]);

        $restaurant = $provision->create(
            $this->dataRestaurant($data, $request),
            [
                'name' => mb_strtoupper($data['main_name']),
                'address' => $data['main_address'] ?? null,
                'phone' => $data['main_phone'] ?? null,
                'city_id' => $data['main_city_id'] ?? null,
            ],
            array_map('intval', $data['partners'] ?? []),
            empty($data['owner_email']) ? null : ['name' => $data['owner_names'], 'username' => $data['owner_user'], 'email' => $data['owner_email'], 'password' => $data['owner_password']],
        );

        AuditLog::register('CREATE', 'restaurant', $restaurant->id, "Creó el restaurante {$restaurant->name}");

        return redirect()->route('restaurants.show', $restaurant)
            ->with('ok', 'Restaurante creado. El dueño debe cargar su dosificación en Facturación SIN antes de vender.');
    }

    public function edit(Restaurant $restaurant): View
    {
        return view('restaurants.form', [
            'restaurant' => $restaurant->load('partners'),
            'cities' => $this->cities(),
            'partners' => $this->partnerOptions(),
        ]);
    }

    public function update(Request $request, Restaurant $restaurant, ProvisionRestaurant $provision): RedirectResponse
    {
        $data = $this->validate($request) + $request->validate([
            'partners' => ['required', 'array', 'min:1'],
            'partners.*' => [$this->partnerRule()],
        ], [], ['partners' => 'propietarios']);

        DB::transaction(function () use ($restaurant, $data, $request, $provision) {
            $restaurant->update($this->dataRestaurant($data, $request));
            $provision->syncPartners($restaurant, array_map('intval', $data['partners']));
        });

        AuditLog::register('UPDATE', 'restaurant', $restaurant->id, "Editó el restaurante {$restaurant->name}");

        return redirect()->route('restaurants.show', $restaurant)->with('ok', 'Restaurante actualizado.');
    }

    public function status(Restaurant $restaurant): RedirectResponse
    {
        $restaurant->update(['status' => $restaurant->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);
        AuditLog::register('STATUS', 'restaurant', $restaurant->id, "Dejó {$restaurant->status} al restaurante {$restaurant->name}");

        return back()->with('ok', $restaurant->status === 'ACTIVE' ? 'Restaurante reactivado.' : 'Restaurante dado de baja: sus usuarios ya no pueden ingresar.');
    }

    private function validate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:250'],
            'activity' => ['nullable', 'string', 'max:255'],
            'web' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'logo' => ['nullable', 'image', 'mimes:'.implode(',', Restaurant::LOGO_MIMES), 'max:'.Restaurant::LOGO_MAX_KB],
            'remove_logo' => ['boolean'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'background_color.regex' => 'El fondo de la tienda debe ser un color como #FAF7F2.',
        ], [
            'background_color' => 'fondo de la tienda',
        ]);
    }

    private function dataRestaurant(array $data, Request $request): array
    {
        $options = collect(Restaurant::OPTIONS)->keys()
            ->mapWithKeys(fn ($key) => [$key => $request->boolean("options.{$key}")])
            ->all();

        $restaurant = [
            'name' => mb_strtoupper($data['name']),
            'options' => $options,
            'background_color' => isset($data['background_color']) ? mb_strtoupper($data['background_color']) : null,
        ] + collect($data)->only(['tax_id', 'email', 'phone', 'address', 'activity', 'web', 'status'])->all();

        // Sin archivo nuevo el logo actual se conserva, salvo que se pida quitarlo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $restaurant['logo'] = 'data:'.$file->getMimeType().';base64,'.base64_encode($file->get());
        } elseif ($request->boolean('remove_logo')) {
            $restaurant['logo'] = null;
        }

        return $restaurant;
    }

    /** @return list<array{id: int, label: string}> */
    private function partnerOptions(): array
    {
        return User::role(Role::PARTNER)->orderBy('name')->get()
            ->map(fn (User $user) => ['id' => $user->id, 'label' => "{$user->name} ({$user->email})"])
            ->all();
    }

    private function partnerRule()
    {
        return Rule::in(User::role(Role::PARTNER)->pluck('id')->all());
    }

    private function cities()
    {
        return City::orderBy('name')->pluck('name', 'id');
    }
}
