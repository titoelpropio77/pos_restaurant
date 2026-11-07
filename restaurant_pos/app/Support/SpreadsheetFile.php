<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Planillas de las cargas masivas: lectura de Excel (xlsx, xls, ods) o CSV y plantillas .xlsx.
 *
 * Los encabezados se normalizan sin tildes, en minúsculas y con "_" ("Código de barras" → codigo_de_barras).
 * Los errores se lanzan como ValidationException del campo "file".
 */
class SpreadsheetFile
{
    public const MAX_ROWS = 1000;

    public const EXTENSIONS = ['xlsx', 'xls', 'ods', 'csv'];

    public static function header(string $text): string
    {
        return Str::slug(trim($text), '_');
    }

    /**
     * Filas de la primera hoja indexadas por su número de fila, con las celdas por encabezado.
     *
     * @param  string[]  $required  encabezados obligatorios
     * @return array<int, array<string, string>>
     */
    public static function read(UploadedFile $file, array $required): array
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
            static::fail('No se pudo leer el archivo. Use la plantilla en Excel (.xlsx).');
        }

        if ($sheet->getHighestDataRow() > self::MAX_ROWS + 1) {
            static::fail('El archivo supera el máximo de '.self::MAX_ROWS.' filas.');
        }

        $table = $sheet->toArray('', true, false, false);
        $headers = array_map(fn ($h) => static::header((string) $h), array_shift($table) ?? []);
        $missing = array_diff($required, $headers);
        if ($missing) {
            static::fail('Faltan las columnas: '.implode(', ', $missing).'. Descargue la plantilla.');
        }

        $rows = [];
        foreach ($table as $i => $cells) {
            $cells = array_map(fn ($c) => static::text($c), $cells);
            if (implode('', $cells) === '') {
                continue;
            }
            $rows[$i + 2] = array_combine($headers, $cells);
        }

        if (! $rows) {
            static::fail('El archivo no tiene filas para importar.');
        }

        return $rows;
    }

    /**
     * Plantilla .xlsx: hoja de datos con ejemplos y listas desplegables, y hoja de instrucciones.
     *
     * @param  array<string, string>  $columns  encabezado => explicación
     * @param  array<int, array>  $examples  filas de ejemplo
     * @param  array<string, string[]>  $lists  encabezado => opciones de la lista desplegable
     * @param  string[]  $text  encabezados con formato texto (códigos largos que Excel redondearía)
     */
    public static function template(string $file, string $title, array $columns, array $examples = [], array $lists = [], array $text = []): StreamedResponse
    {
        $headers = array_keys($columns);
        $last = static::column(count($headers) - 1);

        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($examples, null, 'A2', true);
        $sheet->freezePane('A2');
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setRGB('14213D');
        $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8961C');
        foreach ($headers as $i => $header) {
            $column = static::column($i);
            $sheet->getColumnDimension($column)->setWidth($i === 0 ? 30 : max(14, mb_strlen($header) + 4));
            if (in_array($header, $text, true)) {
                $sheet->getStyle("{$column}2:{$column}".(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            }
        }

        $help = $book->createSheet();
        $help->setTitle('Instrucciones');
        $help->fromArray(['Columna', 'Contenido'], null, 'A1');
        $help->getStyle('A1:B1')->getFont()->setBold(true);
        $line = 2;
        foreach ($columns as $header => $text) {
            $help->fromArray([$header, $text], null, "A{$line}");
            $line++;
        }
        $help->setCellValue('A'.($line + 1), 'Una fila por registro, máximo '.self::MAX_ROWS.'. No cambie los encabezados de la primera hoja. Si alguna fila tiene errores no se guarda ninguna.');
        $help->getColumnDimension('A')->setWidth(18);
        $help->getColumnDimension('B')->setWidth(90);

        if ($lists) {
            static::lists($book, $sheet, $headers, $lists);
        }
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            $file.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /** Listas desplegables a partir de una hoja oculta (sin el límite de 255 caracteres de una lista escrita). */
    private static function lists(Spreadsheet $book, Worksheet $sheet, array $headers, array $lists): void
    {
        $source = $book->createSheet();
        $source->setTitle('Listas');
        $source->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $n = 0;
        foreach ($lists as $header => $options) {
            $position = array_search($header, $headers, true);
            if ($position === false || ! $options) {
                continue;
            }
            $sourceColumn = static::column($n++);
            $source->fromArray(array_map(fn ($o) => [$o], array_values($options)), null, "{$sourceColumn}1", true);

            $validation = new DataValidation;
            $validation->setType(DataValidation::TYPE_LIST)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle('Valor no válido')
                ->setError('Elija una opción de la lista.')
                ->setFormula1("Listas!\${$sourceColumn}\$1:\${$sourceColumn}\$".count($options));

            $column = static::column($position);
            $sheet->setDataValidation("{$column}2:{$column}".(self::MAX_ROWS + 1), $validation);
        }
    }

    private static function column(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index + 1);
    }

    /** Texto de una celda: los enteros que Excel guarda como 4.0 quedan como "4". */
    private static function text(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value && abs($value) < PHP_INT_MAX) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
