<?php

namespace App\Admin;

use App\Exceptions\PosException;
use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\InvoiceSequence;
use App\Models\MovementReason;
use App\Models\ReportGroup;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Definición declarativa de los catálogos simples del panel (CatalogoController).
 *
 * Cada catálogo indica:
 *  - modelo, titulo, singular, icono, descripcion
 *  - feminine:  true si singular es femenino (ver Catalogs::agree)
 *  - buscar:    columnas para la búsqueda de texto
 *  - orden:     [columna, dirección]
 *  - sucursal:  true si se filtra por sucursal (columna sucursal_id)
 *  - columnas:  columnas del listado [campo => [etiqueta, formato, valor?]]
 *               formatos: texto, estado, color, moneda, numero
 *  - campos:    campos del formulario [campo => [etiqueta, tipo, reglas, opciones?, ancho?, ayuda?]]
 *  - eliminar:  'borrar' o 'status:VALUE' (baja lógica)
 *  - consulta:  ajuste de la consulta del listado
 *  - despues:   acción luego de guardar (fn (Model $modelo, bool $nuevo))
 *  - sequence:  campo de orden opcional: vacío al crear queda al final (por sucursal si 'branch'); al editar no cambia
 *  - import:    carga masiva desde CSV (ver App\Admin\CatalogImport)
 */
class Catalogs
{
    public static function all(): array
    {
        $statuses = ['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo'];
        $branches = fn () => Branch::orderBy('name')->pluck('name', 'id')->all();

        return [
            'categories' => [
                'model' => Category::class,
                'title' => 'Categorías del menú',
                'singular' => 'categoría',
                'feminine' => true,
                'icon' => 'fa-th-large',
                'description' => 'Botones del menú del POS. El orden define la posición en pantalla.',
                'search' => ['group_name'],
                'order_number' => ['order_number', 'asc'],
                'sequence' => 'order_number',
                'columns' => [
                    'group_name' => ['Nombre', 'text'],
                    'color' => ['Color', 'color'],
                    'order_number' => ['Orden', 'number'],
                    'products_count' => ['Productos', 'number'],
                    'status' => ['Estado', 'status'],
                ],
                'query' => fn (Builder $q) => $q->withCount('products'),
                'fields' => [
                    'group_name' => ['Nombre', 'text', 'required|string|max:60', 'width' => 'sm:col-span-2'],
                    'color' => ['Color del botón', 'color', 'nullable|string|max:15'],
                    'order_number' => ['Orden', 'number', 'nullable|integer|min:0', 'help' => 'Opcional. Si lo deja vacío, queda al final.'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
                'after' => fn (Model $m) => $m->updateQuietly(['group_code' => (string) $m->id, 'category_group' => $m->id]),
                'import' => [
                    'columns' => ['nombre' => 'group_name', 'color' => 'color', 'orden' => 'order_number', 'estado' => 'status'],
                    'defaults' => ['status' => 'ACTIVE'],
                    'unique' => 'group_name',
                    'help' => [
                        'nombre' => 'Obligatorio, hasta 60 caracteres. No puede repetir una categoría existente.',
                        'color' => 'Opcional, en hexadecimal (#d9534f).',
                        'orden' => 'Opcional, número entero: posición del botón en el POS. Vacío: al final.',
                        'estado' => 'Opcional: Activo o Inactivo (por defecto Activo).',
                    ],
                    'example' => [
                        ['SOPAS', '#d9534f', 4, 'Activo'],
                        ['JUGOS', '#337ab7', 5, 'Activo'],
                    ],
                ],
            ],

            'report_groups' => [
                'model' => ReportGroup::class,
                'title' => 'Grupos de reporte',
                'singular' => 'grupo',
                'icon' => 'fa-object-group',
                'description' => 'Agrupan productos para contar unidades en los reportes (p. ej. presas de pollo vendidas).',
                'search' => ['group_name', 'group_code'],
                'order_number' => ['order_number', 'asc'],
                'sequence' => 'order_number',
                'columns' => [
                    'group_code' => ['Código', 'text'],
                    'group_name' => ['Nombre', 'text'],
                    'order_number' => ['Orden', 'number'],
                    'products_count' => ['Productos', 'number'],
                ],
                'query' => fn (Builder $q) => $q->withCount('products'),
                'fields' => [
                    'group_code' => ['Código', 'text', 'nullable|string|max:10'],
                    'group_name' => ['Nombre', 'text', 'required|string|max:30'],
                    'order_number' => ['Orden', 'number', 'nullable|integer|min:0', 'help' => 'Opcional. Si lo deja vacío, queda al final.'],
                ],
                'delete' => 'delete',
            ],

            'customers' => [
                'model' => Customer::class,
                'title' => 'Clientes',
                'singular' => 'cliente',
                'icon' => 'fa-address-book-o',
                'description' => 'Se registran al facturar o desde el POS; aquí puede agregarlos o corregir sus datos.',
                'search' => ['customer_name', 'customer_tax_id', 'customer_phone', 'customer_mobile'],
                'order_number' => ['customer_name', 'asc'],
                'columns' => [
                    'customer_name' => ['Nombre / razón social', 'text'],
                    'customer_tax_id' => ['NIT / CI', 'text'],
                    'customer_phone' => ['Teléfono', 'text'],
                    'customer_mobile' => ['Celular', 'text'],
                    'sales_count' => ['Compras', 'number'],
                ],
                'query' => fn (Builder $q) => $q->withCount('sales'),
                'fields' => [
                    'customer_name' => ['Nombre / razón social', 'text', 'required|string|max:100', 'width' => 'sm:col-span-2'],
                    'customer_tax_id' => ['NIT', 'text', 'nullable|regex:/^[0-9]*$/|max:20'],
                    'customer_id_number' => ['CI', 'text', 'nullable|string|max:15'],
                    'customer_phone' => ['Teléfono', 'text', 'nullable|string|max:20'],
                    'customer_mobile' => ['Celular', 'text', 'nullable|string|max:20'],
                    'customer_email' => ['Correo', 'email', 'nullable|email|max:100'],
                    'birth_date' => ['Fecha de nacimiento', 'date', 'nullable|date'],
                    'customer_address' => ['Dirección', 'textarea', 'nullable|string|max:500', 'width' => 'sm:col-span-4'],
                ],
                'delete' => 'delete',
            ],

            'dining_tables' => [
                'model' => DiningTable::class,
                'title' => 'Mesas',
                'singular' => 'mesa',
                'feminine' => true,
                'icon' => 'fa-square-o',
                'description' => 'Mesas del salón de cada sucursal, en el orden en que aparecen en el POS.',
                'search' => ['table_number'],
                'order_number' => ['order_number', 'asc'],
                'sequence' => 'order_number',
                'branch' => true,
                'columns' => [
                    'table_number' => ['Mesa', 'text'],
                    'branch' => ['Sucursal', 'text', fn ($m) => $m->branch?->name],
                    'order_number' => ['Orden', 'number'],
                    'occupied' => ['Situación', 'text', fn ($m) => $m->isOccupied() ? 'OCUPADA' : 'LIBRE'],
                    'status' => ['Estado', 'status'],
                ],
                'query' => fn (Builder $q) => $q->with('branch')->where('status', '<>', 'DELETED'),
                'fields' => [
                    'table_number' => ['Número / nombre', 'text', 'required|string|max:10'],
                    'branch_id' => ['Sucursal', 'select', 'required|'.Tenant::exists('branches'), $branches],
                    'order_number' => ['Orden', 'number', 'nullable|integer|min:0', 'help' => 'Opcional. Si lo deja vacío, queda al final.'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:DELETED',
            ],

            'staff' => [
                'model' => Staff::class,
                'title' => 'Meseros y personal',
                'singular' => 'persona',
                'feminine' => true,
                'icon' => 'fa-id-badge',
                'description' => 'Personal que atiende mesas. El color identifica las mesas de cada mesero y el código se pide al abrir una mesa.',
                'search' => ['staff_name', 'staff_id_number', 'position'],
                'order_number' => ['staff_name', 'asc'],
                'branch' => true,
                'columns' => [
                    'staff_name' => ['Nombre', 'text'],
                    'position' => ['Cargo', 'text'],
                    'staff_color' => ['Color', 'color'],
                    'branch' => ['Sucursal', 'text', fn ($m) => $m->branch?->name ?? 'Todas'],
                    'status' => ['Estado', 'status'],
                ],
                'query' => fn (Builder $q) => $q->with('branch'),
                'fields' => [
                    'staff_name' => ['Nombre completo', 'text', 'required|string|max:60', 'width' => 'sm:col-span-2'],
                    'position' => ['Cargo', 'text', 'nullable|string|max:30'],
                    'staff_id_number' => ['CI', 'text', 'nullable|string|max:15'],
                    'staff_address' => ['Dirección', 'text', 'nullable|string|max:100', 'width' => 'sm:col-span-2'],
                    'salary' => ['Sueldo (Bs)', 'decimal', 'nullable|numeric|min:0'],
                    'commission_rate' => ['Comisión (%)', 'number', 'nullable|integer|min:0|max:100'],
                    'staff_color' => ['Color', 'color', 'nullable|string|max:60'],
                    'code' => ['Código para abrir mesa', 'text', 'nullable|string|max:10'],
                    'branch_id' => ['Sucursal', 'select', 'nullable|'.Tenant::exists('branches'), $branches, 'empty' => 'Todas las sucursales'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
            ],

            'supplies' => [
                'model' => Supply::class,
                'title' => 'Insumos',
                'singular' => 'insumo',
                'icon' => 'fa-lemon-o',
                'description' => 'Materia prima que descuentan las recetas de los productos. El stock se gestiona en Movimientos de inventario.',
                'search' => ['supply_name', 'supply_code'],
                'order_number' => ['supply_name', 'asc'],
                'columns' => [
                    'supply_name' => ['Insumo', 'text'],
                    'measure' => ['Unidad', 'text'],
                    'stock_total' => ['Stock total', 'number'],
                    'min_stock' => ['Stock mínimo', 'number'],
                    'average_cost' => ['Costo prom. (Bs)', 'currency', fn ($m) => $m->average_cost ?? $m->cost_price],
                    'status' => ['Estado', 'status'],
                ],
                'query' => fn (Builder $q) => $q->withSum('stocks as stock_total', 'current_stock'),
                'fields' => [
                    'supply_name' => ['Nombre', 'text', 'required|string|max:60', 'width' => 'sm:col-span-2'],
                    'measure' => ['Unidad de medida', 'select', 'required|string|max:20', fn () => Unit::orderBy('name')->pluck('name', 'abbreviation')->all()],
                    'min_stock' => ['Stock mínimo', 'decimal', 'nullable|numeric|min:0'],
                    'cost_price' => ['Costo unitario de referencia (Bs)', 'decimal', 'nullable|numeric|min:0'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
                'after' => fn (Model $m) => $m->updateQuietly(['supply_code' => (string) $m->id]),
            ],

            'warehouses' => [
                'model' => Warehouse::class,
                'title' => 'Almacenes',
                'singular' => 'almacén',
                'icon' => 'fa-archive',
                'search' => ['name'],
                'order_number' => ['name', 'asc'],
                'branch' => true,
                'columns' => [
                    'name' => ['Almacén', 'text'],
                    'branch' => ['Sucursal', 'text', fn ($m) => $m->branch?->name],
                    'status' => ['Estado', 'status'],
                ],
                'query' => fn (Builder $q) => $q->with('branch'),
                'fields' => [
                    'name' => ['Nombre', 'text', 'required|string|max:100', 'width' => 'sm:col-span-2'],
                    'branch_id' => ['Sucursal', 'select', 'required|'.Tenant::exists('branches'), $branches],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
            ],

            'suppliers' => [
                'model' => Supplier::class,
                'title' => 'Proveedores',
                'singular' => 'proveedor',
                'icon' => 'fa-truck',
                'search' => ['name', 'contact_name', 'phone'],
                'order_number' => ['name', 'asc'],
                'columns' => [
                    'name' => ['Proveedor', 'text'],
                    'phone' => ['Teléfono', 'text'],
                    'contact_name' => ['Contacto', 'text'],
                    'contact_phone' => ['Tel. contacto', 'text'],
                    'status' => ['Estado', 'status'],
                ],
                'fields' => [
                    'name' => ['Nombre / razón social', 'text', 'required|string|max:150', 'width' => 'sm:col-span-2'],
                    'phone' => ['Teléfono', 'text', 'nullable|string|max:30'],
                    'city_id' => ['Ciudad', 'select', 'nullable|exists:cities,id', fn () => City::orderBy('name')->pluck('name', 'id')->all(), 'empty' => '—'],
                    'address' => ['Dirección', 'text', 'nullable|string|max:255', 'width' => 'sm:col-span-2'],
                    'contact_name' => ['Persona de contacto', 'text', 'nullable|string|max:100'],
                    'contact_phone' => ['Teléfono de contacto', 'text', 'nullable|string|max:30'],
                    'contact_email' => ['Correo', 'email', 'nullable|email|max:255'],
                    'website' => ['Página web', 'text', 'nullable|string|max:255'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
            ],

            'units' => [
                'model' => Unit::class,
                'title' => 'Unidades de medida',
                'singular' => 'unidad',
                'feminine' => true,
                'icon' => 'fa-balance-scale',
                'search' => ['name', 'abbreviation'],
                'order_number' => ['name', 'asc'],
                'columns' => [
                    'name' => ['Unidad', 'text'],
                    'abbreviation' => ['Abreviatura', 'text'],
                    'status' => ['Estado', 'status'],
                ],
                'fields' => [
                    'name' => ['Nombre', 'text', 'required|string|max:30'],
                    'abbreviation' => ['Abreviatura', 'text', 'required|string|max:30'],
                    'status' => ['Estado', 'select', 'required|in:0,1', ['1' => 'Activo', '0' => 'Inactivo']],
                ],
                'delete' => 'status:0',
            ],

            'reasons' => [
                'model' => MovementReason::class,
                'title' => 'Motivos de movimiento',
                'singular' => 'motivo',
                'icon' => 'fa-tags',
                'description' => 'Clasifican los movimientos de inventario (compra, merma, traspaso...). VENTA lo usa el sistema al descontar recetas.',
                'search' => ['name'],
                'order_number' => ['name', 'asc'],
                'columns' => [
                    'name' => ['Motivo', 'text'],
                    'status' => ['Estado', 'status'],
                ],
                'fields' => [
                    'name' => ['Nombre', 'text', 'required|string|max:50'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
            ],

            'branches' => [
                'model' => Branch::class,
                'title' => 'Sucursales',
                'singular' => 'sucursal',
                'feminine' => true,
                'icon' => 'fa-building-o',
                'description' => 'Locales del restaurante. Cada sucursal necesita una dosificación (Facturación SIN) para poder vender.',
                'search' => ['name', 'address'],
                'order_number' => ['name', 'asc'],
                'columns' => [
                    'name' => ['Sucursal', 'text'],
                    'type' => ['Tipo', 'text', fn ($s) => $s->is_main ? 'Casa matriz' : 'Sucursal'],
                    'address' => ['Dirección', 'text'],
                    'phone' => ['Teléfono', 'text'],
                    'print_ip' => ['IP de impresión', 'text'],
                    'status' => ['Estado', 'status'],
                ],
                'fields' => [
                    'name' => ['Nombre', 'text', 'required|string|max:100', 'width' => 'sm:col-span-2'],
                    'phone' => ['Teléfono', 'text', 'nullable|string|max:20'],
                    'city_id' => ['Ciudad', 'select', 'nullable|exists:cities,id', fn () => City::orderBy('name')->pluck('name', 'id')->all(), 'empty' => '—'],
                    'address' => ['Dirección', 'text', 'nullable|string|max:250', 'width' => 'sm:col-span-2'],
                    'print_ip' => ['IP del agente de impresión', 'text', 'nullable|string|max:60', 'help' => 'Equipo de la caja que imprime facturas y comandas (puerto 8080).'],
                    'status' => ['Estado', 'select', 'required|in:ACTIVE,INACTIVE', $statuses],
                ],
                'delete' => 'status:INACTIVE',
                'before' => function (Model $m, array $data) {
                    if ($m->is_main && ($data['status'] ?? 'ACTIVE') !== 'ACTIVE') {
                        throw new PosException('La casa matriz no se puede dar de baja.');
                    }
                },
                'after' => function (Model $m, bool $is_new) {
                    if ($is_new) {
                        InvoiceSequence::firstOrCreate(['table_name' => config('pos.sales_sequence'), 'branch_id' => $m->id]);
                    }
                },
            ],
        ];
    }

    /**
     * Concuerda una palabra terminada en "o" con el género del catálogo: Nuevo/Nueva, registrado/registrada.
     */
    public static function agree(array $def, string $word): string
    {
        return ($def['feminine'] ?? false) ? substr($word, 0, -1).'a' : $word;
    }

    public static function get(string $slug): array
    {
        $catalogs = static::all();
        abort_unless(isset($catalogs[$slug]), 404);

        return $catalogs[$slug] + ['slug' => $slug];
    }
}
