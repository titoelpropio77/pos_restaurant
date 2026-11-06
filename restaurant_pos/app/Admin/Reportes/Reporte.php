<?php

namespace App\Admin\Reportes;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base de los reportes del panel. La vista admin.reportes.show dibuja los filtros, la tabla,
 * los totales (columnas moneda/numero) y la exportación a Excel a partir de esta definición.
 */
abstract class Reporte
{
    /** Filtros disponibles: fechas, sucursal, cajero, formapago, turno, categoria, estado, tipo */
    public const FILTROS_BASE = ['fechas', 'sucursal'];

    abstract public function titulo(): string;

    abstract public function descripcion(): string;

    abstract public function grupo(): string;

    abstract public function icono(): string;

    /**
     * Columnas: [clave => [etiqueta, formato]]; formatos: texto, fecha, moneda, numero, porcentaje.
     */
    abstract public function columnas(): array;

    /**
     * Filas con las claves de columnas(). Una clave "_venta" convierte la fila en enlace a la venta.
     */
    abstract public function filas(Filtros $filtros): iterable;

    public function filtros(): array
    {
        return static::FILTROS_BASE;
    }

    /** Opciones del filtro "tipo", si el reporte lo usa. */
    public function tipos(): array
    {
        return [];
    }

    /** Columnas numéricas que no deben sumarse en la fila de totales. */
    public function sinTotal(): array
    {
        return [];
    }

    /** Totales calculados distintos a la suma simple (p. ej. porcentajes o promedios). */
    public function totales(array $filas): array
    {
        $totales = [];
        foreach ($this->columnas() as $clave => [, $formato]) {
            if (in_array($formato, ['moneda', 'numero'], true) && ! in_array($clave, $this->sinTotal(), true)) {
                $totales[$clave] = array_sum(array_map(fn ($f) => (float) ($f[$clave] ?? 0), $filas));
            }
        }

        return $totales;
    }

    /**
     * Ventas del rango con los filtros comunes aplicados (sucursal, cajero, forma de pago, turno, estado).
     */
    protected function ventas(Filtros $f): Builder
    {
        return Venta::query()
            ->whereBetween('ventas.f_venta', $f->rango())
            ->when($f->sucursalId, fn ($q) => $q->where('ventas.sucursal_id', $f->sucursalId))
            ->when($f->cajero, fn ($q) => $q->where('ventas.login', $f->cajero))
            ->when($f->formaPago, fn ($q) => $q->where('ventas.formapago', $f->formaPago))
            ->when($f->turno, fn ($q) => $q->where('ventas.turno', $f->turno))
            ->when($f->estado, fn ($q) => $q->where('ventas.estado', $f->estado));
    }
}
