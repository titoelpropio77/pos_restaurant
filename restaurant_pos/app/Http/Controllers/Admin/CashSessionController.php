<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Services\CashRegister\CashSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cajas abiertas y cierres realizados. Desde aquí se cierra una caja que quedó olvidada.
 */
class CashSessionController extends Controller
{
    public function index(Request $request, CashSessions $cashSessions): View
    {
        $open = CashSession::open()->with(['branch', 'opener'])->orderBy('opened_at')->get();

        return view('admin.cash_sessions.index', [
            'open' => $open,
            'expected' => $open->mapWithKeys(fn (CashSession $s) => [$s->id => $cashSessions->summary($s)['expected']]),
            'closed' => CashSession::closed()
                ->with(['branch', 'opener', 'closer'])
                ->when($request->date('from'), fn ($q, $from) => $q->whereDate('closed_at', '>=', $from))
                ->when($request->date('to'), fn ($q, $to) => $q->whereDate('closed_at', '<=', $to))
                ->latest('closed_at')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function close(Request $request, CashSession $cashSession, CashSessions $cashSessions): RedirectResponse
    {
        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $cashSessions->close($cashSession, (float) $data['counted_cash'], $data['notes'], $request->user());

        return back()->with('ok', 'Caja cerrada correctamente.');
    }
}
