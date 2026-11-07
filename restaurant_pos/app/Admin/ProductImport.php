<?php

namespace App\Admin;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\Products\BranchSettings;
use App\Support\SpreadsheetFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Carga masiva de productos desde Excel o CSV.
 *
 * Cada producto queda como uno nuevo del formulario: habilitado en las sucursales indicadas (todas si se deja
 * vacío) con el precio general, todos los días y todo el día. Opciones, receta e imagen se cargan después
 * editando el producto. Es todo o nada: si una fila tiene errores no se guarda ninguna.
 */
class ProductImport
{
    /** Columnas obligatorias en el archivo; las demás pueden faltar. */
    private const REQUIRED = ['nombre', 'categoria', 'precio'];

    private const STATUSES = ['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo'];

    /** @return array<string, string> encabezado => explicación */
    public static function columns(): array
    {
        return [
            'nombre' => 'Obligatorio, hasta 70 caracteres. Se guarda en mayúsculas y no puede repetir un producto existente.',
            'categoria' => 'Obligatorio: nombre de una categoría activa (por ejemplo PLATOS).',
            'precio' => 'Obligatorio: precio de venta en Bs (por ejemplo 38 o 38.50).',
            'precio_de_costo' => 'Opcional: precio de costo en Bs (por defecto 0).',
            'codigo_de_barras' => 'Opcional, hasta 30 caracteres. No puede repetirse.',
            'cantidad' => 'Opcional: unidades que agrega cada toque en el POS (por defecto 1).',
            'color' => 'Opcional: color del botón en hexadecimal (por defecto #337ab7).',
            'estado' => 'Opcional: Activo o Inactivo (por defecto Activo).',
            'sucursales' => 'Opcional: sucursales donde se vende, separadas por coma. Vacío: todas.',
            'imprime_en' => 'Opcional: impresoras de la comanda separadas por coma ('.implode(', ', ProductController::PRINTERS).'). Vacío: ninguna.',
        ];
    }

    public static function template(): StreamedResponse
    {
        $categories = Category::where('status', 'ACTIVE')->orderBy('order_number')->pluck('group_name')->all();
        $category = $categories[0] ?? 'PLATOS';

        return SpreadsheetFile::template('plantilla-productos', 'Productos', static::columns(), [
            ['MILANESA DE POLLO', $category, 38, 22.5, '', 1, '#d9534f', 'Activo', '', 'Cocina'],
            ['SOPA DE MANÍ', $category, 15.5, '', '', 1, '', '', '', ''],
        ], [
            'categoria' => $categories,
            'estado' => array_values(self::STATUSES),
        ], ['codigo_de_barras']);
    }

    /**
     * @return int productos creados
     *
     * @throws ValidationException
     */
    public static function run(UploadedFile $file): int
    {
        $rows = SpreadsheetFile::read($file, self::REQUIRED);

        $categories = Category::where('status', 'ACTIVE')->pluck('id', 'group_name')->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id])->all();
        $branches = Branch::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id])->all();
        $printers = collect(ProductController::PRINTERS)->mapWithKeys(fn ($label, $key) => [mb_strtolower($label) => $key])->all();
        $names = Product::pluck('product_name')->map(fn ($n) => mb_strtolower($n))->flip()->all();
        $barcodes = Product::whereNotNull('barcode')->where('barcode', '<>', '')->pluck('barcode')->flip()->all();

        $errors = [];
        $products = [];
        $seenNames = [];
        $seenBarcodes = [];

        foreach ($rows as $line => $cells) {
            $error = function (string $message) use (&$errors, $line) {
                $errors[] = "Fila {$line}: {$message}";
            };

            $data = [
                'product_name' => mb_strtoupper($cells['nombre'] ?? ''),
                'sale_price' => static::number($cells['precio'] ?? ''),
                'cost_price' => ($cells['precio_de_costo'] ?? '') === '' ? 0 : static::number($cells['precio_de_costo']),
                'barcode' => ($cells['codigo_de_barras'] ?? '') ?: null,
                'quantity' => ($cells['cantidad'] ?? '') === '' ? 1 : static::number($cells['cantidad']),
                'color' => ($cells['color'] ?? '') ?: '#337ab7',
                'status' => static::status($cells['estado'] ?? ''),
            ];

            $validator = Validator::make($data, [
                'product_name' => 'required|string|max:70',
                'sale_price' => 'required|numeric|min:0',
                'cost_price' => 'numeric|min:0',
                'barcode' => 'nullable|string|max:30',
                'quantity' => 'numeric|gt:0',
                'color' => 'string|max:40',
                'status' => 'required|in:ACTIVE,INACTIVE',
            ], [], [
                'product_name' => 'nombre',
                'sale_price' => 'precio',
                'cost_price' => 'precio de costo',
                'barcode' => 'código de barras',
                'quantity' => 'cantidad',
                'status' => 'estado',
            ]);
            foreach ($validator->errors()->all() as $message) {
                $error($message);
            }

            $category = mb_strtolower($cells['categoria'] ?? '');
            if ($category === '') {
                $error('falta la categoría.');
            } elseif (! isset($categories[$category])) {
                $error("la categoría «{$cells['categoria']}» no existe o está inactiva.");
            }
            $data['category_id'] = $categories[$category] ?? null;

            $name = mb_strtolower($data['product_name']);
            if ($name !== '' && isset($names[$name])) {
                $error("el producto «{$data['product_name']}» ya existe.");
            } elseif ($name !== '' && isset($seenNames[$name])) {
                $error("el producto «{$data['product_name']}» está repetido en la fila {$seenNames[$name]}.");
            }
            $seenNames[$name] ??= $line;

            if ($data['barcode'] !== null) {
                if (isset($barcodes[$data['barcode']])) {
                    $error("el código de barras {$data['barcode']} ya es de otro producto.");
                } elseif (isset($seenBarcodes[$data['barcode']])) {
                    $error("el código de barras {$data['barcode']} está repetido en la fila {$seenBarcodes[$data['barcode']]}.");
                }
                $seenBarcodes[$data['barcode']] ??= $line;
            }

            $branchIds = static::pick($cells['sucursales'] ?? '', $branches, fn ($v) => $error("la sucursal «{$v}» no existe."));
            $printerKeys = static::pick($cells['imprime_en'] ?? '', $printers, fn ($v) => $error("la impresora «{$v}» no existe."));

            $products[] = [
                'data' => $data,
                'branches' => $branchIds ?: array_values($branches),
                'printers' => $printerKeys,
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 50)]);
        }

        DB::transaction(function () use ($products) {
            foreach ($products as $item) {
                $product = Product::create($item['data'] + [
                    'unit_factor' => 1,
                    'options_mode' => '',
                    'timed' => '',
                    'available' => 'SI',
                ]);
                $product->updateQuietly(['product_code' => $product->id]);

                BranchSettings::save($product, collect($item['branches'])->mapWithKeys(fn ($id) => [$id => [
                    'enabled' => true,
                    'days' => array_keys(ProductController::DAYS),
                    'printers' => $item['printers'],
                ]])->all());
            }
        });

        return count($products);
    }

    /**
     * Valores separados por coma (o punto y coma) traducidos con el mapa nombre → valor. "Todas" o vacío: [].
     *
     * @param  array<string, mixed>  $map
     */
    private static function pick(string $text, array $map, callable $unknown): array
    {
        $values = [];
        foreach (preg_split('/[,;]/', $text) as $value) {
            $value = trim($value);
            if ($value === '' || mb_strtolower($value) === 'todas') {
                continue;
            }
            isset($map[mb_strtolower($value)]) ? $values[] = $map[mb_strtolower($value)] : $unknown($value);
        }

        return array_values(array_unique($values));
    }

    /** Acepta "38.50" y también "38,50" (CSV de Excel en español). */
    private static function number(string $value): string
    {
        return preg_match('/^-?\d+,\d+$/', $value) ? str_replace(',', '.', $value) : $value;
    }

    private static function status(string $value): string
    {
        if ($value === '') {
            return 'ACTIVE';
        }
        foreach (self::STATUSES as $key => $label) {
            if (in_array(mb_strtolower($value), [mb_strtolower($key), mb_strtolower($label)], true)) {
                return $key;
            }
        }

        return $value;
    }
}
