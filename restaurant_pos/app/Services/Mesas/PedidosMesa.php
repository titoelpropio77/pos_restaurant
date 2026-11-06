<?php

namespace App\Services\Mesas;

use App\Exceptions\ErrorPos;
use App\Models\Mesa;
use App\Models\MesaPedido;
use App\Models\Personal;
use App\Models\Producto;
use App\Models\Usuario;
use App\Support\PosContexto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Atención en mesas: apertura, comandas adicionales, traspasos, cobro y anulación.
 * Port de productoMesaController.php.
 */
class PedidosMesa
{
    public function __construct(private PosContexto $pos) {}

    /**
     * Abre una mesa libre con los productos de la primera comanda.
     *
     * @return int Número de comanda de la mesa
     */
    public function abrir(Mesa $mesa, array $datos, Usuario $usuario): int
    {
        return DB::transaction(function () use ($mesa, $datos, $usuario) {
            $mesa = Mesa::lockForUpdate()->findOrFail($mesa->id);

            if ($mesa->estaOcupada()) {
                throw new ErrorPos('LA MESA YA ESTÁ OCUPADA');
            }

            $mesero = ($datos['meserosc'] ?? '') === 'meserosc'
                ? Personal::find($datos['idMesero'])
                : Personal::where('codigo', $datos['idMesero'])->first();

            if (! $mesero) {
                throw new ErrorPos('MESERO NO ENCONTRADO');
            }

            $comanda = $mesa->nroorden + 1;
            $hora = now()->format('G:i');

            foreach ($datos['idProducto'] as $i => $productoId) {
                $this->crearItem($mesa, $comanda, $i + 1, (int) $productoId, (float) $datos['cantidad'][$i], [
                    'f_venta' => $datos['fechaVenta'],
                    'dhoraven' => $hora,
                    'turno' => $datos['turno'] ?? '',
                    'salida' => 'MESA: '.$mesa->nro_mesa,
                    'agrupa1' => $datos['subtotal'][$i] ?? 0,
                    'login' => $usuario->usuario,
                    'nota' => $datos['nota'][$i] ?? '',
                    'nom_empl' => $mesero->nom_empl,
                    'tiempo_ini' => $hora,
                ], ($datos['billar'] ?? '') === 'billar');
            }

            $this->ocupar($mesa, $comanda, $mesero->coloremp, $datos['cantCli'] ?? '', $this->esSalon($datos));

            return $comanda;
        });
    }

    /**
     * Agrega una nueva comanda a una mesa ya ocupada.
     */
    public function agregar(Mesa $mesa, array $datos, Usuario $usuario): int
    {
        return DB::transaction(function () use ($mesa, $datos, $usuario) {
            $mesa = Mesa::lockForUpdate()->findOrFail($mesa->id);
            $fecha = $datos['fechaVenta'];

            if (($datos['tipoProducto'] ?? '') === 'T' && $this->tiempoAbierto($mesa, $fecha)) {
                throw new ErrorPos('* DEBE PARAR EL TIEMPO DE LA MESA');
            }

            $anterior = $this->detalle($mesa, $fecha)->first();
            $comanda = $mesa->nroorden + 1;
            $hora = now()->format('G:i');

            foreach ($datos['idProducto'] as $i => $productoId) {
                $this->crearItem($mesa, $comanda, $i + 1, (int) $productoId, (float) $datos['cantidad'][$i], [
                    'f_venta' => $fecha,
                    'dhoraven' => $hora,
                    'turno' => $anterior?->turno ?? '',
                    'salida' => (string) ($datos['nroMesa'] ?? $mesa->nro_mesa),
                    'login' => $usuario->usuario,
                    'nota' => $datos['notaProducto'][$i] ?? '',
                    'nom_empl' => $anterior?->nom_empl ?? '',
                    'tiempo_ini' => $hora,
                ]);
            }

            $mesa->update(['nroorden' => $comanda]);

            return $comanda;
        });
    }

    /**
     * Ítems abiertos de la mesa en la fecha de venta.
     */
    public function detalle(Mesa $mesa, string $fecha): Collection
    {
        return MesaPedido::where('mesa_id', $mesa->id)
            ->pendientes()
            ->where('cantidad', '<>', 0)
            ->where('f_venta', $fecha)
            ->orderBy('id')
            ->get();
    }

    /**
     * Tras cobrar en caja: opción 1 descuenta sólo lo pagado; opción 2 cierra toda la mesa.
     */
    public function actualizarTrasCobro(Mesa $mesa, int $opcion, array $idsDetalle, array $cantidades): void
    {
        DB::transaction(function () use ($mesa, $opcion, $idsDetalle, $cantidades) {
            if ($opcion === 1) {
                $mesa->update(['procesando' => '']);
                foreach ($idsDetalle as $i => $id) {
                    $item = MesaPedido::lockForUpdate()->findOrFail($id);
                    $item->update(['cantidad' => (float) $item->cantidad - (float) $cantidades[$i]]);
                }
            } elseif ($opcion === 2) {
                $mesa->liberar();
                MesaPedido::whereIn('id', $idsDetalle)->update(['cerrado' => 'A', 'deleteado' => 'S']);
            }

            $this->pos->setMesaProcesando(null);
        });
    }

    /**
     * Pasa ítems (total o parcialmente) de una mesa a otra.
     */
    public function traspasar(Mesa $origen, Mesa $destino, array $datos, Usuario $usuario): void
    {
        DB::transaction(function () use ($origen, $destino, $datos, $usuario) {
            $fecha = $datos['fecha'];
            $destinoLibre = ($datos['estadoMesa'] ?? '') === 'abierta';

            if ($destinoLibre) {
                $esSalon = $this->esSalon($datos);
                $this->ocupar($destino, 1, $origen->colores, $esSalon ? $origen->nom_cli : $origen->nropersona, $esSalon);
            }

            foreach ($datos['idDet'] as $i => $idDetalle) {
                $item = MesaPedido::lockForUpdate()->findOrFail($idDetalle);
                $cantidad = $item->tiempo === 'T' ? 1.0 : (float) $datos['cantidad'][$i];
                $restante = (float) $item->cantidad - $cantidad;

                if ($restante < 0) {
                    throw new ErrorPos('NO EXISTE ESA CANTIDAD EN ESA MESA');
                }

                $item->update($restante == 0 ? ['cantidad' => 0, 'deleteado' => 'X'] : ['cantidad' => $restante]);

                $existente = $destinoLibre ? null : $this->detalle($destino, $fecha)
                    ->first(fn (MesaPedido $d) => (int) $d->cod_prod === (int) $item->cod_prod && (string) $d->nota === (string) $item->nota);

                if ($existente) {
                    $existente->update(['cantidad' => (float) $existente->cantidad + $cantidad]);
                } else {
                    $nuevo = $item->replicate(['created_at', 'updated_at']);
                    $nuevo->fill([
                        'mesa_id' => $destino->id,
                        'sucursal_id' => $this->pos->sucursalId(),
                        'cantidad' => $cantidad,
                        'pre_costo' => 0,
                        'cam_of' => $this->pos->tipoCambio(),
                        'f_venta' => $fecha,
                        'dhoraven' => now()->format('G:i'),
                        'familia' => '',
                        'salida' => (string) ($datos['nroMesaTraspaso'] ?? $destino->nro_mesa),
                        'agrupa1' => (string) ($cantidad * (float) $item->pre_venta),
                        'login' => $usuario->usuario,
                        'item_eli' => '',
                        'deleteado' => '',
                        'cerrado' => '',
                        'nom_empl' => $usuario->nombres,
                    ])->save();
                }
            }

            if ((string) ($datos['habilitarMesa'] ?? '0') === '1') {
                $origen->liberar();
            }
        });
    }

    public function anular(Mesa $mesa, string $fecha): void
    {
        DB::transaction(function () use ($mesa, $fecha) {
            MesaPedido::whereIn('id', $this->detalle($mesa, $fecha)->modelKeys())
                ->update(['cerrado' => 'A', 'deleteado' => 'S']);
            $mesa->liberar();
        });
    }

    public function eliminarItem(Mesa $mesa, MesaPedido $item, bool $liberarMesa): void
    {
        DB::transaction(function () use ($mesa, $item, $liberarMesa) {
            $item->update(['item_eli' => 'X']);
            if ($liberarMesa) {
                $mesa->liberar();
            }
        });
    }

    /**
     * Elimina parcialmente ítems de la mesa: descuenta la cantidad y registra la porción
     * eliminada como ítem eliminado (aparece en el reporte de ítems eliminados del cierre).
     */
    public function eliminarCantidades(Mesa $mesa, array $idsDetalle, array $cantidades): void
    {
        DB::transaction(function () use ($mesa, $idsDetalle, $cantidades) {
            foreach ($idsDetalle as $i => $id) {
                $item = MesaPedido::where('mesa_id', $mesa->id)->pendientes()->lockForUpdate()->findOrFail($id);
                $cantidad = (float) $cantidades[$i];

                if ($item->tiempo === 'T') {
                    throw new ErrorPos('NO PUEDE ELIMINAR UN PRODUCTO DE TIEMPO');
                }
                if ($cantidad <= 0 || $cantidad > (float) $item->cantidad) {
                    throw new ErrorPos("CANTIDAD INVÁLIDA PARA {$item->nom_prod}");
                }

                $eliminado = $item->replicate(['created_at', 'updated_at']);
                $eliminado->fill(['cantidad' => $cantidad, 'item_eli' => 'X'])->save();

                $item->update(['cantidad' => (float) $item->cantidad - $cantidad]);
            }
        });
    }

    private function tiempoAbierto(Mesa $mesa, string $fecha): bool
    {
        return MesaPedido::where('mesa_id', $mesa->id)
            ->pendientes()
            ->where('tiempo', 'T')
            ->whereRaw("coalesce(tiempo_fin, '') = ''")
            ->where('f_venta', $fecha)
            ->exists();
    }

    private function crearItem(Mesa $mesa, int $comanda, int $item, int $productoId, float $cantidad, array $datos, bool $billar = false): void
    {
        if ($billar && $productoId === config('posgourmet.producto_atencion_billar')) {
            $producto = ['pre_venta' => 0, 'nom_prod' => 'ATENCION', 'grupo' => '0', 'familia' => '0', 'unid' => 0, 'cod_grupo' => '0', 'presa' => '', 'tiempo' => ''];
        } else {
            $p = Producto::paraPos($this->pos->sucursalId())->where('productos.id', $productoId)->firstOrFail();
            $producto = [
                'pre_venta' => $p->pre_venta,
                'nom_prod' => $p->nom_prod,
                'grupo' => (string) $p->grupo,
                'familia' => $p->familia,
                'unid' => $p->unid,
                'cod_grupo' => $p->cod_grupo,
                'presa' => $p->presa,
                'tiempo' => $p->tiempo ?? '',
            ];
        }

        MesaPedido::create(array_merge($producto, $datos, [
            'mesa_id' => $mesa->id,
            'sucursal_id' => $this->pos->sucursalId(),
            'factura' => 0,
            'orden' => $comanda,
            'item' => $item,
            'cod_prod' => $productoId,
            'cantidad' => $cantidad,
            'pre_costo' => 0,
            'cam_of' => $this->pos->tipoCambio(),
            'cerrado' => '',
            'deleteado' => '',
            'item_eli' => '',
            'tiempo_fin' => '',
        ]));
    }

    private function ocupar(Mesa $mesa, int $comanda, ?string $color, ?string $persona, bool $esSalon): void
    {
        $mesa->update([
            'nroorden' => $comanda,
            'uso' => 'S',
            'colores' => $color ?? '',
            'nropersona' => $esSalon ? '' : (string) $persona,
            'nom_cli' => $esSalon ? (string) $persona : '',
        ]);
    }

    private function esSalon(array $datos): bool
    {
        return ($datos['salon'] ?? 'nosalon') === 'salon';
    }
}
