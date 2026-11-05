<?php

/*
|--------------------------------------------------------------------------
| Configuración del POS Gourmet
|--------------------------------------------------------------------------
|
| En el sistema original estas opciones se activaban creando archivos .txt
| vacíos en la raíz (salon.txt, billar.txt, meserosc.txt...). Aquí se
| controlan con variables de entorno.
|
*/

return [

    'opciones' => [
        // salon.txt: las mesas se identifican por nombre de cliente en lugar de nro. de personas
        'salon' => env('POS_SALON', false),

        // cdgmes.txt: pide código del mesero para abrir mesa
        'codigo_mesero' => env('POS_CODIGO_MESERO', false),

        // meserosc.txt: no valida el código del mesero al abrir una mesa
        'meseros_sin_codigo' => env('POS_MESEROS_SIN_CODIGO', true),

        // cierreDiario.txt: muestra el botón de cierre diario de caja
        'cierre_diario' => env('POS_CIERRE_DIARIO', true),

        // billar.txt: habilita productos cobrados por tiempo (mesas de billar)
        'billar' => env('POS_BILLAR', false),

        // porceRest.txt: porcentaje de restaurante
        'porcentaje_restaurante' => env('POS_PORCENTAJE_RESTAURANTE', false),

        // sinvistaempleado.txt: abre mesa sin pedir datos del mesero
        'sin_vista_empleado' => env('POS_SIN_VISTA_EMPLEADO', true),

        // impresionnube.txt: la impresión la gestiona el agente local en http://<ip>:8080
        'impresion_nube' => env('POS_IMPRESION_NUBE', true),

        // cambiofecha.txt: permite cambiar la fecha de venta desde el POS
        'cambio_fecha' => env('POS_CAMBIO_FECHA', false),
    ],

    // Producto ficticio usado para la "atención" en mesas de billar
    'producto_atencion_billar' => 99999,

    // Nombre con el que se registran los correlativos de ventas
    'correlativo_ventas' => 'ventas',

    // Motivo de movimiento de inventario usado al descontar insumos por venta
    'motivo_movimiento_venta' => 'VENTA',

    // Días de anticipación para avisar el vencimiento de la dosificación
    'dias_aviso_dosificacion' => 7,
];
