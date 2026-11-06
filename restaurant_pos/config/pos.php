<?php

/*
|--------------------------------------------------------------------------
| Configuración de Mesatek
|--------------------------------------------------------------------------
|
| En el sistema original estas opciones se activaban creando archivos .txt
| vacíos en la raíz (salon.txt, billar.txt, meserosc.txt...). Aquí se
| controlan con variables de entorno.
|
*/

return [

    'options' => [
        // salon.txt: las mesas se identifican por nombre de cliente en lugar de nro. de personas
        'dining_room' => env('POS_SALON', false),

        // cdgmes.txt: pide código del mesero para abrir mesa
        'code_waiter' => env('POS_CODIGO_MESERO', false),

        // meserosc.txt: no valida el código del mesero al abrir una mesa
        'waiters_without_code' => env('POS_MESEROS_SIN_CODIGO', true),

        // cierreDiario.txt: exige apertura y cierre de caja por punto de venta
        'daily_close' => env('POS_CIERRE_DIARIO', true),

        // billar.txt: habilita productos cobrados por tiempo (mesas de billar)
        'billiards' => env('POS_BILLAR', false),

        // porceRest.txt: porcentaje de restaurante
        'percentage_restaurant' => env('POS_PORCENTAJE_RESTAURANTE', false),

        // sinvistaempleado.txt: abre mesa sin pedir datos del mesero
        'without_view_employee' => env('POS_SIN_VISTA_EMPLEADO', true),

        // impresionnube.txt: la impresión la gestiona el agente local en http://<ip>:8080
        'print_cloud' => env('POS_IMPRESION_NUBE', true),

        // cambiofecha.txt: permite cambiar la fecha de venta desde el POS
        'change_date' => env('POS_CAMBIO_FECHA', false),
    ],

    // Producto ficticio usado para la "atención" en mesas de billar
    'product_service_billiards' => 99999,

    // Nombre con el que se registran los correlativos de ventas
    'sales_sequence' => 'sales',

    // Motivo de movimiento de inventario usado al descontar insumos por venta
    'movement_reason_sale' => 'VENTA',

    // Días de anticipación para avisar el vencimiento de la dosificación
    'days_warning_authorization' => 7,
];
