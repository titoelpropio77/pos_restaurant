<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Services\Menu\DigitalMenu;
use App\Services\Menu\MenuTheme;
use App\Support\Tenant;
use Illuminate\Http\Response;

/**
 * Menú digital público de una sucursal: es el destino del QR de las mesas.
 *
 * La ruta no pasa por el grupo web (sin sesión ni cookies: cada escaneo crearía una sesión en la BD),
 * así que el restaurante se fija aquí, a partir del token de la sucursal, mientras se arma el menú.
 */
class PublicMenuController extends Controller
{
    public function __invoke(string $token): Response
    {
        $branch = Branch::withoutGlobalScopes()->where('menu_token', $token)->where('status', 'ACTIVE')->first();
        $restaurant = $branch ? Restaurant::where('status', 'ACTIVE')->find($branch->restaurant_id) : null;

        abort_unless($restaurant, 404);

        $tenant = Tenant::current();
        $tenant->startRequest($restaurant->id);

        try {
            $categories = DigitalMenu::for($branch);
        } finally {
            $tenant->finishRequest();
        }

        return response()
            ->view('menu.show', [
                'restaurant' => $restaurant,
                'branch' => $branch,
                'categories' => $categories,
                'theme' => MenuTheme::from($restaurant->background_color),
            ])
            ->header('Cache-Control', 'public, max-age=300');
    }
}
