<?php

namespace App\Http\Controllers\Admin;

use App\Admin\ProductImport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ReportGroup;
use App\Models\Supply;
use App\Services\Products\BranchSettings;
use App\Support\SpreadsheetFile;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Productos del menú con su configuración por sucursal, opciones, receta y grupos de reporte.
 * Legacy: productos.php / productoController (guardar, modificarPoducto).
 */
class ProductController extends Controller
{
    public const DAYS = ['mon' => 'Lu', 'tue' => 'Ma', 'wed' => 'Mi', 'thu' => 'Ju', 'fri' => 'Vi', 'sat' => 'Sá', 'sun' => 'Do'];

    public const PRINTERS = ['print_kitchen' => 'Cocina', 'print_station_1' => 'Punto 1', 'print_station_2' => 'Punto 2', 'print_station_3' => 'Punto 3', 'print_station_4' => 'Punto 4'];

    /** Carpeta pública de imágenes de botones; el POS sólo muestra rutas que empiezan con "legacy/". */
    private const IMAGE_FOLDER = 'legacy/images/buttons';

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('category')
            ->withCount(['branches', 'recipe'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                ->where('product_name', 'ilike', '%'.$request->query('q').'%')
                ->orWhere('barcode', $request->query('q'))))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('product_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('order_number')->pluck('group_name', 'id'),
            'totalBranches' => Branch::count(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['status' => 'ACTIVE', 'quantity' => 1, 'unit_factor' => 1, 'color' => '#337ab7']));
    }

    public function store(Request $request): RedirectResponse
    {
        $product = $this->save($request, new Product);

        return redirect()->route('admin.products.edit', $product)->with('ok', 'Producto creado.');
    }

    public function importTemplate(): StreamedResponse
    {
        return ProductImport::template();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|max:5120|extensions:'.implode(',', SpreadsheetFile::EXTENSIONS)], [], ['file' => 'archivo']);
        $count = ProductImport::run($request->file('file'));

        return redirect()->route('admin.products.index')
            ->with('ok', 'Carga masiva completada: '.($count === 1 ? '1 producto creado.' : "{$count} productos creados."));
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load(['branches', 'options', 'recipe', 'groups']));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->save($request, $product);

        return redirect()->route('admin.products.edit', $product)->with('ok', 'Producto actualizado.');
    }

    /**
     * Los productos no se borran (tienen ventas): se desactivan.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['status' => 'INACTIVE']);

        return back()->with('ok', "«{$product->product_name}» fue desactivado.");
    }

    private function form(Product $product): View
    {
        $configuration = $product->branches->keyBy('branch_id');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::where('status', 'ACTIVE')->orWhere('id', $product->category_id)->orderBy('order_number')->pluck('group_name', 'id'),
            'branches' => Branch::orderBy('name')->get(),
            'configuration' => $configuration,
            'supplies' => Supply::where('status', 'ACTIVE')->orderBy('supply_name')->get(['id', 'supply_name', 'measure']),
            'groups' => ReportGroup::orderBy('order_number')->pluck('group_name', 'id'),
            'recipe' => old('recipe', $product->recipe->map(fn ($r) => ['supply_id' => $r->supply_id, 'quantity' => (float) $r->quantity])->values()->all()),
            'groupsProduct' => old('report_groups', $product->groups->map(fn ($g) => ['report_group_id' => $g->report_group_id, 'factor' => (float) $g->factor])->values()->all()),
        ]);
    }

    private function save(Request $request, Product $product): Product
    {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:70', Tenant::unique('products', 'product_name')->ignore($product->id)],
            'category_id' => ['required', Tenant::exists('categories')],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'gt:0'],
            'unit_factor' => ['nullable', 'numeric', 'gt:0'],
            'barcode' => ['nullable', 'string', 'max:30', Tenant::unique('products', 'barcode')->ignore($product->id)],
            'color' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'asks_options' => ['boolean'],
            'por_time' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['boolean'],
            'options' => ['nullable', 'string', 'max:2000'],
            'branches' => ['nullable', 'array'],
            'branches.*.enabled' => ['boolean'],
            'branches.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'branches.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'branches.*.start_time' => ['nullable', 'date_format:H:i'],
            'branches.*.end_time' => ['nullable', 'date_format:H:i'],
            'branches.*.expires_on' => ['nullable', 'date'],
            'branches.*.days' => ['nullable', 'array'],
            'branches.*.printers' => ['nullable', 'array'],
            'recipe' => ['nullable', 'array'],
            'recipe.*.supply_id' => ['required', 'distinct', Tenant::exists('supplies')],
            'recipe.*.quantity' => ['required', 'numeric', 'gt:0'],
            'report_groups' => ['nullable', 'array'],
            'report_groups.*.report_group_id' => ['required', 'distinct', Tenant::exists('report_groups')],
            'report_groups.*.factor' => ['required', 'numeric', 'gt:0'],
        ], [], [
            'product_name' => 'nombre',
            'category_id' => 'categoría',
            'sale_price' => 'precio de venta',
            'cost_price' => 'precio de costo',
            'barcode' => 'código de barras',
            'recipe.*.supply_id' => 'insumo de la receta',
            'recipe.*.quantity' => 'cantidad de la receta',
            'report_groups.*.report_group_id' => 'grupo de reporte',
            'report_groups.*.factor' => 'factor del grupo',
        ]);

        return DB::transaction(function () use ($request, $product, $data) {
            $product->fill([
                'product_name' => mb_strtoupper(trim($data['product_name'])),
                'category_id' => $data['category_id'],
                'sale_price' => $data['sale_price'],
                'cost_price' => $data['cost_price'] ?? 0,
                'quantity' => $data['quantity'] ?? 1,
                'unit_factor' => $data['unit_factor'] ?? 1,
                'barcode' => $data['barcode'] ?? null,
                'color' => ($data['color'] ?? null) ?: '#337ab7',
                'status' => $data['status'],
                'options_mode' => $request->boolean('asks_options') ? 'OPCIONES' : '',
                'timed' => $request->boolean('por_time') ? 'T' : '',
                'available' => 'SI',
            ]);

            if ($request->boolean('remove_image')) {
                $product->image_path = '';
            }
            if ($request->hasFile('image')) {
                $file = Str::slug($product->product_name).'-'.Str::random(6).'.'.$request->file('image')->extension();
                $request->file('image')->move(public_path(self::IMAGE_FOLDER), $file);
                $product->image_path = self::IMAGE_FOLDER.'/'.$file;
            }

            $product->save();
            $product->updateQuietly(['product_code' => $product->id]);

            BranchSettings::save($product, $data['branches'] ?? []);

            $product->options()->delete();
            foreach (array_filter(array_map('trim', preg_split('/\R/', (string) ($data['options'] ?? '')))) as $option_name) {
                $product->options()->create(['option_name' => mb_strtoupper($option_name), 'product_name' => $product->product_name]);
            }

            $product->recipe()->delete();
            foreach ($data['recipe'] ?? [] as $item) {
                $product->recipe()->create($item);
            }

            $product->groups()->delete();
            foreach ($data['report_groups'] ?? [] as $item) {
                $product->groups()->create($item + ['product_name' => $product->product_name, 'quantity' => $product->quantity, 'unit' => $product->unit_factor]);
            }

            return $product;
        });
    }
}
