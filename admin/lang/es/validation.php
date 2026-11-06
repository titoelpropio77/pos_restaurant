<?php

return [
    'array' => 'El campo :attribute debe ser una lista.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'exists' => 'El valor seleccionado en :attribute no existe.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor a :value.',
    ],
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor a :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],
    'numeric' => 'El campo :attribute debe ser numérico.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'El valor del campo :attribute ya está registrado.',

    'attributes' => [
        'usuario' => 'usuario',
        'password' => 'contraseña',
        'tipoCambio' => 'tipo de cambio',
        'idProducto' => 'productos',
        'cantidad' => 'cantidad',
        'cantidad.*' => 'cantidad',
        'grupo' => 'categoría',
        'idMesa' => 'mesa',
        'idMesero' => 'mesero',
        'fechaVenta' => 'fecha de venta',
        'formaPago' => 'forma de pago',
        'totalBs' => 'total Bs',
        'nitCli' => 'NIT',
        'nomCli' => 'nombre del cliente',
        'monto' => 'monto',
        'concepto' => 'concepto',
        'tarjeta' => 'tarjeta',
        'nuevoSaldo' => 'nuevo saldo',
    ],
];
