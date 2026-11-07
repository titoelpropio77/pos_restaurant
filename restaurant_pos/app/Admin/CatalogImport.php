<?php

namespace App\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Carga masiva de un catálogo desde Excel (xlsx, xls, ods) o CSV (definición 'import' en App\Admin\Catalogs).
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
    public const MAX_ROWS = 1000;

    public const EXTENSIONS = ['xlsx', 'xls', 'ods', 'csv'];

    /** Plantilla Excel: hoja de datos con ejemplos y listas desplegables, y hoja de instrucciones. */
    public static function template(array $def): StreamedResponse
    {
        $import = $def['import'];
        $headers = array_keys($import['columns']);
        $last = chr(ord('A') + count($headers) - 1);

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(mb_substr($def['title'], 0, 31));
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($import['example'] ?? [], null, 'A2');
        $sheet->freezePane('A2');
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setRGB('14213D');
        $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8961C');

        foreach ($headers as $i => $header) {
            $column = chr(ord('A') + $i);
            $sheet->getColumnDimension($column)->setWidth(max(14, mb_strlen($header) + 4));
            $field = $def['fields'][$import['columns'][$header]];

            // Las opciones de los select (Activo/Inactivo...) como lista desplegable
            if ($field[1] === 'select' && isset($field[3])) {
                $options = is_callable($field[3]) ? ($field[3])() : $field[3];
                $validation = new DataValidation;
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setAllowBlank(true)
                    ->setShowDropDown(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Valor no válido')
                    ->setError('Elija una opción de la lista.')
                    ->setFormula1('"'.implode(',', array_values($options)).'"');
                $sheet->setDataValidation("{$column}2:{$column}".(self::MAX_ROWS + 1), $validation);
            }
        }
        $sheet->getColumnDimension('A')->setWidth(30);

        $help = $book->createSheet();
        $help->setTitle('Instrucciones');
        $help->fromArray(['Columna', 'Contenido'], null, 'A1');
        $help->getStyle('A1:B1')->getFont()->setBold(true);
        $line = 2;
        foreach ($import['columns'] as $header => $field) {
            $help->fromArray([$header, $import['help'][$header] ?? $def['fields'][$field][0]], null, "A{$line}");
            $line++;
        }
        $line++;
        $help->setCellValue("A{$line}", 'Una fila por registro, máximo '.self::MAX_ROWS.'. No cambie los encabezados de la primera hoja. Si alguna fila tiene errores no se guarda ninguna.');
        $help->getColumnDimension('A')->setWidth(14);
        $help->getColumnDimension('B')->setWidth(80);
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            'plantilla-'.$def['slug'].'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * @return int registros creados
     *
     * @throws ValidationException
     */
    public static function run(array $def, UploadedFile $file): int
    {
        $import = $def['import'];
        $rows = static::read($file, array_keys($import['columns']));
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
                $value = trim((string) ($cells[$header] ?? ''));
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

    /**
     * Filas de la primera hoja indexadas por su número de fila, con las celdas por encabezado.
     * En CSV detecta el separador (";" de Excel en español o ",").
     *
     * @return array<int, array<string, string>>
     */
    private static function read(UploadedFile $file, array $columns): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $reader = IOFactory::createReader(ucfirst($extension));
            if ($reader instanceof Csv) {
                $reader->setInputEncoding(Csv::GUESS_ENCODING);
            }
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file->getRealPath())->getSheet(0);
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'No se pudo leer el archivo. Use la plantilla en Excel (.xlsx).']);
        }

        if ($sheet->getHighestDataRow() > self::MAX_ROWS + 1) {
            throw ValidationException::withMessages(['file' => 'El archivo supera el máximo de '.self::MAX_ROWS.' filas.']);
        }

        $table = $sheet->toArray('', true, false, false);
        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($table) ?? []);
        $missing = array_diff($columns, $headers);
        if ($missing) {
            throw ValidationException::withMessages(['file' => 'Faltan las columnas: '.implode(', ', $missing).'. Descargue la plantilla.']);
        }

        $rows = [];
        foreach ($table as $i => $cells) {
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $rows[$i + 2] = array_combine($headers, array_map(fn ($c) => static::text($c), $cells));
        }

        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'El archivo no tiene filas para importar.']);
        }

        return $rows;
    }

    /** Texto de una celda: los enteros que Excel guarda como 4.0 quedan como "4". */
    private static function text(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    /** En los campos select acepta la clave o la etiqueta de la opción ("Activo" → ACTIVE). */
    private static function option(array $field, string $value): string
    {
        if ($field[1] !== 'select' || ! isset($field[3])) {
            return $value;
        }

        $options = is_callable($field[3]) ? ($field[3])() : $field[3];
        foreach ($options as $key => $label) {
            if (mb_strtolower((string) $key) === mb_strtolower($value) || mb_strtolower((string) $label) === mb_strtolower($value)) {
                return (string) $key;
            }
        }

        return $value;
    }
}
