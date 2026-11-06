<?php

namespace App\Services\CashRegister;

use App\Exceptions\PosException;
use App\Models\CashSession;
use App\Models\InvoiceSequence;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Apertura y cierre de caja por punto de venta. Sólo se exige si el restaurante tiene activa
 * la opción daily_close; sin ella el POS trabaja sin sesiones de caja.
 */
class CashSessions
{
    public const NOT_OPEN = 'DEBE ABRIR CAJA';

    public function __construct(private CashCount $cashCount) {}

    public function enabled(): bool
    {
        return (bool) (Tenant::options()['daily_close'] ?? false);
    }

    /** Caja abierta del punto de venta del usuario. */
    public function current(User $user): ?CashSession
    {
        $pointOfSaleId = $user->pointOfSale()->value('id');

        return $pointOfSaleId ? CashSession::open()->where('point_of_sale_id', $pointOfSaleId)->first() : null;
    }

    public function open(User $user, float $amount, int $branchId): CashSession
    {
        $pointOfSale = $user->pointOfSale()->first();

        if (! $pointOfSale) {
            throw new PosException('EL USUARIO NO TIENE UN PUNTO DE VENTA ASIGNADO');
        }

        if ($this->current($user)) {
            throw new PosException('LA CAJA YA ESTÁ ABIERTA');
        }

        try {
            return CashSession::create([
                'branch_id' => $branchId,
                'point_of_sale_id' => $pointOfSale->id,
                'opened_by' => $user->id,
                'opened_at' => now(),
                'opening_amount' => $amount,
                'status' => CashSession::OPEN,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new PosException('LA CAJA YA ESTÁ ABIERTA');
        }
    }

    /**
     * Caja a la que se asigna un movimiento. Debe llamarse dentro de la transacción que lo
     * registra: el bloqueo compartido hace que un cierre en curso espere a que termine.
     *
     * @param  bool  $required  false si el movimiento no toca el efectivo de la caja
     */
    public function forMovement(User $user, bool $required = true): ?int
    {
        if (! $this->enabled()) {
            return null;
        }

        $pointOfSaleId = $user->pointOfSale()->value('id');
        $session = $pointOfSaleId
            ? CashSession::open()->where('point_of_sale_id', $pointOfSaleId)->sharedLock()->first()
            : null;

        if (! $session && $required) {
            throw new PosException(self::NOT_OPEN);
        }

        return $session?->id;
    }

    /** Una venta de una caja cerrada no se anula ni se reactiva: su cierre ya quedó firme. */
    public function ensureSaleEditable(Sale $sale): void
    {
        if (! $sale->cash_session_id) {
            return;
        }

        $session = CashSession::whereKey($sale->cash_session_id)->sharedLock()->first();

        if ($session?->status === CashSession::CLOSED) {
            throw new PosException('NO SE PUEDE MODIFICAR UNA VENTA DE UNA CAJA CERRADA');
        }
    }

    /**
     * Totales de la caja con su monto inicial y el efectivo que debe haber.
     *
     * @return array<string, float>
     */
    public function summary(CashSession $session): array
    {
        $totals = $this->cashCount->forSession($session);
        $opening = (float) $session->opening_amount;

        return $totals + ['opening' => $opening, 'expected' => round($opening + $totals['balance'], 2)];
    }

    public function close(CashSession $session, float $counted, ?string $notes, User $closer): CashSession
    {
        return DB::transaction(function () use ($session, $counted, $notes, $closer) {
            $session = CashSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->status !== CashSession::OPEN) {
                throw new PosException('LA CAJA YA ESTÁ CERRADA');
            }

            $expected = $this->summary($session)['expected'];

            $session->update([
                'status' => CashSession::CLOSED,
                'closed_by' => $closer->id,
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $counted,
                'difference' => round($counted - $expected, 2),
                'notes' => $notes,
            ]);

            $sales = Sale::where('cash_session_id', $session->id);
            SaleItem::whereIn('sale_id', (clone $sales)->select('id'))->update(['closed' => 'S']);
            $sales->update(['closed' => 'S']);

            // La orden del día es de la sucursal: se reinicia cuando cierra su última caja
            if (! CashSession::open()->where('branch_id', $session->branch_id)->exists()) {
                InvoiceSequence::where('branch_id', $session->branch_id)->update(['daily_order' => 0]);
            }

            return $session;
        });
    }

    /** Nota de quien cerró: llega después porque el cierre es ciego. */
    public function addNote(int $sessionId, string $note, User $user): void
    {
        $session = CashSession::closed()->whereKey($sessionId)->where('closed_by', $user->id)->whereNull('notes')->first();

        if (! $session) {
            throw new PosException('EL CIERRE NO EXISTE O YA TIENE NOTA');
        }

        $session->update(['notes' => $note]);
    }
}
