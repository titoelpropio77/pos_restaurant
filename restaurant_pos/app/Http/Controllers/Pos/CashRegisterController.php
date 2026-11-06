<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSequence;
use App\Services\CashRegister\CashSessions;
use App\Support\PosContext;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Apertura y cierre de caja desde el POS. El cierre es ciego: quien cierra escribe lo contado
 * antes de ver lo esperado, y la nota sobre la diferencia llega en un segundo paso.
 */
class CashRegisterController extends Controller
{
    public function __construct(private PosContext $pos, private CashSessions $cashSessions) {}

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate(
            ['openingAmount' => ['required', 'numeric', 'min:0', 'max:9999999999.99']],
            ['openingAmount.required' => 'DEBE INDICAR EL MONTO INICIAL'],
        );

        $session = $this->cashSessions->open($request->user(), (float) $data['openingAmount'], $this->pos->branchId());

        return PosResponse::ok($session->id);
    }

    public function closing(Request $request): JsonResponse
    {
        $data = $request->validate(
            ['countedCash' => ['required', 'numeric', 'min:0', 'max:9999999999.99']],
            ['countedCash.required' => 'DEBE INDICAR EL EFECTIVO CONTADO'],
        );

        $user = $request->user();
        $session = $this->cashSessions->current($user);

        if (! $session) {
            return PosResponse::error('NO HAY CAJA ABIERTA');
        }

        $session = $this->cashSessions->close($session, (float) $data['countedCash'], null, $user);
        $this->pos->setDailyOrder(InvoiceSequence::forSales($this->pos->branchId())?->daily_order ?? 0);

        $totals = $this->cashSessions->summary($session) + [
            'counted' => (float) $session->counted_cash,
            'difference' => (float) $session->difference,
        ];

        return PosResponse::ok([array_map(fn ($value) => number_format($value, 2, '.', ''), $totals)], [
            'sessionId' => $session->id,
            'date' => $session->opened_at->toDateString(),
            'cashier' => $user->username,
        ]);
    }

    public function note(Request $request): JsonResponse
    {
        $data = $request->validate(
            ['sessionId' => ['required', 'integer'], 'note' => ['required', 'string', 'max:500']],
            ['note.required' => 'DEBE EXPLICAR LA DIFERENCIA'],
        );

        $this->cashSessions->addNote((int) $data['sessionId'], $data['note'], $request->user());

        return PosResponse::ok();
    }
}
