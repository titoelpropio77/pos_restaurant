<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Supply;
use App\Support\PosContext;
use App\Support\PosResponse;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Menú del POS: categorías, productos, búsqueda, opciones y receta.
 * Port de menuGrupoController, productoController, opProductoController e insumoProductoController.
 */
class CatalogController extends Controller
{
    public function __construct(private PosContext $pos) {}

    /**
     * Categorías con productos habilitados en la sucursal, en el orden del menú.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->whereIn('id', BranchProduct::where('branch_id', $this->pos->branchId())->select('category_id'))
            ->orderBy('order_number')
            ->get();

        return PosResponse::ok(PosResponse::rows($categories));
    }

    public function productsPorCategory(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'integer']]);

        $products = Product::forPos($this->pos->branchId())
            ->where('ps.category_id', $request->integer('id'))
            ->where('ps.expires_on', '>=', today())
            ->orderBy('products.id')
            ->get();

        return PosResponse::ok(PosResponse::rows($products));
    }

    public function search(Request $request): JsonResponse
    {
        $text = trim((string) $request->input('productName'));

        $products = Product::forPos($this->pos->branchId())
            ->where('products.product_name', 'ilike', '%'.$text.'%')
            ->orderBy('products.product_name')
            ->limit(100)
            ->get();

        return PosResponse::ok(PosResponse::rows($products));
    }

    public function searchBarcode(Request $request): JsonResponse
    {
        $products = Product::forPos($this->pos->branchId())
            ->where('products.barcode', (string) $request->input('productCodeBar'))
            ->get();

        return PosResponse::ok($products->isEmpty() ? '' : PosResponse::rows($products));
    }

    public function deactivate(Request $request): JsonResponse
    {
        Product::whereKey($request->integer('idProduct'))->update(['status' => 'INACTIVE']);

        return PosResponse::ok();
    }

    /**
     * Cambia el producto de categoría en el menú de la sucursal actual.
     */
    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idProduct' => ['required', 'integer', Tenant::exists('products')],
            'category_group' => ['required', 'integer', Tenant::exists('categories')],
        ]);

        DB::transaction(function () use ($data) {
            Product::whereKey($data['idProduct'])->update(['category_id' => $data['category_group']]);
            BranchProduct::where('product_id', $data['idProduct'])
                ->where('branch_id', $this->pos->branchId())
                ->update(['category_id' => $data['category_group']]);
        });

        return PosResponse::ok();
    }

    public function options(Request $request): JsonResponse
    {
        $options = ProductOption::where('product_id', $request->integer('idProduct'))->get();

        return PosResponse::ok(PosResponse::rows($options, ['product_id' => 'product_code']));
    }

    public function supplies(Request $request): JsonResponse
    {
        // El stock mostrado es el del almacén por defecto del punto de venta
        $supplies = Supply::query()
            ->join('recipe_items as ip', 'ip.supply_id', '=', 'supplies.id')
            ->leftJoin('warehouse_stocks as ia', fn ($join) => $join->on('ia.supply_id', '=', 'supplies.id')
                ->where('ia.warehouse_id', $this->pos->warehouseId()))
            ->where('ip.product_id', $request->integer('idProduct'))
            ->select('supplies.*', 'ip.quantity as recipe_quantity')
            ->selectRaw('coalesce(ia.current_stock, 0) as current_stock')
            ->get();

        return PosResponse::ok(PosResponse::rows($supplies));
    }
}
