<?php

namespace App\Services\Tables;

use App\Exceptions\PosException;
use App\Models\DiningTable;
use App\Models\TableOrder;
use App\Models\User;
use App\Services\Sales\RegisterSale;
use App\Support\PosContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cuenta compartida: la mesa se cobra en partes, cada una como una venta propia
 * (nombre, NIT y forma de pago distintos).
 *
 * - Partes iguales ("equal"): la parte se lleva 1/n de cada ítem pendiente, siendo n las
 *   partes que faltan cobrar; la última se lleva todo lo que queda, así no se pierden centavos.
 * - Por ítems ("items"): la parte se lleva las cantidades elegidas de cada ítem.
 *
 * Cuando ya no queda nada pendiente, la mesa se cierra y queda libre.
 */
class SplitBill
{
    public function __construct(private RegisterSale $registerSale, private PosContext $pos) {}

    /**
     * Ítems y total de la próxima parte, sin cobrar nada.
     *
     * @return array{lines: list<array{id: int, product_code: int, product_name: string, note: string, quantity: float, price: float, subtotal: float}>, total: float, pending: float}
     */
    public function preview(DiningTable $diningTable, array $data): array
    {
        $items = $this->pendingItems($diningTable, $data['saleDate']);
        $lines = $this->lines($items, $data);

        return [
            'lines' => $lines,
            'total' => $this->sum($lines),
            'pending' => $this->sum($items->map(fn (TableOrder $item) => [
                'subtotal' => round((float) $item->quantity * (float) $item->sale_price, 2),
            ])->all()),
        ];
    }

    /**
     * Cobra una parte: registra la venta, descuenta lo cobrado de la mesa y la libera si ya no queda saldo.
     *
     * @return array{sale_id: int, total: float, change: float, pending: float, closed: bool}
     */
    public function charge(DiningTable $diningTable, array $data, User $user): array
    {
        return DB::transaction(function () use ($diningTable, $data, $user) {
            $diningTable = DiningTable::lockForUpdate()->findOrFail($diningTable->id);
            $date = $data['saleDate'];
            $items = $this->pendingItems($diningTable, $date, lock: true);
            $lines = $this->lines($items, $data);
            $total = $this->sum($lines);

            if ($total <= 0) {
                throw new PosException('LA PARTE NO TIENE PRODUCTOS PARA COBRAR');
            }

            if ($data['paymentMethod'] === 'CREDITO' && in_array($data['nameCustomer'], ['', 'SIN NOMBRE'], true)) {
                throw new PosException('EL CAMPO NOMBRE DE CLIENTE NO DEBE ESTAR VACIO');
            }

            $received = ($data['received'] ?? '') === '' ? $total : round((float) $data['received'], 2);
            if ($data['paymentMethod'] === 'CONTADO' && $received < $total) {
                throw new PosException('EL MONTO RECIBIDO ES MENOR AL TOTAL DE LA PARTE');
            }
            $change = $data['paymentMethod'] === 'CONTADO' ? round($received - $total, 2) : 0.0;

            $exchangeRate = (float) $this->pos->exchangeRate() ?: 1;
            $result = $this->registerSale->run([
                'saleDate' => $date,
                'nameCustomer' => $data['nameCustomer'] ?: 'SIN NOMBRE',
                'customerTaxId' => $data['customerTaxId'] ?? '',
                'paymentMethod' => $data['paymentMethod'],
                'exitProduct' => mb_substr('MESA:'.$diningTable->table_number, 0, 20),
                'shift' => (string) ($items->first()?->shift ?? ''),
                'totalBs' => $total,
                'totalUsd' => round($total / $exchangeRate, 2),
                'totalInvoiceBs' => $data['paymentMethod'] === 'CONTADO' ? $received : $total,
                'changeBs' => $change,
                'changeUsd' => round($change / $exchangeRate, 2),
                'idProduct' => array_column($lines, 'product_code'),
                'quantity' => array_column($lines, 'quantity'),
                'price' => array_column($lines, 'price'),
                'subTotal' => array_column($lines, 'subtotal'),
                'nameProduct' => array_column($lines, 'product_name'),
                'note' => array_column($lines, 'note'),
            ], $user);

            foreach ($lines as $line) {
                $item = $items->firstWhere('id', $line['id']);
                $item->update(['quantity' => round((float) $item->quantity - $line['quantity'], 2)]);
            }

            $pending = $this->sum($items->map(fn (TableOrder $item) => [
                'subtotal' => round((float) $item->quantity * (float) $item->sale_price, 2),
            ])->all());
            $closed = $items->every(fn (TableOrder $item) => (float) $item->quantity <= 0);

            if ($closed) {
                TableOrder::where('dining_table_id', $diningTable->id)->pending()->where('sale_date', $date)
                    ->update(['closed' => 'A', 'deleted_flag' => 'S']);
                $diningTable->release();
            }

            return ['sale_id' => $result['sale']->id, 'total' => $total, 'change' => $change, 'pending' => $pending, 'closed' => $closed];
        });
    }

    /**
     * Ítems pendientes de la mesa en la fecha de venta.
     *
     * @return Collection<int, TableOrder>
     */
    private function pendingItems(DiningTable $diningTable, string $date, bool $lock = false): Collection
    {
        $query = TableOrder::where('dining_table_id', $diningTable->id)
            ->pending()
            ->where('quantity', '>', 0)
            ->where('sale_date', $date)
            ->orderBy('id');

        $items = ($lock ? $query->lockForUpdate() : $query)->get();

        if ($items->isEmpty()) {
            throw new PosException('LA MESA NO TIENE PRODUCTOS PENDIENTES DE COBRO');
        }
        if ($items->contains(fn (TableOrder $item) => $item->timed === 'T')) {
            throw new PosException('LA CUENTA COMPARTIDA NO ADMITE PRODUCTOS POR TIEMPO');
        }

        return $items;
    }

    private function lines(Collection $items, array $data): array
    {
        $lines = [];

        if ($data['mode'] === 'equal') {
            $parts = max(1, (int) $data['parts']);
            foreach ($items as $item) {
                $available = (float) $item->quantity;
                $quantity = $parts === 1 ? $available : round($available / $parts, 2);
                if ($quantity > 0) {
                    $lines[] = $this->line($item, $quantity);
                }
            }

            return $lines;
        }

        foreach ($data['items'] ?? [] as $chosen) {
            $quantity = round((float) ($chosen['quantity'] ?? 0), 2);
            if ($quantity <= 0) {
                continue;
            }

            $item = $items->firstWhere('id', (int) $chosen['id']);
            if (! $item) {
                throw new PosException('UNO DE LOS PRODUCTOS YA NO ESTÁ PENDIENTE EN LA MESA');
            }
            if ($quantity > (float) $item->quantity) {
                throw new PosException("NO EXISTE ESA CANTIDAD DE {$item->product_name} EN LA MESA");
            }

            $lines[] = $this->line($item, $quantity);
        }

        return $lines;
    }

    private function line(TableOrder $item, float $quantity): array
    {
        return [
            'id' => $item->id,
            'product_code' => (int) $item->product_code,
            'product_name' => (string) $item->product_name,
            'note' => (string) ($item->note ?? ''),
            'quantity' => $quantity,
            'price' => (float) $item->sale_price,
            'subtotal' => round($quantity * (float) $item->sale_price, 2),
        ];
    }

    private function sum(array $lines): float
    {
        return round(array_sum(array_column($lines, 'subtotal')), 2);
    }
}
