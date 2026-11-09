<?php

namespace App\Services\Tables;

use App\Exceptions\PosException;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Staff;
use App\Models\TableOrder;
use App\Models\User;
use App\Support\PosContext;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Atención en mesas: apertura, comandas adicionales, traspasos, cobro y anulación.
 * Port de productoMesaController.php.
 */
class TableOrders
{
    public function __construct(private PosContext $pos) {}

    /**
     * Abre una mesa libre, con los productos de la primera comanda o sin productos.
     *
     * @return int Número de comanda de la mesa (0 si se abrió sin productos)
     */
    public function open(DiningTable $diningTable, array $data, User $user): int
    {
        return DB::transaction(function () use ($diningTable, $data, $user) {
            $diningTable = DiningTable::lockForUpdate()->findOrFail($diningTable->id);

            if ($diningTable->isOccupied()) {
                throw new PosException('LA MESA YA ESTÁ OCUPADA');
            }

            $waiter = Staff::find($data['idWaiter']);

            if (! $waiter) {
                throw new PosException('MESERO NO ENCONTRADO');
            }

            // El código del mesero no viaja al navegador: se verifica aquí contra el mesero elegido.
            $codeRequired = ! Tenant::option('waiters_without_code') && ! Tenant::option('without_view_employee');
            if ($codeRequired && (string) $waiter->code !== trim((string) ($data['waiterCode'] ?? ''))) {
                throw new PosException('CÓDIGO DE MESERO ERRÓNEO, INTENTE NUEVAMENTE');
            }

            // La mesa puede abrirse sin productos: entonces no hay primera comanda todavía
            $products = $data['idProduct'] ?? [];
            $ticket = $diningTable->ticket_count + ($products === [] ? 0 : 1);
            $time = now()->format('G:i');

            foreach ($products as $i => $productId) {
                $this->createItem($diningTable, $ticket, $i + 1, (int) $productId, (float) $data['quantity'][$i], [
                    'sale_date' => $data['saleDate'],
                    'sale_time' => $time,
                    'shift' => $data['shift'] ?? '',
                    'service_type' => 'MESA: '.$diningTable->table_number,
                    'subtotal' => $data['subtotal'][$i] ?? 0,
                    'login' => $user->username,
                    'note' => $data['note'][$i] ?? '',
                    'staff_name' => $waiter->staff_name,
                    'time_start' => $time,
                ], ($data['billiards'] ?? '') === 'billiards');
            }

            $this->occupy($diningTable, $ticket, $waiter->staff_color, $data['qtyCustomer'] ?? '', $this->isDiningRoom($data));
            $diningTable->update(['waiter_name' => $waiter->staff_name]);

            return $ticket;
        });
    }

    /**
     * Agrega una nueva comanda a una mesa ya ocupada.
     */
    public function add(DiningTable $diningTable, array $data, User $user): int
    {
        return DB::transaction(function () use ($diningTable, $data, $user) {
            $diningTable = DiningTable::lockForUpdate()->findOrFail($diningTable->id);
            $date = $data['saleDate'];

            if (($data['typeProduct'] ?? '') === 'T' && $this->timeOpen($diningTable, $date)) {
                throw new PosException('* DEBE PARAR EL TIEMPO DE LA MESA');
            }

            $previous = $this->detail($diningTable, $date)->first();
            $ticket = $diningTable->ticket_count + 1;
            $time = now()->format('G:i');

            foreach ($data['idProduct'] as $i => $productId) {
                $this->createItem($diningTable, $ticket, $i + 1, (int) $productId, (float) $data['quantity'][$i], [
                    'sale_date' => $date,
                    'sale_time' => $time,
                    'shift' => $previous?->shift ?? '',
                    'service_type' => (string) ($data['numberTable'] ?? $diningTable->table_number),
                    'login' => $user->username,
                    'note' => $data['noteProduct'][$i] ?? '',
                    'staff_name' => $previous?->staff_name ?? ($diningTable->waiter_name ?? ''),
                    'time_start' => $time,
                ]);
            }

            $diningTable->update(['ticket_count' => $ticket]);

            return $ticket;
        });
    }

    /**
     * Ítems abiertos de la mesa en la fecha de venta.
     */
    public function detail(DiningTable $diningTable, string $date): Collection
    {
        return TableOrder::where('dining_table_id', $diningTable->id)
            ->pending()
            ->where('quantity', '<>', 0)
            ->where('sale_date', $date)
            ->orderBy('id')
            ->get();
    }

    /**
     * Tras cobrar en caja: opción 1 descuenta sólo lo pagado; opción 2 cierra toda la mesa.
     */
    public function updateAfterCheckout(DiningTable $diningTable, int $option_name, array $idsDetail, array $quantities): void
    {
        DB::transaction(function () use ($diningTable, $option_name, $idsDetail, $quantities) {
            if ($option_name === 1) {
                $diningTable->update(['processing' => '']);
                foreach ($idsDetail as $i => $id) {
                    $item = TableOrder::lockForUpdate()->findOrFail($id);
                    $item->update(['quantity' => (float) $item->quantity - (float) $quantities[$i]]);
                }
            } elseif ($option_name === 2) {
                $diningTable->release();
                TableOrder::whereIn('id', $idsDetail)->update(['closed' => 'A', 'deleted_flag' => 'S']);
            }

            $this->pos->setTableProcessing(null);
        });
    }

    /**
     * Pasa ítems (total o parcialmente) de una mesa a otra.
     */
    public function transfer(DiningTable $source, DiningTable $destination, array $data, User $user): void
    {
        DB::transaction(function () use ($source, $destination, $data, $user) {
            $date = $data['date'];
            $destinationFree = ($data['statusTable'] ?? '') === 'open';

            if ($destinationFree) {
                $isDiningRoom = $this->isDiningRoom($data);
                $this->occupy($destination, 1, $source->color, $isDiningRoom ? $source->customer_name : $source->people_count, $isDiningRoom);
            }

            foreach ($data['idItem'] as $i => $idDetail) {
                $item = TableOrder::lockForUpdate()->findOrFail($idDetail);
                $quantity = $item->timed === 'T' ? 1.0 : (float) $data['quantity'][$i];
                $remaining = (float) $item->quantity - $quantity;

                if ($remaining < 0) {
                    throw new PosException('NO EXISTE ESA CANTIDAD EN ESA MESA');
                }

                $item->update($remaining == 0 ? ['quantity' => 0, 'deleted_flag' => 'X'] : ['quantity' => $remaining]);

                $existing = $destinationFree ? null : $this->detail($destination, $date)
                    ->first(fn (TableOrder $d) => (int) $d->product_code === (int) $item->product_code && (string) $d->note === (string) $item->note);

                if ($existing) {
                    $existing->update(['quantity' => (float) $existing->quantity + $quantity]);
                } else {
                    $is_new = $item->replicate(['created_at', 'updated_at']);
                    $is_new->fill([
                        'dining_table_id' => $destination->id,
                        'branch_id' => $this->pos->branchId(),
                        'quantity' => $quantity,
                        'cost_price' => 0,
                        'exchange_rate' => $this->pos->exchangeRate(),
                        'sale_date' => $date,
                        'sale_time' => now()->format('G:i'),
                        'family' => '',
                        'service_type' => (string) ($data['numberTableTransfer'] ?? $destination->table_number),
                        'subtotal' => (string) ($quantity * (float) $item->sale_price),
                        'login' => $user->username,
                        'item_deleted' => '',
                        'deleted_flag' => '',
                        'closed' => '',
                        'staff_name' => $user->name,
                    ])->save();
                }
            }

            if ((string) ($data['enableTable'] ?? '0') === '1') {
                $source->release();
            }
        });
    }

    public function void(DiningTable $diningTable, string $date): void
    {
        DB::transaction(function () use ($diningTable, $date) {
            TableOrder::whereIn('id', $this->detail($diningTable, $date)->modelKeys())
                ->update(['closed' => 'A', 'deleted_flag' => 'S']);
            $diningTable->release();
        });
    }

    public function deleteItem(DiningTable $diningTable, TableOrder $item, bool $releaseTable): void
    {
        DB::transaction(function () use ($diningTable, $item, $releaseTable) {
            $item->update(['item_deleted' => 'X']);
            if ($releaseTable) {
                $diningTable->release();
            }
        });
    }

    /**
     * Elimina parcialmente ítems de la mesa: descuenta la cantidad y registra la porción
     * eliminada como ítem eliminado (aparece en el reporte de ítems eliminados del cierre).
     */
    public function deleteQuantities(DiningTable $diningTable, array $idsDetail, array $quantities): void
    {
        DB::transaction(function () use ($diningTable, $idsDetail, $quantities) {
            foreach ($idsDetail as $i => $id) {
                $item = TableOrder::where('dining_table_id', $diningTable->id)->pending()->lockForUpdate()->findOrFail($id);
                $quantity = (float) $quantities[$i];

                if ($item->timed === 'T') {
                    throw new PosException('NO PUEDE ELIMINAR UN PRODUCTO DE TIEMPO');
                }
                if ($quantity <= 0 || $quantity > (float) $item->quantity) {
                    throw new PosException("CANTIDAD INVÁLIDA PARA {$item->product_name}");
                }

                $deleted = $item->replicate(['created_at', 'updated_at']);
                $deleted->fill(['quantity' => $quantity, 'item_deleted' => 'X'])->save();

                $item->update(['quantity' => (float) $item->quantity - $quantity]);
            }
        });
    }

    private function timeOpen(DiningTable $diningTable, string $date): bool
    {
        return TableOrder::where('dining_table_id', $diningTable->id)
            ->pending()
            ->where('timed', 'T')
            ->whereRaw("coalesce(time_end, '') = ''")
            ->where('sale_date', $date)
            ->exists();
    }

    private function createItem(DiningTable $diningTable, int $ticket, int $item, int $productId, float $quantity, array $data, bool $billiards = false): void
    {
        if ($billiards && $productId === config('pos.product_service_billiards')) {
            $product = ['sale_price' => 0, 'product_name' => 'ATENCION', 'category_group' => '0', 'family' => '0', 'unit_factor' => 0, 'group_code' => '0', 'options_mode' => '', 'timed' => ''];
        } else {
            $p = Product::forPos($this->pos->branchId())->where('products.id', $productId)->firstOrFail();
            $product = [
                'sale_price' => $p->sale_price,
                'product_name' => $p->product_name,
                'category_group' => (string) $p->category_group,
                'family' => $p->family,
                'unit_factor' => $p->unit_factor,
                'group_code' => $p->group_code,
                'options_mode' => $p->options_mode,
                'timed' => $p->timed ?? '',
            ];
        }

        TableOrder::create(array_merge($product, $data, [
            'dining_table_id' => $diningTable->id,
            'branch_id' => $this->pos->branchId(),
            'invoice_ref' => 0,
            'order_number' => $ticket,
            'item' => $item,
            'product_code' => $productId,
            'quantity' => $quantity,
            'cost_price' => 0,
            'exchange_rate' => $this->pos->exchangeRate(),
            'closed' => '',
            'deleted_flag' => '',
            'item_deleted' => '',
            'time_end' => '',
        ]));
    }

    private function occupy(DiningTable $diningTable, int $ticket, ?string $color, ?string $person, bool $isDiningRoom): void
    {
        $diningTable->update([
            'ticket_count' => $ticket,
            'occupied' => 'S',
            'color' => $color ?? '',
            'people_count' => $isDiningRoom ? '' : (string) $person,
            'customer_name' => $isDiningRoom ? (string) $person : '',
        ]);
    }

    private function isDiningRoom(array $data): bool
    {
        return ($data['dining_room'] ?? 'no_dining_room') === 'dining_room';
    }
}
