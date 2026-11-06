@extends('layout')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="max-w-4xl space-y-4"
          x-data="{
              empresa: @js((string) old('empresa_id', $empresaId)),
              sucursal: @js((string) old('sucursal_id', $usuario->puntoVenta?->sucursal_id)),
              almacen: @js((string) old('almacen_por_defecto_id', $usuario->puntoVenta?->almacen_por_defecto_id)),
              sucursales: @js($sucursales),
              almacenes: @js($almacenes),
          }">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <section class="tarjeta p-6">
            <h2 class="mb-4 font-bold">Datos de acceso</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @if ($usuario->exists)
                    <div class="sm:col-span-3"><span class="etiqueta">Restaurante</span><p class="font-semibold">{{ $usuario->empresa->nombre }}</p></div>
                @else
                    <div class="sm:col-span-3">
                        <label class="etiqueta" for="empresa_id">Restaurante <span class="text-marca-600">*</span></label>
                        <select name="empresa_id" id="empresa_id" class="campo" x-model="empresa" @change="sucursal = ''; almacen = ''">
                            <option value="">Seleccione...</option>
                            @foreach ($restaurantes as $id => $nombre)<option value="{{ $id }}">{{ $nombre }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <x-campo nombre="nombres" etiqueta="Nombre completo" :valor="$usuario->nombres" requerido />
                <x-campo nombre="usuario" etiqueta="Usuario" :valor="$usuario->usuario" requerido ayuda="Único en todo el sistema, sin espacios." />
                <x-campo nombre="cargo" etiqueta="Cargo" :valor="$usuario->cargo" />
                <x-campo nombre="password" etiqueta="Contraseña" tipo="password" :requerido="! $usuario->exists" :ayuda="$usuario->exists ? 'Déjela vacía para no cambiarla.' : 'Mínimo 4 caracteres. No distingue mayúsculas.'" />
                <x-campo nombre="password_confirmation" etiqueta="Repetir contraseña" tipo="password" />
                <x-campo nombre="rol_id" etiqueta="Rol" tipo="select" :opciones="$roles" :valor="$usuario->rol_id" vacio="Seleccione..." requerido />
                <x-campo nombre="estado" etiqueta="Estado" tipo="select" :opciones="['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo']" :valor="$usuario->estado" />
            </div>
        </section>

        <section class="tarjeta p-6">
            <h2 class="font-bold">Punto de venta</h2>
            <p class="mb-4 text-sm text-tenue">Sucursal con la que ingresa al POS y almacén del que se descuentan las recetas.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="etiqueta" for="sucursal_id">Sucursal <span class="text-marca-600">*</span></label>
                    <select name="sucursal_id" id="sucursal_id" class="campo" x-model="sucursal" @change="almacen = ''">
                        <option value="">Seleccione...</option>
                        <template x-for="s in sucursales.filter(s => String(s.empresa_id) === String(empresa))" :key="s.id">
                            <option :value="String(s.id)" x-text="s.nombre" :selected="String(s.id) === sucursal"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="etiqueta" for="almacen_por_defecto_id">Almacén por defecto</label>
                    <select name="almacen_por_defecto_id" id="almacen_por_defecto_id" class="campo" x-model="almacen">
                        <option value="">Ninguno</option>
                        <template x-for="a in almacenes.filter(a => String(a.sucursal_id) === String(sucursal))" :key="a.id">
                            <option :value="String(a.id)" x-text="a.nombre" :selected="String(a.id) === almacen"></option>
                        </template>
                    </select>
                </div>
                <x-campo nombre="puede_vender" etiqueta="Puede vender" tipo="checkbox" :valor="$usuario->puntoVenta?->puede_vender ?? true" />
                <x-campo nombre="venta_multialmacen" etiqueta="Puede cambiar de sucursal en el POS" tipo="checkbox" :valor="$usuario->puntoVenta?->venta_multialmacen ?? false" />
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('usuarios.index') }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
