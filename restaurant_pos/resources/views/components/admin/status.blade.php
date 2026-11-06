@props(['value'])
@php
    $labels = ['ACTIVE' => 'ACTIVO', 'INACTIVE' => 'INACTIVO', 'DELETED' => 'ELIMINADO'];
    $active = in_array(strtoupper((string) $value), ['ACTIVE', '1', 'S', 'SI'], true);
    $text = is_numeric($value) ? ($active ? 'ACTIVO' : 'INACTIVO') : ($labels[$value] ?? $value);
@endphp
<span @class(['badge', 'bg-green-100 text-green-800' => $active, 'bg-slate-100 text-slate-600' => ! $active])>
    <i @class(['fa', 'fa-check-circle' => $active, 'fa-minus-circle' => ! $active])></i>{{ $text }}
</span>
