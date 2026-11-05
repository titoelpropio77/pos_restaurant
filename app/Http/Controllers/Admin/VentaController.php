<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Detalle de una venta y su anulación desde el panel.
 */
class VentaController extends Controller
{
    public function show(Venta $venta): View
    {
        return view('admin.ventas.show', [
            'venta' => $venta->load(['detalles' => fn ($q) => $q->orderBy('item'), 'sucursal', 'cliente', 'consumosTarjeta']),
        ]);
    }

    public function anular(Venta $venta): RedirectResponse
    {
        $anular = $venta->estado === 'ACTIVO';

        DB::transaction(function () use ($venta, $anular) {
            $venta->update(['estado' => $anular ? 'INACTIVO' : 'ACTIVO', 'anulado' => $anular ? 'S' : '', 'eliminpor' => $anular ? auth()->user()->usuario : '']);
            $venta->detalles()->update(['anulado' => $anular ? 'S' : '']);
        });

        return back()->with('ok', $anular ? 'Venta anulada.' : 'Venta reactivada.');
    }
}
