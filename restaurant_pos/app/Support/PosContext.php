<?php

namespace App\Support;

use App\Exceptions\PosException;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\InvoiceSequence;
use App\Models\PointOfSale;
use Illuminate\Contracts\Session\Session;

/**
 * Datos de trabajo del cajero guardados en sesión al ingresar al POS
 * (sucursal, almacén, tipo de cambio, horario de turnos y orden del día).
 *
 * Reemplaza las variables $_SESSION del sistema legacy (idSucursal, camoff, HINITM...).
 */
class PosContext
{
    private const KEY = 'pos';

    public function __construct(private Session $session) {}

    public function start(PointOfSale $pointOfSale, float $exchangeRate): void
    {
        $this->session->put(self::KEY, [
            'invoice_exchange_rate' => $exchangeRate,
            'multi_branch_sales' => $pointOfSale->multi_branch_sales,
            'default_warehouse_id' => $pointOfSale->default_warehouse_id,
            'table_processing_id' => null,
        ]);

        $this->changeBranch($pointOfSale->branch, $pointOfSale->default_warehouse_id);
    }

    /**
     * Cambia la sucursal de trabajo; exige que tenga dosificación registrada.
     */
    public function changeBranch(Branch $branch, ?int $warehouseId = null): void
    {
        $authorization = BillingAuthorization::currentFor($branch->id);

        if (! $authorization) {
            throw new PosException('NO HAY DOSIFICACIÓN PARA ESTA SUCURSAL');
        }

        $warehouseId ??= $branch->warehouses()->value('id');

        $this->session->put(self::KEY, array_merge($this->data(), [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'default_warehouse_id' => $warehouseId,
            'time_start_morning' => substr((string) $authorization->morning_start, 0, 5),
            'time_end_morning' => substr((string) $authorization->morning_end, 0, 5),
            'restaurant' => $authorization->business_name,
            'daily_order' => InvoiceSequence::forSales($branch->id)?->daily_order ?? 0,
        ]));
    }

    public function active(): bool
    {
        return isset($this->data()['branch_id']);
    }

    public function branchId(): int
    {
        return (int) ($this->data()['branch_id'] ?? 0);
    }

    public function branchName(): string
    {
        return (string) ($this->data()['branch_name'] ?? '');
    }

    public function warehouseId(): ?int
    {
        return $this->data()['default_warehouse_id'] ?? null;
    }

    public function multiBranchSales(): bool
    {
        return (bool) ($this->data()['multi_branch_sales'] ?? false);
    }

    public function exchangeRate(): float
    {
        return (float) ($this->data()['invoice_exchange_rate'] ?? 0);
    }

    public function timeStartMorning(): string
    {
        return (string) ($this->data()['time_start_morning'] ?? '');
    }

    public function timeEndMorning(): string
    {
        return (string) ($this->data()['time_end_morning'] ?? '');
    }

    public function dailyOrder(): int
    {
        return (int) ($this->data()['daily_order'] ?? 0);
    }

    public function setDailyOrder(int $order_number): void
    {
        $this->set('daily_order', $order_number);
    }

    public function tableProcessingId(): ?int
    {
        return $this->data()['table_processing_id'] ?? null;
    }

    public function setTableProcessing(?int $tableId): void
    {
        $this->set('table_processing_id', $tableId);
    }

    public function forget(): void
    {
        $this->session->forget(self::KEY);
    }

    private function set(string $key, mixed $value): void
    {
        $this->session->put(self::KEY.'.'.$key, $value);
    }

    private function data(): array
    {
        return $this->session->get(self::KEY, []);
    }
}
