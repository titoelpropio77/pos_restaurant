<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Detalle de una venta y su anulación desde el panel.
 */
class SaleController extends Controller
{
    public function show(Sale $sale): View
    {
        return view('admin.sales.show', [
            'sale' => $sale->load(['details' => fn ($q) => $q->orderBy('item'), 'branch', 'customer', 'consumptionsCard']),
        ]);
    }

    public function void(Sale $sale): RedirectResponse
    {
        $void = $sale->status === 'ACTIVE';

        DB::transaction(function () use ($sale, $void) {
            $sale->update(['status' => $void ? 'INACTIVE' : 'ACTIVE', 'voided' => $void ? 'S' : '', 'voided_by' => $void ? auth()->user()->username : '']);
            $sale->details()->update(['voided' => $void ? 'S' : '']);
        });

        return back()->with('ok', $void ? 'Venta anulada.' : 'Venta reactivada.');
    }
}
