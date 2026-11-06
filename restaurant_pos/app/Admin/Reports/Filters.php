<?php

namespace App\Admin\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Valores de los filtros de un reporte. Por defecto: el mes en curso, todas las sucursales.
 */
class Filters
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public ?int $branchId = null,
        public ?string $cashier = null,
        public ?string $paymentMethod = null,
        public ?string $shift = null,
        public ?int $categoryId = null,
        public ?string $status = null,
        public ?string $type = null,
    ) {}

    public static function from(Request $request): self
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return new self(
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->startOfDay(),
            $request->integer('branch') ?: null,
            $request->query('cajero') ?: null,
            $request->query('payment_method') ?: null,
            in_array($request->query('shift'), ['MAÑANA', 'NOCHE'], true) ? $request->query('shift') : null,
            $request->integer('category') ?: null,
            $request->query('status') ?: null,
            $request->query('type') ?: null,
        );
    }

    /** @return array{0: string, 1: string} */
    public function range(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString()];
    }
}
