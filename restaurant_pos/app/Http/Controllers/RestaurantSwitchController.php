<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\OpenPosSession;
use App\Exceptions\PosException;
use App\Support\PosContext;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Un partner con varios restaurantes elige con cuál trabaja. La caja se abre de nuevo
 * porque sucursal, almacén y tipo de cambio son de cada restaurante.
 */
class RestaurantSwitchController extends Controller
{
    public function __invoke(Request $request, OpenPosSession $opener, PosContext $pos): RedirectResponse
    {
        $user = $request->user();
        $restaurantId = $request->integer('restaurant_id');

        abort_unless(in_array($restaurantId, $user->activeRestaurantIds(), true), 403, 'No pertenece a ese restaurante.');

        $previous = Tenant::id();
        $context = $request->session()->get('pos');

        try {
            Tenant::use($restaurantId);
            $opener->open($request, $user, $pos->exchangeRate());
        } catch (PosException $e) {
            Tenant::use($previous);
            $request->session()->put('pos', $context);

            return back()->with('error', $e->getMessage());
        }

        $request->session()->put(Tenant::SESSION_KEY, $restaurantId);

        // La página anterior puede ser un registro del otro restaurante
        return str_contains($request->headers->get('referer', ''), '/admin')
            ? redirect()->route('admin.dashboard')
            : redirect()->route('pos.index');
    }
}
