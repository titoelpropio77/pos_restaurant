@php
    use App\Services\Menu\DigitalMenu;

    $name = DigitalMenu::title($restaurant->name);
    $branchName = DigitalMenu::title($branch->name);
    $address = $branch->address ?: $restaurant->address;
    $phone = $branch->phone ?: $restaurant->phone;
    $city = $branch->city?->name;
    // Precios: sin decimales si son enteros (Bs 25), con dos si tienen centavos (Bs 25.50)
    $money = fn (float $price) => number_format($price, fmod($price, 1) ? 2 : 0);
    $total = collect($categories)->sum(fn ($c) => count($c['items']));
    $dishes = collect($categories)->flatMap(fn ($c) => $c['items'])->mapWithKeys(fn ($item) => [$item['id'] => [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'] > 0 ? $money($item['price']) : '',
        'image' => $item['image'],
        'schedule' => $item['schedule'],
        'available' => $item['available_now'],
    ]]);
@endphp
<!DOCTYPE html>
<html lang="es" class="{{ $theme->dark ? 'theme-dark' : 'theme-light' }}" style="--bg: {{ $theme->background }}; --ink: {{ $theme->ink }}; color-scheme: {{ $theme->dark ? 'dark' : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Menú · {{ $name }}</title>
    <meta name="description" content="Menú de {{ $name }} ({{ $branchName }}): {{ collect($categories)->pluck('name')->join(', ') }}.">
    <meta name="theme-color" content="{{ $theme->background }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Menú · {{ $name }}">
    <meta property="og:description" content="{{ $total }} platos y bebidas · {{ $branchName }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preload" href="{{ asset('assets/fonts/playfair-display-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu/menu.css') }}?v={{ filemtime(public_path('css/menu/menu.css')) }}">
</head>
<body>
    <header class="hero wrap">
        @if ($restaurant->logo)
            <img class="hero-logo" src="{{ $restaurant->logo }}" alt="{{ $name }}">
        @endif
        <p class="hero-eyebrow">Menú · {{ $branchName }}</p>
        <h1 class="hero-name">{{ $name }}</h1>
        <div class="ornament" aria-hidden="true"><span></span></div>
        @if ($address || $phone)
            <ul class="hero-info">
                @if ($address)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                        {{ DigitalMenu::title($address) }}{{ $city ? ', '.$city : '' }}
                    </li>
                @endif
                @if ($phone)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>
                    </li>
                @endif
            </ul>
        @endif
    </header>

    @if ($categories)
        <nav class="nav" aria-label="Categorías del menú">
            <div class="wrap nav-inner">
                <div class="chips js-chips">
                    @foreach ($categories as $category)
                        <a class="chip" href="#c{{ $category['id'] }}" data-target="c{{ $category['id'] }}">{{ $category['name'] }}</a>
                    @endforeach
                </div>
                <button type="button" class="icon-btn js-open-search" aria-label="Buscar en el menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/></svg>
                </button>
                <div class="search" role="search">
                    <label for="menu-search" class="sr-only">Buscar un plato</label>
                    <input type="search" id="menu-search" class="js-search" placeholder="Buscar un plato o bebida…" autocomplete="off" enterkeyhint="search">
                    <button type="button" class="icon-btn js-close-search" aria-label="Cerrar la búsqueda">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>
            </div>
        </nav>
    @endif

    <main class="wrap">
        @forelse ($categories as $category)
            <section class="category js-category" id="c{{ $category['id'] }}" aria-labelledby="t{{ $category['id'] }}">
                <div class="category-head">
                    <h2 class="category-title" id="t{{ $category['id'] }}">{{ $category['name'] }}</h2>
                    <span class="category-count">{{ count($category['items']) }}</span>
                </div>
                <ul class="dishes">
                    @foreach ($category['items'] as $item)
                        <li class="js-dish" data-search="{{ Str::lower(Str::ascii($item['name'].' '.$item['description'])) }}">
                            <button type="button" class="dish {{ $item['available_now'] ? '' : 'is-off' }}" data-id="{{ $item['id'] }}">
                                <span class="dish-body">
                                    <span class="dish-name">{{ $item['name'] }}</span>
                                    @if ($item['description'])
                                        <span class="dish-desc">{{ $item['description'] }}</span>
                                    @endif
                                    <span class="dish-foot">
                                        @if ($item['price'] > 0)
                                            <span class="price"><small>Bs</small>{{ $money($item['price']) }}</span>
                                        @endif
                                        @if (! $item['available_now'])
                                            <span class="tag tag-off">{{ $item['schedule'] ? 'Disponible: '.$item['schedule'] : 'No disponible ahora' }}</span>
                                        @elseif ($item['schedule'])
                                            <span class="tag">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>
                                                {{ $item['schedule'] }}
                                            </span>
                                        @endif
                                    </span>
                                </span>
                                @if ($item['image'])
                                    <span class="dish-photo">
                                        <img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async" width="128" height="128">
                                    </span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="empty">
                <strong>Estamos preparando el menú</strong>
                Vuelve pronto para ver nuestros platos.
            </div>
        @endforelse

        <div class="empty js-no-results" hidden>
            <strong>Sin resultados</strong>
            No encontramos platos con ese nombre.
        </div>
    </main>

    <footer class="footer">
        <div class="wrap">
            <p class="footer-name">{{ $name }}</p>
            <p>{{ $branchName }}{{ $address ? ' · '.DigitalMenu::title($address) : '' }}</p>
            <p>Precios en bolivianos (Bs).</p>
            <p class="powered">Menú digital · Mesatek</p>
        </div>
    </footer>

    <dialog class="sheet js-sheet" aria-labelledby="sheet-name">
        <button type="button" class="icon-btn sheet-close js-close-sheet" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <div class="sheet-photo js-sheet-photo"><img alt=""></div>
        <div class="sheet-body">
            <h3 class="sheet-name" id="sheet-name"></h3>
            <p class="sheet-desc"></p>
            <div class="sheet-foot">
                <span class="price"></span>
                <span class="tag sheet-tag"></span>
            </div>
        </div>
    </dialog>

    <script type="application/json" id="menu-data">@json($dishes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>
    <script src="{{ asset('js/menu/menu.js') }}?v={{ filemtime(public_path('js/menu/menu.js')) }}" defer></script>
</body>
</html>
