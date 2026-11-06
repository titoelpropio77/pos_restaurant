@extends('layout')

@section('title', $user->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="max-w-4xl space-y-4"
          x-data="{
              empresa: @js((string) old('restaurant_id', $restaurantId)),
              sucursal: @js((string) old('branch_id', $pointOfSale?->branch_id)),
              almacen: @js((string) old('default_warehouse_id', $pointOfSale?->default_warehouse_id)),
              sucursales: @js($branches),
              almacenes: @js($warehouses),
          }">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <section class="card p-6">
            <h2 class="mb-4 font-bold">Datos de acceso</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @if ($user->exists)
                    <div class="sm:col-span-3"><span class="label">Restaurante</span><p class="font-semibold">{{ $user->restaurants->firstWhere('id', $restaurantId)?->name }}</p><input type="hidden" name="restaurant_id" value="{{ $restaurantId }}"></div>
                @else
                    <div class="sm:col-span-3">
                        <label class="label" for="restaurant_id">Restaurante <span class="text-brand-600">*</span></label>
                        <select name="restaurant_id" id="restaurant_id" class="field" x-model="restaurant" @change="sucursal = ''; almacen = ''">
                            <option value="">Seleccione...</option>
                            @foreach ($restaurants as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <x-field name="name" label="Nombre completo" :value="$user->name" required />
                <x-field name="email" label="Correo" type="email" :value="$user->email" required help="Con este correo ingresa al sistema." />
                <x-field name="username" label="Código" :value="$user->username" required help="Nombre corto que aparece en ventas y reportes, sin espacios." />
                <x-field name="position" label="Cargo" :value="$user->position" />
                <x-field name="password" label="Contraseña" type="password" :required="! $user->exists" :help="$user->exists ? 'Déjela vacía para no cambiarla.' : 'Mínimo 4 caracteres. No distingue mayúsculas.'" />
                <x-field name="password_confirmation" label="Repetir contraseña" type="password" />
                <x-field name="role_id" label="Rol" type="select" :options="$roles" :value="$user->roles->first()?->id" empty="Seleccione..." required />
                <x-field name="status" label="Estado" type="select" :options="['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo']" :value="$user->status" />
            </div>
        </section>

        <section class="card p-6">
            <h2 class="font-bold">Punto de venta</h2>
            <p class="mb-4 text-sm text-muted">Sucursal con la que ingresa al POS y almacén del que se descuentan las recetas.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="branch_id">Sucursal <span class="text-brand-600">*</span></label>
                    <select name="branch_id" id="branch_id" class="field" x-model="branch" @change="almacen = ''">
                        <option value="">Seleccione...</option>
                        <template x-for="s in branches.filter(s => String(s.restaurant_id) === String(restaurant))" :key="s.id">
                            <option :value="String(s.id)" x-text="s.name" :selected="String(s.id) === sucursal"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label" for="default_warehouse_id">Almacén por defecto</label>
                    <select name="default_warehouse_id" id="default_warehouse_id" class="field" x-model="warehouse">
                        <option value="">Ninguno</option>
                        <template x-for="a in warehouses.filter(a => String(a.branch_id) === String(branch))" :key="a.id">
                            <option :value="String(a.id)" x-text="a.name" :selected="String(a.id) === almacen"></option>
                        </template>
                    </select>
                </div>
                <x-field name="can_sell" label="Puede vender" type="checkbox" :value="$pointOfSale?->can_sell ?? true" />
                <x-field name="multi_branch_sales" label="Puede cambiar de sucursal en el POS" type="checkbox" :value="$pointOfSale?->multi_branch_sales ?? false" />
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('users.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
