<tr>
    @if ($withRestaurant)<td class="font-semibold"><a href="{{ route('restaurants.show', $s->restaurant_id) }}" class="text-brand-600 hover:underline">{{ $s->restaurant->name }}</a></td>@endif
    <td class="font-semibold">{{ $s->name }}<span class="block text-xs font-normal text-muted">{{ $s->address }}</span></td>
    <td>
        <span @class(['badge', 'bg-brand-100 text-brand-700' => $s->is_main, 'bg-slate-100 text-slate-700' => ! $s->is_main])>
            {{ $s->is_main ? 'Casa matriz' : 'Sucursal' }}
        </span>
    </td>
    <td><x-status :value="$s->status" /></td>
    <td class="whitespace-nowrap">
        @can('branches.update')
            <a href="{{ route('branches.edit', $s) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
        @endcan
        @unless ($s->is_main)
            @can('branches.set_main')
                <form method="POST" action="{{ route('branches.main', $s) }}" class="inline" onsubmit="return confirm('¿Convertir {{ $s->name }} en la casa matriz?')">
                    @csrf
                    <button class="btn-secondary btn-small" title="Definir como casa matriz"><i class="fa fa-star-o"></i></button>
                </form>
            @endcan
            @can('branches.deactivate')
                <form method="POST" action="{{ route('branches.status', $s) }}" class="inline">
                    @csrf
                    <button class="btn-secondary btn-small" title="{{ $s->status === 'ACTIVE' ? 'Dar de baja' : 'Reactivar' }}"><i class="fa fa-power-off"></i></button>
                </form>
            @endcan
        @endunless
    </td>
</tr>
