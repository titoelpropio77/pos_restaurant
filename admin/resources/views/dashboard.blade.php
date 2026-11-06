@extends('layout')

@section('title', 'Inicio')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as [$text, $value, $icon])
            <div class="card p-5">
                <div class="flex items-center justify-between text-sm text-muted">{{ $text }} <i class="fa {{ $icon }}"></i></div>
                <p class="mt-2 font-display text-3xl font-bold text-panel tabular-nums">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <section class="card overflow-x-auto">
        <div class="flex items-center justify-between px-5 pt-5 pb-3">
            <h2 class="font-bold">Restaurantes</h2>
            <span class="text-sm text-muted">Ventas de los últimos 30 días</span>
        </div>
        <table class="data-table">
            <thead><tr><th>Restaurante</th><th class="num">Sucursales</th><th class="num">Usuarios</th><th class="num">Ventas</th><th class="num">Total Bs</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse ($restaurants as $r)
                    <tr>
                        <td class="font-semibold">
                            @if (auth()->user()->can('restaurants.view'))
                                <a href="{{ route('restaurants.show', $r) }}" class="text-brand-600 hover:underline">{{ $r->name }}</a>
                            @else
                                {{ $r->name }}
                            @endif
                        </td>
                        <td class="num">{{ $r->branches_count }}</td>
                        <td class="num">{{ $r->users_count }}</td>
                        <td class="num">{{ number_format($sales[$r->id]->quantity ?? 0) }}</td>
                        <td class="num">{{ number_format((float) ($sales[$r->id]->total ?? 0), 2) }}</td>
                        <td><x-status :value="$r->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-muted">Todavía no hay restaurantes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
