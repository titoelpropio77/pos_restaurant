<?php

namespace App\Admin;

use App\Support\SpreadsheetFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Carga masiva de un catálogo desde Excel o CSV (definición 'import' en App\Admin\Catalogs).
 *
 * La definición indica:
 *  - columns:  [encabezado del archivo => campo del formulario]
 *  - defaults: valores para las celdas vacías
 *  - unique:   campo que no puede repetirse (sin distinguir mayúsculas)
 *  - help:     [encabezado => explicación] para el modal y la hoja de instrucciones
 *  - example:  filas de ejemplo de la plantilla
 *
 * El campo 'sequence' del catálogo (p. ej. el orden) vacío continúa desde el mayor existente.
 * Es todo o nada: si una fila tiene errores no se guarda ninguna.
 */
class CatalogImport
{
    /** @return array<string, string> encabezado => explicación */
    public static function columns(array $def): array
    {
        $columns = [];
        foreach ($def['import']['columns'] as $header => $field) {
            $columns[$header] = $def['import']['help'][$header] ?? $def['fields'][$field][0];
        }

        return $columns;
    }

    public static function template(array $def): StreamedResponse
    {
        $lists = [];
        foreach ($def['import']['columns'] as $header => $field) {
            if ($def['fields'][$field][1] === 'select' && isset($def['fields'][$field][3])) {
                $lists[$header] = array_values(static::options($def['fields'][$field]));
            }
        }

        return SpreadsheetFile::template('plantilla-'.$def['slug'], $def['title'], static::columns($def), $def['import']['example'] ?? [], $lists);
    }

    /**
     * @return int registros creados
     *
     * @throws ValidationException
     */
    public static function run(array $def, UploadedFile $file): int
    {
        $import = $def['import'];
        $rows = SpreadsheetFile::read($file, array_keys($import['columns']));
        $errors = [];
        $records = [];
        $seen = [];
        $unique = $import['unique'] ?? null;
        $existing = $unique
            ? $def['model']::query()->pluck($unique)->map(fn ($v) => mb_strtolower(trim((string) $v)))->flip()->all()
            : [];

        $sequence = $def['sequence'] ?? null;
        $next = $sequence ? (int) $def['model']::query()->max($sequence) : 0;

        foreach ($rows as $line => $cells) {
            $data = [];
            foreach ($import['columns'] as $header => $field) {
                $value = $cells[$header] ?? '';
                $data[$field] = $value === '' ? ($import['defaults'][$field] ?? null) : static::option($def['fields'][$field], $value);
            }

            if ($sequence && $data[$sequence] === null) {
                $data[$sequence] = ++$next;
            } elseif ($sequence && is_numeric($data[$sequence])) {
                $next = max($next, (int) $data[$sequence]);
            }

            $validator = Validator::make(
                $data,
                array_map(fn ($f) => $f[2], array_intersect_key($def['fields'], $data)),
                [],
                array_map(fn ($f) => mb_strtolower($f[0]), $def['fields']),
            );

            foreach ($validator->errors()->all() as $message) {
                $errors[] = "Fila {$line}: {$message}";
            }

            if ($unique && filled($data[$unique])) {
                $key = mb_strtolower($data[$unique]);
                if (isset($existing[$key])) {
                    $errors[] = "Fila {$line}: «{$data[$unique]}» ya existe.";
                } elseif (isset($seen[$key])) {
                    $errors[] = "Fila {$line}: «{$data[$unique]}» está repetido en la fila {$seen[$key]}.";
                }
                $seen[$key] ??= $line;
            }

            $records[] = $data;
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 50)]);
        }

        DB::transaction(function () use ($def, $records) {
            foreach ($records as $data) {
                $record = $def['model']::create($data);
                if (isset($def['after'])) {
                    ($def['after'])($record, true);
                }
            }
        });

        return count($records);
    }

    private static function options(array $field): array
    {
        return is_callable($field[3]) ? ($field[3])() : $field[3];
    }

    /** En los campos select acepta la clave o la etiqueta de la opción ("Activo" → ACTIVE). */
    private static function option(array $field, string $value): string
    {
        if ($field[1] !== 'select' || ! isset($field[3])) {
            return $value;
        }

        foreach (static::options($field) as $key => $label) {
            if (mb_strtolower((string) $key) === mb_strtolower($value) || mb_strtolower((string) $label) === mb_strtolower($value)) {
                return (string) $key;
            }
        }

        return $value;
    }
}
