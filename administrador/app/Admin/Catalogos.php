<?php

namespace App\Admin;

use App\Models\Banco;
use App\Models\Ciudad;
use App\Models\Moneda;
use App\Models\Pais;

/**
 * Catálogos comunes a todos los restaurantes. Cada campo: [etiqueta, tipo, reglas, opciones].
 */
class Catalogos
{
    public static function todos(): array
    {
        return [
            'paises' => [
                'modelo' => Pais::class, 'titulo' => 'Países', 'singular' => 'país', 'icono' => 'fa-globe',
                'columnas' => ['nombre' => 'País', 'estado' => 'Estado'],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', self::estados()],
                ],
            ],
            'ciudades' => [
                'modelo' => Ciudad::class, 'titulo' => 'Ciudades', 'singular' => 'ciudad', 'icono' => 'fa-map-marker',
                'columnas' => ['nombre' => 'Ciudad', 'pais.nombre' => 'País'],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100'],
                    'pais_id' => ['País', 'select', 'required|exists:paises,id', fn () => Pais::orderBy('nombre')->pluck('nombre', 'id')->all()],
                ],
            ],
            'monedas' => [
                'modelo' => Moneda::class, 'titulo' => 'Monedas', 'singular' => 'moneda', 'icono' => 'fa-money',
                'columnas' => ['nombre' => 'Moneda', 'abreviatura' => 'Abreviatura', 'estado' => 'Estado'],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:30'],
                    'abreviatura' => ['Abreviatura', 'text', 'nullable|string|max:10'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', self::estados()],
                ],
            ],
            'bancos' => [
                'modelo' => Banco::class, 'titulo' => 'Bancos', 'singular' => 'banco', 'icono' => 'fa-university',
                'columnas' => ['nombre' => 'Banco', 'estado' => 'Estado'],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', self::estados()],
                ],
            ],
        ];
    }

    public static function obtener(string $slug): array
    {
        $catalogos = static::todos();
        abort_unless(isset($catalogos[$slug]), 404);

        return $catalogos[$slug] + ['slug' => $slug];
    }

    private static function estados(): array
    {
        return ['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo'];
    }
}
