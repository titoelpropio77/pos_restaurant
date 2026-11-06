<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Grupo;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Support\Restaurante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Productos del menú con su configuración por sucursal, opciones, receta y grupos de reporte.
 * Legacy: productos.php / productoController (guardar, modificarPoducto).
 */
class ProductoController extends Controller
{
    public const DIAS = ['lun' => 'Lu', 'mar' => 'Ma', 'mier' => 'Mi', 'jue' => 'Ju', 'vie' => 'Vi', 'sab' => 'Sá', 'dom' => 'Do'];

    public const IMPRESORAS = ['impcoc' => 'Cocina', 'imppunt1' => 'Punto 1', 'imppunt2' => 'Punto 2', 'imppunt3' => 'Punto 3', 'imppunt4' => 'Punto 4'];

    /** Carpeta pública de imágenes de botones; el POS sólo muestra rutas que empiezan con "abms1/". */
    private const CARPETA_IMAGENES = 'abms1/imagenes/imagenboton';

    public function index(Request $request): View
    {
        $productos = Producto::query()
            ->with('categoria')
            ->withCount(['sucursales', 'receta'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q
                ->where('nom_prod', 'ilike', '%'.$request->query('q').'%')
                ->orWhere('barcode', $request->query('q'))))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria_id', $request->integer('categoria')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->orderBy('nom_prod')
            ->paginate(25)
            ->withQueryString();

        return view('admin.productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('orden')->pluck('nom_grupo', 'id'),
            'totalSucursales' => Sucursal::count(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Producto(['estado' => 'ACTIVO', 'cantidad' => 1, 'unid' => 1, 'colores' => '#337ab7']));
    }

    public function store(Request $request): RedirectResponse
    {
        $producto = $this->guardar($request, new Producto);

        return redirect()->route('admin.productos.edit', $producto)->with('ok', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        return $this->formulario($producto->load(['sucursales', 'opciones', 'receta', 'grupos']));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $this->guardar($request, $producto);

        return redirect()->route('admin.productos.edit', $producto)->with('ok', 'Producto actualizado.');
    }

    /**
     * Los productos no se borran (tienen ventas): se desactivan.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->update(['estado' => 'INACTIVO']);

        return back()->with('ok', "«{$producto->nom_prod}» fue desactivado.");
    }

    private function formulario(Producto $producto): View
    {
        $configuracion = $producto->sucursales->keyBy('sucursal_id');

        return view('admin.productos.form', [
            'producto' => $producto,
            'categorias' => Categoria::where('estado', 'ACTIVO')->orWhere('id', $producto->categoria_id)->orderBy('orden')->pluck('nom_grupo', 'id'),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'configuracion' => $configuracion,
            'insumos' => Insumo::where('estado', 'ACTIVO')->orderBy('nom_insumo')->get(['id', 'nom_insumo', 'medida']),
            'grupos' => Grupo::orderBy('orden')->pluck('nom_grupo', 'id'),
            'receta' => old('receta', $producto->receta->map(fn ($r) => ['insumo_id' => $r->insumo_id, 'cantidad' => (float) $r->cantidad])->values()->all()),
            'gruposProducto' => old('grupos', $producto->grupos->map(fn ($g) => ['grupo_id' => $g->grupo_id, 'factor' => (float) $g->factor])->values()->all()),
        ]);
    }

    private function guardar(Request $request, Producto $producto): Producto
    {
        $datos = $request->validate([
            'nom_prod' => ['required', 'string', 'max:70', Restaurante::unico('productos', 'nom_prod')->ignore($producto->id)],
            'categoria_id' => ['required', Restaurante::existe('categorias')],
            'pre_venta' => ['required', 'numeric', 'min:0'],
            'cantidad' => ['nullable', 'numeric', 'gt:0'],
            'unid' => ['nullable', 'numeric', 'gt:0'],
            'barcode' => ['nullable', 'string', 'max:30', Restaurante::unico('productos', 'barcode')->ignore($producto->id)],
            'colores' => ['nullable', 'string', 'max:40'],
            'familia' => ['nullable', 'string', 'max:20'],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
            'pide_opciones' => ['boolean'],
            'por_tiempo' => ['boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'quitar_imagen' => ['boolean'],
            'opciones' => ['nullable', 'string', 'max:2000'],
            'sucursales' => ['nullable', 'array'],
            'sucursales.*.habilitado' => ['boolean'],
            'sucursales.*.precio_venta' => ['nullable', 'numeric', 'min:0'],
            'sucursales.*.precio_venta_mayorista' => ['nullable', 'numeric', 'min:0'],
            'sucursales.*.hora_inicio' => ['nullable', 'date_format:H:i'],
            'sucursales.*.hora_fin' => ['nullable', 'date_format:H:i'],
            'sucursales.*.f_vence' => ['nullable', 'date'],
            'sucursales.*.dias' => ['nullable', 'array'],
            'sucursales.*.impresoras' => ['nullable', 'array'],
            'receta' => ['nullable', 'array'],
            'receta.*.insumo_id' => ['required', 'distinct', Restaurante::existe('insumos')],
            'receta.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'grupos' => ['nullable', 'array'],
            'grupos.*.grupo_id' => ['required', 'distinct', Restaurante::existe('grupos')],
            'grupos.*.factor' => ['required', 'numeric', 'gt:0'],
        ], [], [
            'nom_prod' => 'nombre',
            'categoria_id' => 'categoría',
            'pre_venta' => 'precio de venta',
            'barcode' => 'código de barras',
            'receta.*.insumo_id' => 'insumo de la receta',
            'receta.*.cantidad' => 'cantidad de la receta',
            'grupos.*.grupo_id' => 'grupo de reporte',
        ]);

        return DB::transaction(function () use ($request, $producto, $datos) {
            $producto->fill([
                'nom_prod' => mb_strtoupper(trim($datos['nom_prod'])),
                'categoria_id' => $datos['categoria_id'],
                'pre_venta' => $datos['pre_venta'],
                'cantidad' => $datos['cantidad'] ?? 1,
                'unid' => $datos['unid'] ?? 1,
                'barcode' => $datos['barcode'] ?? null,
                'colores' => ($datos['colores'] ?? null) ?: '#337ab7',
                'familia' => $datos['familia'] ?? null,
                'estado' => $datos['estado'],
                'presa' => $request->boolean('pide_opciones') ? 'OPCIONES' : '',
                'tiempo' => $request->boolean('por_tiempo') ? 'T' : '',
                'disponible' => 'SI',
            ]);

            if ($request->boolean('quitar_imagen')) {
                $producto->imgnormal = '';
            }
            if ($request->hasFile('imagen')) {
                $archivo = Str::slug($producto->nom_prod).'-'.Str::random(6).'.'.$request->file('imagen')->extension();
                $request->file('imagen')->move(public_path(self::CARPETA_IMAGENES), $archivo);
                $producto->imgnormal = self::CARPETA_IMAGENES.'/'.$archivo;
            }

            $producto->save();
            $producto->updateQuietly(['cod_prod' => $producto->id]);

            $this->guardarSucursales($producto, $datos['sucursales'] ?? []);

            $producto->opciones()->delete();
            foreach (array_filter(array_map('trim', preg_split('/\R/', (string) ($datos['opciones'] ?? '')))) as $opcion) {
                $producto->opciones()->create(['opcion' => mb_strtoupper($opcion), 'nom_prod' => $producto->nom_prod]);
            }

            $producto->receta()->delete();
            foreach ($datos['receta'] ?? [] as $item) {
                $producto->receta()->create($item);
            }

            $producto->grupos()->delete();
            foreach ($datos['grupos'] ?? [] as $item) {
                $producto->grupos()->create($item + ['nom_prod' => $producto->nom_prod, 'cantidad' => $producto->cantidad, 'unidad' => $producto->unid]);
            }

            return $producto;
        });
    }

    private function guardarSucursales(Producto $producto, array $sucursales): void
    {
        foreach ($sucursales as $sucursalId => $config) {
            $existente = $producto->sucursales()->where('sucursal_id', $sucursalId)->first();

            if (empty($config['habilitado'])) {
                $existente?->delete();

                continue;
            }

            $dias = $config['dias'] ?? [];
            $impresoras = $config['impresoras'] ?? [];
            $precio = ($config['precio_venta'] ?? '') !== '' && $config['precio_venta'] !== null ? $config['precio_venta'] : $producto->pre_venta;

            $valores = [
                'categoria_id' => $producto->categoria_id,
                'precio_venta' => $precio,
                'precio_venta_normal' => $precio,
                'precio_venta_mayorista' => $config['precio_venta_mayorista'] ?? null,
                'hora_inicio' => ($config['hora_inicio'] ?? null) ?: '00:00',
                'hora_fin' => ($config['hora_fin'] ?? null) ?: '00:00',
                'f_vence' => ($config['f_vence'] ?? null) ?: '2999-12-29',
            ];
            foreach (array_keys(self::DIAS) as $dia) {
                $valores[$dia] = in_array($dia, $dias, true) ? 'x' : '';
            }
            foreach (array_keys(self::IMPRESORAS) as $impresora) {
                $valores[$impresora] = in_array($impresora, $impresoras, true) ? 'x' : '';
            }

            $producto->sucursales()->updateOrCreate(['sucursal_id' => $sucursalId], $valores);
        }
    }
}
