<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Restaurante (empresa) de la petición actual: decide qué datos ve y escribe cada usuario.
 *
 * Durante una petición web el modo es estricto: sin restaurante en contexto las consultas no
 * devuelven nada, de modo que olvidar autenticar una ruta nunca expone datos de otro restaurante.
 * Fuera de una petición (migraciones, seeders, comandos) no se filtra salvo que se llame a usar().
 */
class Restaurante
{
    private ?int $empresaId = null;

    private bool $estricto = false;

    /** @var array<int, array{0: ?int, 1: bool}> */
    private array $anteriores = [];

    public static function actual(): self
    {
        return app(self::class);
    }

    public static function id(): ?int
    {
        return self::actual()->empresaId;
    }

    public static function estricto(): bool
    {
        return self::actual()->estricto;
    }

    public static function usar(?int $empresaId): void
    {
        self::actual()->empresaId = $empresaId;
    }

    public function iniciarPeticion(?int $empresaId): void
    {
        $this->anteriores[] = [$this->empresaId, $this->estricto];
        $this->empresaId = $empresaId;
        $this->estricto = true;
    }

    public function terminarPeticion(): void
    {
        if ($this->anteriores) {
            [$this->empresaId, $this->estricto] = array_pop($this->anteriores);
        }
    }

    /**
     * Regla exists limitada al restaurante actual. Las reglas de validación consultan la BD
     * directamente, sin pasar por el scope global de los modelos.
     */
    public static function existe(string $tabla, string $columna = 'id'): string
    {
        return "exists:{$tabla},{$columna},empresa_id,".(int) self::id();
    }

    public static function unico(string $tabla, string $columna): Unique
    {
        return Rule::unique($tabla, $columna)->where('empresa_id', (int) self::id());
    }

    /**
     * Opciones del POS: las del restaurante reemplazan a las generales de config/posgourmet.php.
     */
    public static function opciones(): array
    {
        $propias = self::id() ? Empresa::whereKey(self::id())->value('opciones') : null;

        return array_replace(config('posgourmet.opciones'), is_array($propias) ? $propias : []);
    }
}
