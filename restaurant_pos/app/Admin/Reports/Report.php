<?php

namespace App\Admin\Reports;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base de los reportes del panel. La vista admin.reportes.show dibuja los filtros, la tabla,
 * los totales (columnas moneda/numero) y la exportación a Excel a partir de esta definición.
 */
abstract class Report
{
    /** Filtros disponibles: fechas, sucursal, cajero, formapago, turno, categoria, estado, tipo */
    public const BASE_FILTERS = ['dates', 'branch'];

    abstract public function title(): string;

    abstract public function description(): string;

    abstract public function category_group(): string;

    abstract public function icon(): string;

    /**
     * Columnas: [clave => [etiqueta, formato]]; formatos: texto, fecha, moneda, numero, porcentaje.
     */
    abstract public function columns(): array;

    /**
     * Filas con las claves de columnas(). Una clave "_venta" convierte la fila en enlace a la venta.
     */
    abstract public function rows(Filters $filters): iterable;

    public function filters(): array
    {
        return static::BASE_FILTERS;
    }

    /** Opciones del filtro "tipo", si el reporte lo usa. */
    public function types(): array
    {
        return [];
    }

    /** Columnas numéricas que no deben sumarse en la fila de totales. */
    public function withoutTotal(): array
    {
        return [];
    }

    /** Totales calculados distintos a la suma simple (p. ej. porcentajes o promedios). */
    public function totals(array $rows): array
    {
        $totals = [];
        foreach ($this->columns() as $key => [, $format]) {
            if (in_array($format, ['currency', 'number'], true) && ! in_array($key, $this->withoutTotal(), true)) {
                $totals[$key] = array_sum(array_map(fn ($f) => (float) ($f[$key] ?? 0), $rows));
            }
        }

        return $totals;
    }

    /**
     * Ventas del rango con los filtros comunes aplicados (sucursal, cajero, forma de pago, turno, estado).
     */
    protected function sales(Filters $f): Builder
    {
        return Sale::query()
            ->whereBetween('sales.sale_date', $f->range())
            ->when($f->branchId, fn ($q) => $q->where('sales.branch_id', $f->branchId))
            ->when($f->cashier, fn ($q) => $q->where('sales.login', $f->cashier))
            ->when($f->paymentMethod, fn ($q) => $q->where('sales.payment_method', $f->paymentMethod))
            ->when($f->shift, fn ($q) => $q->where('sales.shift', $f->shift))
            ->when($f->status, fn ($q) => $q->where('sales.status', $f->status));
    }
}
