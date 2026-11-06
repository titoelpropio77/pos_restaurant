@props(['valor'])
@php
    $activo = in_array(strtoupper((string) $valor), ['ACTIVO', '1', 'S', 'SI'], true);
    $texto = is_numeric($valor) ? ($activo ? 'ACTIVO' : 'INACTIVO') : $valor;
@endphp
<span @class(['insignia', 'bg-green-100 text-green-800' => $activo, 'bg-slate-100 text-slate-600' => ! $activo])>
    <i @class(['fa', 'fa-check-circle' => $activo, 'fa-minus-circle' => ! $activo])></i>{{ $texto }}
</span>
