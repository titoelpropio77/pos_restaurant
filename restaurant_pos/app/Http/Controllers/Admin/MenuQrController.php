<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Services\Menu\MenuTheme;
use App\Support\QrCode;
use App\Support\Tenant;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Menú digital: link público y QR de cada sucursal, para descargar o imprimir un afiche de mesa.
 */
class MenuQrController extends Controller
{
    public function index(): View
    {
        return view('admin.menu.index', [
            'branches' => Branch::where('status', 'ACTIVE')->orderByDesc('is_main')->orderBy('name')->get()
                ->map(fn (Branch $branch) => [
                    'branch' => $branch,
                    'url' => $url = $this->url($branch),
                    'qr' => QrCode::inlineSvg($url, 220),
                ]),
        ]);
    }

    public function qr(Branch $branch): Response
    {
        $file = 'menu-qr-'.Str::slug($branch->name).'.svg';

        return response(QrCode::svg($this->url($branch), 1024, 2), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$file}\"",
        ]);
    }

    /** Afiche para imprimir y poner en las mesas, con los colores de la tienda. */
    public function poster(Branch $branch): View
    {
        $restaurant = Restaurant::findOrFail(Tenant::id());

        return view('admin.menu.poster', [
            'branch' => $branch,
            'restaurant' => $restaurant,
            'url' => $url = $this->url($branch),
            'qr' => QrCode::inlineSvg($url, 600, 0),
            'theme' => MenuTheme::from($restaurant->background_color),
        ]);
    }

    private function url(Branch $branch): string
    {
        return route('menu.show', $branch->menuToken());
    }
}
