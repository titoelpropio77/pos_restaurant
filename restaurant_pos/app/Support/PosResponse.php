<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

/**
 * Respuestas JSON con el contrato que espera el frontend del POS heredado:
 * siempre {"error": "...", "result": ...} y filas con los nombres de las columnas.
 *
 * El JS original compara los campos contra "" (p. ej. `PROCESANDO != ""`), por eso
 * los null se devuelven como cadena vacía, igual que hacían las clases *_MYSQL.
 */
class PosResponse
{
    public static function ok(mixed $result = '', array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['error' => '', 'result' => $result], $extra));
    }

    public static function error(string $message, mixed $result = '', array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['error' => $message, 'result' => $result], $extra));
    }

    /**
     * Filas para el JS del POS: los null se devuelven como cadena vacía y las claves se pueden renombrar.
     */
    public static function rows(iterable $rows, array $alias = []): array
    {
        return self::transform($rows, fn (string $key) => $alias[$key] ?? $key);
    }

    public static function row(mixed $row, array $alias = []): array
    {
        return self::rows([$row], $alias)[0];
    }

    private static function transform(iterable $rows, callable $key): array
    {
        $result = [];

        foreach ($rows as $row) {
            $data = $row instanceof Arrayable ? $row->toArray() : (array) $row;
            $normalized = [];

            foreach ($data as $name => $value) {
                if (is_array($value)) {
                    continue;
                }
                $normalized[$key($name)] = $value ?? '';
            }

            $result[] = $normalized;
        }

        return $result;
    }
}
