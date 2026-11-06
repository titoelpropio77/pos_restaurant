@extends('admin.layout')

@section('title', 'Caja')

@section('content')
    <p class="mb-4 max-w-3xl text-sm text-muted">Cajas abiertas ahora y cierres realizados, con el efectivo esperado, el contado y la diferencia.</p>

    <h2 class="mb-2 font-semibold">Cajas abiertas</h2>
    <div class="card mb-6 overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th class="text-right">Monto inicial</th>
                    <th class="text-right">Esperado</th>
                    @can('cash_sessions.close')<th>Cerrar caja</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse ($open as $session)
                    <tr>
                        <td>{{ $session->branch?->name }}</td>
                        <td>{{ $session->opener?->username }}</td>
                        <td>{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                        <td class="num">{{ number_format((float) $session->opening_amount, 2) }}</td>
                        <td class="num">{{ number_format($expected[$session->id], 2) }}</td>
                        @can('cash_sessions.close')
                        <td>
                            <form method="POST" action="{{ route('admin.cash_sessions.close', $session) }}" class="flex flex-wrap items-center gap-2"
                                  onsubmit="return confirm('¿Cerrar esta caja? No se podrá reabrir.')">
                                @csrf
                                <input type="number" step="0.01" min="0" name="counted_cash" required placeholder="Efectivo contado" aria-label="Efectivo contado" class="field w-40">
                                <input type="text" name="notes" maxlength="500" required placeholder="Motivo" aria-label="Motivo del cierre" class="field w-48">
                                <button class="btn-primary btn-small"><i class="fa fa-lock"></i> Cerrar</button>
                            </form>
                        </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-muted">No hay cajas abiertas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="mb-2 font-semibold">Cierres</h2>
    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div><label class="label" for="from">Desde</label><input type="date" name="from" id="from" value="{{ request('from') }}" class="field"></div>
        <div><label class="label" for="to">Hasta</label><input type="date" name="to" id="to" value="{{ request('to') }}" class="field"></div>
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th>Abrió</th>
                    <th>Cerró</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th class="text-right">Monto inicial</th>
                    <th class="text-right">Esperado</th>
                    <th class="text-right">Contado</th>
                    <th class="text-right">Diferencia</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($closed as $session)
                    <tr>
                        <td>{{ $session->branch?->name }}</td>
                        <td>{{ $session->opener?->username }}</td>
                        <td>{{ $session->closer?->username }}</td>
                        <td>{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $session->closed_at->format('d/m/Y H:i') }}</td>
                        <td class="num">{{ number_format((float) $session->opening_amount, 2) }}</td>
                        <td class="num">{{ number_format((float) $session->expected_cash, 2) }}</td>
                        <td class="num">{{ number_format((float) $session->counted_cash, 2) }}</td>
                        <td @class(['num', 'text-red-600' => (float) $session->difference !== 0.0])>{{ number_format((float) $session->difference, 2) }}</td>
                        <td>{{ $session->notes ?: ((float) $session->difference !== 0.0 ? 'Sin nota' : '') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="py-10 text-center text-muted">No hay cierres.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $closed->links() }}</div>
@endsection
