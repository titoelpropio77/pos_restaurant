<?php

namespace App\Services\Ventas;

use App\Exceptions\ErrorPos;
use App\Models\Cliente;
use App\Models\Dosificacion;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\Facturacion\CodigoControl;
use App\Services\Inventario\DescargoInsumos;
use App\Support\PosContexto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registra una venta del POS: cliente, numeración, código de control SIN, detalle
 * y descargo de insumos. Port de facturarController.php (proceso "guardar").
 */
class RegistrarVenta
{
    public const CONSUMO_TARJETA = 'Consumo Adicional de Tarjeta';

    public function __construct(
        private NumeracionFactura $numeracion,
        private CodigoControl $codigoControl,
        private PosContexto $pos,
    ) {}

    /**
     * @param  array  $datos  Datos validados del formulario de facturación
     * @return array{venta: Venta, orden: int, factura: int}
     */
    public function ejecutar(array $datos, Usuario $usuario): array
    {
        $sucursalId = $this->pos->sucursalId();
        $fecha = Carbon::parse($datos['fechaVenta'])->toDateString();
        $hora = now()->format('G:i');
        $nit = trim((string) ($datos['nitCli'] ?? ''));
        $totalBs = (float) $datos['totalBs'];

        $dosificacion = Dosificacion::vigenteDe($sucursalId);
        if (! $dosificacion || $dosificacion->diasParaVencer($fecha) < 0) {
            throw new ErrorPos('SU DOSIFICACIÓN YA SE VENCIÓ, NO PODRÁ VENDER');
        }

        return DB::transaction(function () use ($datos, $usuario, $sucursalId, $fecha, $hora, $nit, $totalBs, $dosificacion) {
            $cliente = $this->cliente(
                $datos['nombreCli'] ?? 'SIN NOMBRE',
                $nit === '' ? '0' : $nit,
                $datos['telefonoCli'] ?? '',
                $datos['direccionCli'] ?? '',
            );

            $numero = $this->numeracion->siguiente($sucursalId, $nit, $datos['formaPago']);

            $codigoControl = $this->codigoControl->generar(
                (string) $dosificacion->nroautori,
                (string) $numero['factura_impresa'],
                $nit === '' ? '0' : $nit,
                str_replace('-', '', $fecha),
                (string) $totalBs,
                (string) $dosificacion->llave,
            );

            $dosificacion->update(['facact' => $numero['folio'], 'facactr' => $numero['factura_impresa']]);

            $prepago = (string) ($datos['selectPrepago'] ?? '');

            $venta = Venta::create([
                'sucursal_id' => $sucursalId,
                'orden' => $numero['orden'],
                'f_venta' => $fecha,
                'login' => $usuario->usuario,
                'formapago' => $datos['formaPago'],
                'estado' => 'ACTIVO',
                'salida' => $datos['salidaProducto'] ?? '',
                'turno' => $datos['turno'] ?? '',
                'cliente_id' => $cliente->id,
                'vendedor' => (int) ($datos['vendedor'] ?? 0),
                'dhoraven' => $hora,
                'cam_of' => $this->pos->tipoCambio(),
                'total_fact' => $totalBs,
                'tot_deuda' => 0,
                'xpagar' => '0',
                'pendiente' => 'P',
                'fortax' => $numero['folio'],
                'tot_des' => $numero['folior'],
                'numorden' => $prepago === '' ? '' : 'P'.$prepago,
                'pago_us' => (float) ($datos['totalUsd'] ?? 0),
                'cambio_us' => (float) ($datos['cambioUsd'] ?? 0),
                'pago_bs' => (float) ($datos['totalFacturaBs'] ?? 0),
                'cambio_bs' => (float) ($datos['cambioBs'] ?? 0),
                'cod_empl' => '0',
                'nit_cli' => $nit === '' ? '0' : $nit,
                'ci_cli' => '0',
                'nom_cli' => $datos['nombreCli'] ?? 'SIN NOMBRE',
                'llave' => $dosificacion->llave,
                'nroautori' => $dosificacion->nroautori,
                'fecha_lim' => $dosificacion->fecha_lim,
                'cifrado' => $codigoControl,
                'pre_pago' => $prepago === '0' || $prepago === '' ? null : $totalBs,
                'ordenes' => '0',
                'entregado' => '0',
            ]);
            $venta->update(['factura' => $venta->id]);

            $descargo = new DescargoInsumos($this->pos->almacenId(), $usuario->id, $venta->id, $fecha, $hora);

            foreach ($datos['idProducto'] as $i => $productoId) {
                $venta->detalles()->create(array_merge(
                    $this->datosProducto($datos, $i, (int) $productoId),
                    [
                        'orden' => $numero['orden'],
                        'item' => $i + 1,
                        'cod_prod' => (int) $productoId,
                        'cantidad' => (float) $datos['cantidad'][$i],
                        'pre_costo' => 0,
                        'cam_of' => $this->pos->tipoCambio(),
                        'f_venta' => $fecha,
                        'dhoraven' => $hora,
                        'unidad' => 'duni',
                        'turno' => $datos['turno'] ?? '',
                        'familia' => $datos['formaPago'],
                        'salida' => $datos['salidaProducto'] ?? '',
                        'fortax' => $numero['folio'],
                        'tot_des' => $numero['folior'],
                        'factor' => 0,
                        'agrupa1' => $datos['subTotal'][$i] ?? 0,
                        'agrupa3' => $datos['qr'][$i] ?? '',
                        'login' => $usuario->usuario,
                        'ordenes' => '0',
                        'nota' => $datos['nota'][$i] ?? '',
                        'indice' => 2,
                        'deconsumo' => 's',
                    ],
                ));

                $descargo->descargar((int) $productoId, (float) $datos['cantidad'][$i]);
            }

            $this->pos->setOrdenDia($numero['orden']);

            return ['venta' => $venta, 'orden' => $numero['orden'], 'factura' => $numero['factura_impresa']];
        });
    }

    /**
     * Datos del producto que se copian al detalle. Contempla los ítems especiales
     * "Consumo Adicional de Tarjeta" y la "Atención" de billar, que no existen como producto.
     */
    private function datosProducto(array $datos, int $i, int $productoId): array
    {
        $nombre = $datos['nombreProduc'][$i] ?? '';
        $precio = (float) ($datos['precio'][$i] ?? 0);

        if ($i === 0 && $nombre === self::CONSUMO_TARJETA) {
            return ['nom_prod' => self::CONSUMO_TARJETA, 'pre_venta' => $precio, 'grupo' => '0', 'presa' => '0', 'unid' => 0, 'barcode' => '0', 'cod_grupo' => '0'];
        }

        if (($datos['billar'] ?? '') === 'billar' && $productoId === config('posgourmet.producto_atencion_billar')) {
            return ['nom_prod' => 'Atencion', 'pre_venta' => $precio, 'agrupa2' => (string) $precio, 'grupo' => '0', 'presa' => '0', 'unid' => 0, 'barcode' => '0', 'cod_grupo' => '0'];
        }

        $producto = Producto::paraPos($this->pos->sucursalId())->where('productos.id', $productoId)->first();
        if (! $producto) {
            throw new ErrorPos("EL PRODUCTO {$productoId} NO ESTÁ HABILITADO EN ESTA SUCURSAL");
        }

        return [
            'nom_prod' => $producto->nom_prod,
            'pre_venta' => $producto->pre_venta,
            'grupo' => (string) $producto->grupo,
            'presa' => $producto->presa,
            'unid' => $producto->unid,
            'barcode' => $producto->barcode,
            'cod_grupo' => $producto->cod_grupo,
        ];
    }

    /**
     * Busca el cliente por nombre y NIT; si no existe lo crea. Si viene teléfono y dirección
     * (envío a domicilio) los actualiza. Legacy: verificarCliente().
     */
    private function cliente(string $nombre, string $nit, string $telefono, string $direccion): Cliente
    {
        $cliente = Cliente::where('nom_cli', $nombre)->where('nit_cli', $nit)->first();

        if (! $cliente) {
            return Cliente::create(['cod_cli' => '0', 'nom_cli' => $nombre, 'nit_cli' => $nit, 'tel_cli' => $telefono, 'dir_cli' => $direccion]);
        }

        if ($telefono !== '' && $direccion !== '') {
            $cliente->update(['tel_cli' => $telefono, 'dir_cli' => $direccion]);
        }

        return $cliente;
    }
}
