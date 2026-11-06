<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\ProductoOpcion;
use App\Models\ProductoSucursal;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use App\Support\Restaurante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Menú del POS: categorías, productos, búsqueda, opciones y receta.
 * Port de menuGrupoController, productoController, opProductoController e insumoProductoController.
 */
class CatalogoController extends Controller
{
    public function __construct(private PosContexto $pos) {}

    /**
     * Categorías con productos habilitados en la sucursal, en el orden del menú.
     */
    public function categorias(): JsonResponse
    {
        $categorias = Categoria::query()
            ->whereIn('id', ProductoSucursal::where('sucursal_id', $this->pos->sucursalId())->select('categoria_id'))
            ->orderBy('orden')
            ->get();

        return RespuestaPos::ok(RespuestaPos::minusculas($categorias));
    }

    public function productosPorCategoria(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'integer']]);

        $productos = Producto::paraPos($this->pos->sucursalId())
            ->where('ps.categoria_id', $request->integer('id'))
            ->where('ps.f_vence', '>=', today())
            ->orderBy('productos.id')
            ->get();

        return RespuestaPos::ok(RespuestaPos::minusculas($productos));
    }

    public function buscar(Request $request): JsonResponse
    {
        $texto = trim((string) $request->input('nomProd'));

        $productos = Producto::paraPos($this->pos->sucursalId())
            ->where('productos.nom_prod', 'ilike', '%'.$texto.'%')
            ->orderBy('productos.nom_prod')
            ->limit(100)
            ->get();

        return RespuestaPos::ok(RespuestaPos::minusculas($productos));
    }

    public function buscarCodigoBarra(Request $request): JsonResponse
    {
        $productos = Producto::paraPos($this->pos->sucursalId())
            ->where('productos.barcode', (string) $request->input('codProdBarra'))
            ->get();

        return RespuestaPos::ok($productos->isEmpty() ? '' : RespuestaPos::minusculas($productos));
    }

    public function desactivar(Request $request): JsonResponse
    {
        Producto::whereKey($request->integer('idProducto'))->update(['estado' => 'INACTIVO']);

        return RespuestaPos::ok();
    }

    /**
     * Cambia el producto de categoría en el menú de la sucursal actual.
     */
    public function mover(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'idProducto' => ['required', 'integer', Restaurante::existe('productos')],
            'grupo' => ['required', 'integer', Restaurante::existe('categorias')],
        ]);

        DB::transaction(function () use ($datos) {
            Producto::whereKey($datos['idProducto'])->update(['categoria_id' => $datos['grupo']]);
            ProductoSucursal::where('producto_id', $datos['idProducto'])
                ->where('sucursal_id', $this->pos->sucursalId())
                ->update(['categoria_id' => $datos['grupo']]);
        });

        return RespuestaPos::ok();
    }

    public function opciones(Request $request): JsonResponse
    {
        $opciones = ProductoOpcion::where('producto_id', $request->integer('idProducto'))->get();

        return RespuestaPos::ok(RespuestaPos::mayusculas($opciones, ['producto_id' => 'COD_PROD']));
    }

    public function insumos(Request $request): JsonResponse
    {
        // El stock mostrado es el del almacén por defecto del punto de venta
        $insumos = Insumo::query()
            ->join('insumo_producto as ip', 'ip.insumo_id', '=', 'insumos.id')
            ->leftJoin('insumo_almacen as ia', fn ($join) => $join->on('ia.insumo_id', '=', 'insumos.id')
                ->where('ia.almacen_id', $this->pos->almacenId()))
            ->where('ip.producto_id', $request->integer('idProducto'))
            ->select('insumos.*', 'ip.cantidad as cantidad_receta')
            ->selectRaw('coalesce(ia.stock_actual, 0) as stock_act')
            ->get();

        return RespuestaPos::ok(RespuestaPos::mayusculas($insumos));
    }
}
