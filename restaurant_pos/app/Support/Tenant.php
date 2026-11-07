<?php

namespace App\Support;

use App\Models\Restaurant;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Restaurante (empresa) de la petición actual: decide qué datos ve y escribe cada usuario.
 *
 * Durante una petición web el modo es estricto: sin restaurante en contexto las consultas no
 * devuelven nada, de modo que olvidar autenticar una ruta nunca expone datos de otro restaurante.
 * Fuera de una petición (migraciones, seeders, comandos) no se filtra salvo que se llame a usar().
 */
class Tenant
{
    /** Restaurante con el que trabaja el usuario: un partner puede tener varios. */
    public const SESSION_KEY = 'restaurant_id';

    private ?int $restaurantId = null;

    private bool $strict = false;

    /** @var array<int, array{0: ?int, 1: bool}> */
    private array $previous = [];

    public static function current(): self
    {
        return app(self::class);
    }

    public static function id(): ?int
    {
        return self::current()->restaurantId;
    }

    public static function strict(): bool
    {
        return self::current()->strict;
    }

    public static function use(?int $restaurantId): void
    {
        self::current()->restaurantId = $restaurantId;
    }

    public function startRequest(?int $restaurantId): void
    {
        $this->previous[] = [$this->restaurantId, $this->strict];
        $this->restaurantId = $restaurantId;
        $this->strict = true;
    }

    public function finishRequest(): void
    {
        if ($this->previous) {
            [$this->restaurantId, $this->strict] = array_pop($this->previous);
        }
    }

    /**
     * Regla exists limitada al restaurante actual. Las reglas de validación consultan la BD
     * directamente, sin pasar por el scope global de los modelos.
     */
    public static function exists(string $table_name, string $column = 'id'): string
    {
        return "exists:{$table_name},{$column},restaurant_id,".(int) self::id();
    }

    public static function unique(string $table_name, string $column): Unique
    {
        return Rule::unique($table_name, $column)->where('restaurant_id', (int) self::id());
    }

    /**
     * Opciones del POS: las del restaurante reemplazan a las generales de config/pos.php.
     */
    public static function options(): array
    {
        $own = self::id() ? Restaurant::whereKey(self::id())->value('options') : null;

        return array_replace(config('pos.options'), is_array($own) ? $own : []);
    }

    /** Una opción del POS del restaurante (ver options()). */
    public static function option(string $key): bool
    {
        return (bool) (self::options()[$key] ?? false);
    }
}
