<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\IngresoUsuario;
use App\Support\PosContexto;
use App\Support\Restaurante;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pantalla principal del punto de venta (legacy: index.php).
 */
class PosController extends Controller
{
    public function index(PosContexto $pos): View
    {
        $autenticado = Auth::check() && $pos->activo();

        return view('pos.index', [
            'autenticado' => $autenticado,
            'usuario' => Auth::user(),
            'pos' => $pos,
            'opciones' => Restaurante::opciones(),
            'tipoCambioActual' => (float) IngresoUsuario::latest('id')->value('cam_of'),
            'rutas' => $this->rutas(),
        ]);
    }

    /**
     * URLs que usa posgourmet.js, indexadas por el nombre con que las referencia el script.
     */
    private function rutas(): array
    {
        return array_map(fn (string $nombre) => route($nombre, absolute: false), [
            'autorizarTipoCambio' => 'pos.tipo-cambio.autorizar',
            'sucursales' => 'pos.sucursales.index',
            'cambiarSucursal' => 'pos.sucursales.cambiar',
            'impresionSucursal' => 'pos.sucursal.impresion',
            'dosificacion' => 'pos.sucursal.dosificacion',
            'verificarDosificacion' => 'pos.sucursal.dosificacion.verificar',
            'meseros' => 'pos.meseros.index',
            'categorias' => 'pos.categorias.index',
            'productos' => 'pos.productos.index',
            'buscarProducto' => 'pos.productos.buscar',
            'buscarCodigoBarra' => 'pos.productos.codigo-barra',
            'opcionesProducto' => 'pos.productos.opciones',
            'insumosProducto' => 'pos.productos.insumos',
            'desactivarProducto' => 'pos.productos.desactivar',
            'moverProducto' => 'pos.productos.mover',
            'facturar' => 'pos.ventas.store',
            'historico' => 'pos.ventas.historico',
            'detalleVenta' => 'pos.ventas.detalle',
            'anularVenta' => 'pos.ventas.anular',
            'impresionVenta' => 'pos.ventas.impresion',
            'cajeros' => 'pos.caja.cajeros',
            'totalesCierre' => 'pos.caja.totales',
            'cierreCaja' => 'pos.caja.cierre',
            'gastos' => 'pos.gastos.index',
            'entregados' => 'pos.gastos.entregados',
            'guardarGasto' => 'pos.gastos.store',
            'mesas' => 'pos.mesas.index',
            'mesasProcesando' => 'pos.mesas.procesando',
            'mesasLiberarProcesando' => 'pos.mesas.liberar-procesando',
            'mesaDetalle' => 'pos.mesas.detalle',
            'abrirMesa' => 'pos.mesas.abrir',
            'agregarMesa' => 'pos.mesas.agregar',
            'mesaCobrada' => 'pos.mesas.cobrada',
            'traspasarMesa' => 'pos.mesas.traspasar',
            'anularMesa' => 'pos.mesas.anular',
            'eliminarItemMesa' => 'pos.mesas.eliminar-item',
            'eliminarCantidadMesa' => 'pos.mesas.eliminar-cantidad',
            'pararTiempo' => 'pos.mesas.parar-tiempo',
            'itemsEliminados' => 'pos.mesas.items-eliminados',
            'clientesSugerenciasNombre' => 'pos.clientes.sugerencias.nombre',
            'clientesSugerenciasNit' => 'pos.clientes.sugerencias.nit',
            'clientesSugerenciasDomicilio' => 'pos.clientes.sugerencias.domicilio',
            'clientesBuscarNombre' => 'pos.clientes.buscar-nombre',
            'clientesBuscarNit' => 'pos.clientes.buscar-nit',
            'clientesActualizar' => 'pos.clientes.update',
            'clientesEliminar' => 'pos.clientes.destroy',
            'tarjetas' => 'pos.tarjetas.buscar',
            'consumoTarjeta' => 'pos.tarjetas.consumir',
            'logout' => 'logout',
        ]);
    }
}
