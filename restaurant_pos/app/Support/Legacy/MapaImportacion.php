<?php

namespace App\Support\Legacy;

use Illuminate\Support\Facades\Hash;

/**
 * Correspondencia entre las tablas MySQL del sistema original y el nuevo esquema PostgreSQL.
 *
 * Reglas automáticas (ver ImportadorLegacy::nombreDestino):
 *  - NOM_PROD -> nom_prod, ID -> id
 *  - idSucursal -> sucursal_id, precioVenta -> precio_venta
 *  - Las columnas legacy que no existen en la tabla destino se descartan (BLOB de imágenes, etc.).
 *
 * Claves de cada entrada:
 *  - origen:      tabla legacy
 *  - columnas:    renombres explícitos [columna legacy => columna destino]
 *  - fk:          FKs opcionales [columna => tabla]; si el registro padre no existe queda en NULL
 *  - requiere:    FKs obligatorias [columna => tabla]; si el padre no existe la fila se omite
 *  - unico:       columna que no puede repetirse; las filas duplicadas se omiten
 *  - transformar: ajuste final de la fila ya renombrada
 *
 * El orden del arreglo respeta las dependencias entre tablas.
 */
class MapaImportacion
{
    public static function tablas(): array
    {
        return [
            'paises' => ['origen' => 'pais'],
            'ciudades' => ['origen' => 'ciudad', 'fk' => ['pais_id' => 'paises']],
            'monedas' => ['origen' => 'moneda'],
            'empresas' => ['origen' => 'empresa'],
            'sucursales' => ['origen' => 'sucursal', 'fk' => ['empresa_id' => 'empresas', 'ciudad_id' => 'ciudades']],
            'dosificaciones' => ['origen' => 'empresa_mysql', 'requiere' => ['sucursal_id' => 'sucursales']],
            'correlativos' => [
                'origen' => 'correlativos',
                'requiere' => ['sucursal_id' => 'sucursales'],
                'transformar' => function (array $fila): array {
                    if ($fila['tabla'] === 'ven_gral_mysql') {
                        $fila['tabla'] = config('posgourmet.correlativo_ventas');
                    }

                    return $fila;
                },
            ],
            'ip_sucursales' => ['origen' => 'ipsucursales'],
            'printer_settings' => ['origen' => 'printersetting'],
            'modulos' => ['origen' => 'modulo'],
            'tigo_money_configuraciones' => ['origen' => 'tigomoney'],

            'usuarios' => [
                'origen' => 'usuarios_mysql',
                'unico' => 'usuario',
                'transformar' => function (array $fila, array $original): array {
                    // Las claves legacy se guardaban en texto plano y se comparaban en mayúsculas
                    $fila['usuario'] = mb_strtoupper(trim((string) $fila['usuario']));
                    $fila['password'] = Hash::make(mb_strtoupper((string) ($original['CLAVES'] ?? '')));
                    $fila['nombres'] = $fila['nombres'] ?: $fila['usuario'];

                    return $fila;
                },
            ],
            'ingresos_usuario' => ['origen' => 'inguser_mysql'],
            'cargos' => ['origen' => 'cargo'],
            'turnos' => ['origen' => 'turno'],
            'empleados' => [
                'origen' => 'empleado',
                'columnas' => ['felefono' => 'telefono'],
                'fk' => ['cargo_id' => 'cargos', 'turno_id' => 'turnos'],
            ],
            'personal' => ['origen' => 'empleados_mysql', 'fk' => ['sucursal_id' => 'sucursales']],

            'cuentas_contables' => [
                'origen' => 'cuentacontable',
                'columnas' => ['id_padre' => 'padre_id'],
                'fk' => ['moneda_id' => 'monedas'],
            ],
            'gestiones' => ['origen' => 'gestion'],
            'centros_costo' => ['origen' => 'centro_costo'],
            'cuenta_grupos' => ['origen' => 'cuenta_grupo', 'fk' => ['cuenta_contable_id' => 'cuentas_contables']],
            'cuenta_impuestos' => ['origen' => 'cuenta_impuesto', 'fk' => ['cuenta_contable_id' => 'cuentas_contables']],
            'cuentas_automaticas' => [
                'origen' => 'cuentaautomatica',
                'columnas' => ['id_cuenta' => 'cuenta_id'],
                'requiere' => ['cuenta_id' => 'cuentas_contables'],
            ],
            'impuestos' => ['origen' => 'impuestos', 'fk' => ['cuenta_id' => 'cuentas_contables']],
            'asientos' => [
                'origen' => 'asiento',
                'columnas' => ['cantegoria_asiento' => 'categoria_asiento', 'id_gestion' => 'gestion_id', 'id_usuario' => 'usuario_id'],
                'fk' => ['gestion_id' => 'gestiones'],
                'requiere' => ['usuario_id' => 'usuarios'],
            ],
            'asiento_detalles' => [
                'origen' => 'det_asiento_cuenta',
                'columnas' => ['id_cuenta' => 'cuenta_id', 'id_asiento' => 'asiento_id', 'idCCosto' => 'centro_costo_id'],
                'fk' => ['centro_costo_id' => 'centros_costo'],
                'requiere' => ['asiento_id' => 'asientos', 'cuenta_id' => 'cuentas_contables'],
            ],
            'bancos' => ['origen' => 'banco'],
            'cuentas_bancarias' => [
                'origen' => 'cuentabancaria',
                'columnas' => ['chekera' => 'chequera'],
                'requiere' => ['banco_id' => 'bancos'],
                'fk' => ['cuenta_id' => 'cuentas_contables'],
            ],
            'cajas' => ['origen' => 'cajacuenta', 'fk' => ['cuenta_id' => 'cuentas_contables']],
            'tipos_ingreso' => ['origen' => 'tipoingreso', 'fk' => ['cuenta_id' => 'cuentas_contables']],
            'tipos_egreso' => ['origen' => 'tipoegreso', 'fk' => ['cuenta_id' => 'cuentas_contables']],
            'tipos_gasto_compra' => ['origen' => 'tipogastocompra', 'fk' => ['cuenta_id' => 'cuentas_contables']],
            'tipos_cliente' => ['origen' => 'tipocliente'],
            'listas_precio' => ['origen' => 'listaprecio', 'fk' => ['moneda_id' => 'monedas']],

            'unidades_medida' => ['origen' => 'unidadmedida'],
            'almacenes' => ['origen' => 'almacen', 'fk' => ['sucursal_id' => 'sucursales', 'cuenta_id' => 'cuentas_contables']],
            'motivos_movimiento' => ['origen' => 'motivomovimiento', 'fk' => ['cuenta_id' => 'cuentas_contables']],
            'proveedores' => ['origen' => 'proveedores', 'fk' => ['ciudad_id' => 'ciudades', 'cuenta_id' => 'cuentas_contables']],
            'insumos' => ['origen' => 'insumos_mysql'],
            'insumo_almacen' => [
                'origen' => 'insumoalmacen',
                'requiere' => ['insumo_id' => 'insumos', 'almacen_id' => 'almacenes'],
            ],
            'inventarios' => [
                'origen' => 'inventario',
                'fk' => ['almacen_id' => 'almacenes', 'almacen_destino_id' => 'almacenes', 'motivo_movimiento_id' => 'motivos_movimiento', 'usuario_id' => 'usuarios'],
            ],
            'kardex_insumos' => [
                'origen' => 'kardexins_mysql',
                'columnas' => ['REGISTRO' => 'inventario_id', 'FACTURA' => 'venta_id', 'COD_INS' => 'insumo_id', 'idUnidad' => 'unidad'],
                'fk' => ['inventario_id' => 'inventarios'],
                'requiere' => ['insumo_id' => 'insumos'],
            ],
            'puntos_venta' => [
                'origen' => 'puntoventa',
                'columnas' => ['idEmpleado' => 'usuario_id', 'alamcenPorDefecto' => 'almacen_por_defecto_id'],
                'unico' => 'usuario_id',
                'fk' => ['almacen_por_defecto_id' => 'almacenes'],
                'requiere' => ['sucursal_id' => 'sucursales', 'usuario_id' => 'usuarios'],
            ],
            'punto_venta_cajas' => [
                'origen' => 'puntoventacaja',
                'requiere' => ['punto_venta_id' => 'puntos_venta', 'caja_id' => 'cajas'],
            ],
            'punto_venta_cuentas' => [
                'origen' => 'puntoventacuenta',
                'columnas' => ['idCuentaBanco' => 'cuenta_bancaria_id'],
                'requiere' => ['punto_venta_id' => 'puntos_venta', 'cuenta_bancaria_id' => 'cuentas_bancarias'],
            ],

            'categorias' => ['origen' => 'menugrupo_mysql'],
            'productos' => [
                'origen' => 'productos_mysql',
                'columnas' => ['grupo' => 'categoria_id'],
                'fk' => ['categoria_id' => 'categorias'],
            ],
            'producto_sucursal' => [
                'origen' => 'productosucursal',
                'requiere' => ['producto_id' => 'productos', 'sucursal_id' => 'sucursales'],
                'fk' => ['categoria_id' => 'categorias'],
            ],
            'producto_opciones' => [
                'origen' => 'opproductos_mysql',
                'columnas' => ['COD_PROD' => 'producto_id'],
                'requiere' => ['producto_id' => 'productos'],
            ],
            'grupos' => ['origen' => 'grupos_mysql'],
            'grupo_producto' => [
                'origen' => 'relprogru_mysql',
                'columnas' => ['COD_GRUPO' => 'grupo_id', 'COD_PROD' => 'producto_id'],
                'buscar' => ['grupo_id' => ['grupos', 'cod_grupo']],
                'requiere' => ['grupo_id' => 'grupos', 'producto_id' => 'productos'],
            ],
            'insumo_producto' => [
                'origen' => 'relproins_mysql',
                'columnas' => ['COD_PROD' => 'producto_id', 'COD_INS' => 'insumo_id'],
                'requiere' => ['producto_id' => 'productos', 'insumo_id' => 'insumos'],
            ],

            'clientes' => ['origen' => 'clientes_mysql', 'excluir' => ['FOTO']],
            'ventas' => [
                'origen' => 'ven_gral_mysql',
                'columnas' => ['COD_CLI' => 'cliente_id'],
                'fk' => ['cliente_id' => 'clientes'],
                'requiere' => ['sucursal_id' => 'sucursales'],
            ],
            'venta_detalles' => [
                'origen' => 'detalles_mysql',
                'columnas' => ['FACTURA' => 'venta_id'],
                'requiere' => ['venta_id' => 'ventas'],
            ],
            'tarjeta_detalles' => [
                'origen' => 'dettarjeta_mysql',
                'columnas' => ['FACTURA' => 'venta_id'],
                'requiere' => ['venta_id' => 'ventas'],
                'fk' => ['sucursal_id' => 'sucursales'],
            ],
            'gastos' => ['origen' => 'gastos_mysql', 'requiere' => ['sucursal_id' => 'sucursales']],
            'mesas' => ['origen' => 'mesas_mysql', 'requiere' => ['sucursal_id' => 'sucursales']],
            'mesa_pedidos' => [
                'origen' => 'productos_mesas_mysql',
                'columnas' => ['NRO_MESA' => 'mesa_id'],
                'requiere' => ['mesa_id' => 'mesas'],
                'fk' => ['sucursal_id' => 'sucursales'],
            ],
        ];
    }
}
