{{-- Selector para quien pertenece a varios restaurantes --}}
@auth
@php
    $restaurantOptions = auth()->user()->restaurants()->where('status', 'ACTIVE')->orderBy('name')->pluck('name', 'restaurants.id');
@endphp
@if ($restaurantOptions->count() > 1)
    <form method="POST" action="{{ route('restaurant.switch') }}">
        @csrf
        <select name="restaurant_id" onchange="this.form.submit()" title="Restaurante" class="{{ $class ?? '' }}">
            @foreach ($restaurantOptions as $id => $name)
                <option value="{{ $id }}" @selected($id === \App\Support\Tenant::id())>{{ $name }}</option>
            @endforeach
        </select>
    </form>
@endif
@endauth
