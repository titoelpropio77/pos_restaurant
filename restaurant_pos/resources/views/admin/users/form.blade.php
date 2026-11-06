@extends('admin.layout')

@section('title', $user->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')
    <form method="POST" class="max-w-4xl space-y-5"
          action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <section class="card p-6">
            <h2 class="mb-4 font-bold">Datos de acceso</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-admin.field name="name" label="Nombre completo" :value="$user->name" required class="sm:col-span-2" />
                <x-admin.field name="email" label="Correo" type="email" :value="$user->email" required help="Con este correo ingresa al sistema." />
                <x-admin.field name="username" label="Código" :value="$user->username" required help="Nombre corto que aparece en ventas y reportes, sin espacios." />
                <x-admin.field name="position" label="Cargo" :value="$user->position" />
                <x-admin.field name="password" label="Contraseña" type="password" :required="! $user->exists" :help="$user->exists ? 'Déjela vacía para no cambiarla.' : 'Mínimo 4 caracteres.'" />
                <x-admin.field name="password_confirmation" label="Repetir contraseña" type="password" />
                <x-admin.field name="role_id" label="Rol" type="select" :options="$roles" :value="$user->roles->first()?->id" empty="Seleccione..." required />
                <x-admin.field name="status" label="Estado" type="select" :options="['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo']" :value="$user->status" />
            </div>
        </section>

        <section class="card p-6">
            <h2 class="font-bold">Punto de venta</h2>
            <p class="mb-4 text-sm text-muted">Sucursal con la que ingresa al POS y almacén del que se descuentan las recetas.</p>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-admin.field name="branch_id" label="Sucursal" type="select" :options="$branches" :value="$user->pointOfSale?->branch_id" empty="Seleccione..." required class="sm:col-span-2" />
                <x-admin.field name="default_warehouse_id" label="Almacén por defecto" type="select" :options="$warehouses" :value="$user->pointOfSale?->default_warehouse_id" empty="Ninguno" class="sm:col-span-2" />
                <x-admin.field name="can_sell" label="Puede vender" type="checkbox" :value="$user->pointOfSale?->can_sell ?? true" class="sm:col-span-2" />
                <x-admin.field name="multi_branch_sales" label="Puede cambiar de sucursal en el POS" type="checkbox" :value="$user->pointOfSale?->multi_branch_sales ?? false" class="sm:col-span-2" />
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
@endsection
