{{--
    Ticket para impresora térmica (58 u 80 mm). Blanco y negro, letras grandes y sin márgenes de página.
    Cada .ticket sale en su propia hoja: los controladores de las ticketeras cortan el papel entre hojas.
    data-empty="1" avisa al POS que no hay nada que imprimir.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; }
        body {
            width: {{ $width === 58 ? 48 : 72 }}mm;
            margin: 0 auto;
            font-family: Arial, Helvetica, sans-serif;
            font-size: {{ $width === 58 ? 10 : 12 }}pt;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .ticket { padding: 2mm 0 6mm; }
        .ticket + .ticket { break-before: page; page-break-before: always; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .small { font-size: 0.8em; }
        .big { font-size: 1.6em; font-weight: bold; line-height: 1.1; }
        .huge { font-size: 2em; font-weight: bold; line-height: 1.05; }
        .inverse { background: #000; color: #fff; padding: 1mm 0; }
        .rule { border: 0; border-top: 1px dashed #000; margin: 2mm 0; }
        .rule-solid { border: 0; border-top: 2px solid #000; margin: 2mm 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0.6mm 0; }
        .qty { width: 1%; white-space: nowrap; padding-right: 2mm; }
        .note { font-style: italic; padding-left: 3mm; }
        .qr svg { display: block; width: 100%; height: auto; }
        .money { width: 1%; white-space: nowrap; padding-left: 2mm; text-align: right; }
        @media screen { body { background: #fff; padding: 4mm 0; } .ticket { border-bottom: 1px dotted #999; } }
    </style>
</head>
<body data-empty="@yield('empty', '0')">
    @yield('content')
</body>
</html>
