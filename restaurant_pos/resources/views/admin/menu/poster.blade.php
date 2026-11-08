@php
    use App\Services\Menu\DigitalMenu;

    $name = DigitalMenu::title($restaurant->name);
@endphp
<!DOCTYPE html>
<html lang="es" style="--bg: {{ $theme->background }}; --ink: {{ $theme->ink }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Afiche del menú · {{ $name }} · {{ $branch->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <style>
        /* Afiche A5 vertical; los colores de la tienda se imprimen tal cual (print-color-adjust) */
        @page { size: A5 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #E7E5E4;
            font-family: "Inter", system-ui, sans-serif; color: var(--ink);
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .poster {
            width: 148mm; height: 210mm; padding: 16mm 14mm 12mm; background: var(--bg);
            display: flex; flex-direction: column; align-items: center; text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .18);
        }
        .logo { max-height: 30mm; max-width: 80mm; object-fit: contain; margin-bottom: 6mm; }
        .name { margin: 0; font-family: "Playfair Display", Georgia, serif; font-weight: 600; font-size: 20pt; line-height: 1.1; }
        .ornament { display: flex; align-items: center; gap: 10px; margin: 6mm 0 5mm; opacity: .55; }
        .ornament::before, .ornament::after { content: ""; width: 18mm; height: 1px; background: currentColor; }
        .ornament span { width: 5px; height: 5px; background: currentColor; transform: rotate(45deg); }
        .title { margin: 0; font-family: "Playfair Display", Georgia, serif; font-style: italic; font-weight: 500; font-size: 30pt; line-height: 1; }
        .hint { margin: 3mm 0 0; font-size: 10.5pt; opacity: .75; }
        .qr { margin: auto 0; width: 72mm; padding: 5mm; background: #fff; border-radius: 6mm; }
        .qr svg { display: block; width: 100%; height: auto; }
        .branch { margin: 0; font-size: 8.5pt; font-weight: 600; letter-spacing: .24em; text-transform: uppercase; opacity: .7; }
        .url { margin: 2mm 0 0; font-size: 7pt; opacity: .5; word-break: break-all; }
        .actions { position: fixed; top: 16px; right: 16px; display: flex; gap: 8px; }
        .actions button, .actions a {
            padding: 10px 18px; border: 0; border-radius: 10px; background: #14213D; color: #fff; font: 600 14px "Inter", sans-serif;
            text-decoration: none; cursor: pointer;
        }
        .actions a { background: #fff; color: #14213D; }
        @media print {
            body { padding: 0; background: none; display: block; min-height: 0; }
            .poster { box-shadow: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('admin.menu.index') }}">Volver</a>
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <main class="poster">
        @if ($restaurant->logo)
            <img class="logo" src="{{ $restaurant->logo }}" alt="{{ $name }}">
        @endif
        <h1 class="name">{{ $name }}</h1>
        <div class="ornament" aria-hidden="true"><span></span></div>
        <p class="title">Nuestro menú</p>
        <p class="hint">Escanea el código con la cámara de tu celular</p>

        <div class="qr">{!! $qr !!}</div>

        <p class="branch">{{ DigitalMenu::title($branch->name) }}</p>
        <p class="url">{{ $url }}</p>
    </main>
</body>
</html>
