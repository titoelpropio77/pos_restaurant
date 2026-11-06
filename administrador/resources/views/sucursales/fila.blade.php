<tr>
    @if ($conRestaurante)<td class="font-semibold"><a href="{{ route('restaurantes.show', $s->empresa_id) }}" class="text-marca-600 hover:underline">{{ $s->empresa->nombre }}</a></td>@endif
    <td class="font-semibold">{{ $s->nombre }}<span class="block text-xs font-normal text-tenue">{{ $s->direccion }}</span></td>
    <td>
        <span @class(['insignia', 'bg-marca-100 text-marca-700' => $s->es_matriz, 'bg-slate-100 text-slate-700' => ! $s->es_matriz])>
            {{ $s->es_matriz ? 'Casa matriz' : 'Sucursal' }}
        </span>
    </td>
    <td><x-estado :valor="$s->estado" /></td>
    <td class="whitespace-nowrap">
        @if (auth()->user()->puede('restaurantes.gestionar'))
            <a href="{{ route('sucursales.edit', $s) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
            @unless ($s->es_matriz)
                <form method="POST" action="{{ route('sucursales.matriz', $s) }}" class="inline" onsubmit="return confirm('¿Convertir {{ $s->nombre }} en la casa matriz?')">
                    @csrf
                    <button class="btn-secundario btn-chico" title="Definir como casa matriz"><i class="fa fa-star-o"></i></button>
                </form>
                <form method="POST" action="{{ route('sucursales.estado', $s) }}" class="inline">
                    @csrf
                    <button class="btn-secundario btn-chico" title="{{ $s->estado === 'ACTIVO' ? 'Dar de baja' : 'Reactivar' }}"><i class="fa fa-power-off"></i></button>
                </form>
            @endunless
        @endif
    </td>
</tr>
