<?php

namespace App\Admin;

use App\Models\City;
use App\Models\Country;

/**
 * Catálogos comunes a todos los restaurantes. Cada campo: [etiqueta, tipo, reglas, opciones].
 */
class Catalogs
{
    public static function all(): array
    {
        return [
            'countries' => [
                'model' => Country::class, 'title' => 'Países', 'singular' => 'país', 'icon' => 'fa-globe',
                'columns' => ['name' => 'País', 'status' => 'Estado'],
                'fields' => [
                    'name' => ['Nombre', 'text', 'required|string|max:100'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', self::statuses()],
                ],
            ],
            'cities' => [
                'model' => City::class, 'title' => 'Ciudades', 'singular' => 'city', 'icon' => 'fa-map-marker',
                'columns' => ['name' => 'Ciudad', 'country.name' => 'País'],
                'fields' => [
                    'name' => ['Nombre', 'text', 'required|string|max:100'],
                    'country_id' => ['País', 'select', 'required|exists:countries,id', fn () => Country::orderBy('name')->pluck('name', 'id')->all()],
                ],
            ],
        ];
    }

    public static function get(string $slug): array
    {
        $catalogs = static::all();
        abort_unless(isset($catalogs[$slug]), 404);

        return $catalogs[$slug] + ['slug' => $slug];
    }

    private static function statuses(): array
    {
        return ['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo'];
    }
}
