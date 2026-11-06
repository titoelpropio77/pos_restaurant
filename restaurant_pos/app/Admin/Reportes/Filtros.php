<?php

namespace App\Admin\Reportes;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Valores de los filtros de un reporte. Por defecto: el mes en curso, todas las sucursales.
 */
class Filtros
{
    public function __construct(
        public Carbon $desde,
        public Carbon $hasta,
        public ?int $sucursalId = null,
        public ?string $cajero = null,
        public ?string $formaPago = null,
        public ?string $turno = null,
        public ?int $categoriaId = null,
        public ?string $estado = null,
        public ?string $tipo = null,
    ) {}

    public static function desde(Request $request): self
    {
        $desde = $request->date('desde') ?? today()->startOfMonth();
        $hasta = $request->date('hasta') ?? today();

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self(
            Carbon::parse($desde)->startOfDay(),
            Carbon::parse($hasta)->startOfDay(),
            $request->integer('sucursal') ?: null,
            $request->query('cajero') ?: null,
            $request->query('formapago') ?: null,
            in_array($request->query('turno'), ['MAÑANA', 'NOCHE'], true) ? $request->query('turno') : null,
            $request->integer('categoria') ?: null,
            $request->query('estado') ?: null,
            $request->query('tipo') ?: null,
        );
    }

    /** @return array{0: string, 1: string} */
    public function rango(): array
    {
        return [$this->desde->toDateString(), $this->hasta->toDateString()];
    }
}
