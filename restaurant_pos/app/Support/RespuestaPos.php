<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

/**
 * Respuestas JSON con el contrato que espera el frontend del POS heredado:
 * siempre {"error": "...", "result": ...} y filas con los nombres de campo legacy.
 *
 * El JS original compara los campos contra "" (p. ej. `PROCESANDO != ""`), por eso
 * los null se devuelven como cadena vacía, igual que hacían las clases *_MYSQL.
 */
class RespuestaPos
{
    public static function ok(mixed $result = '', array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['error' => '', 'result' => $result], $extra));
    }

    public static function error(string $mensaje, mixed $result = '', array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['error' => $mensaje, 'result' => $result], $extra));
    }

    /**
     * Filas con claves en MAYÚSCULAS (tablas legacy *_mysql: ventas, mesas, gastos...).
     */
    public static function mayusculas(iterable $filas, array $alias = []): array
    {
        return self::transformar($filas, fn (string $clave) => $alias[$clave] ?? strtoupper($clave));
    }

    /**
     * Filas con claves en minúsculas (productos, categorías, sucursales).
     */
    public static function minusculas(iterable $filas, array $alias = []): array
    {
        return self::transformar($filas, fn (string $clave) => $alias[$clave] ?? $clave);
    }

    public static function fila(mixed $fila, bool $mayusculas = true, array $alias = []): array
    {
        return ($mayusculas ? self::mayusculas([$fila], $alias) : self::minusculas([$fila], $alias))[0];
    }

    private static function transformar(iterable $filas, callable $clave): array
    {
        $resultado = [];

        foreach ($filas as $fila) {
            $datos = $fila instanceof Arrayable ? $fila->toArray() : (array) $fila;
            $normalizada = [];

            foreach ($datos as $nombre => $valor) {
                if (is_array($valor)) {
                    continue;
                }
                $normalizada[$clave($nombre)] = $valor ?? '';
            }

            $resultado[] = $normalizada;
        }

        return $resultado;
    }
}
