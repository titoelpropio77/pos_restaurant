<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Datos fiscales y de dosificación SIN por sucursal (legacy: empresa_mysql).
 */
class Dosificacion extends Model
{
    protected $table = 'dosificaciones';

    protected $fillable = [
        'sucursal_id',
        'codemp',
        'nomemp',
        'nompro',
        'ciupai',
        'nitruc',
        'logo',
        'dirlog',
        'moneda',
        'sucursal',
        'alfa',
        'diremp',
        'inifac',
        'finfac',
        'facact',
        'facactr',
        'telemp',
        'numorden',
        'tit_imposi',
        'fec_docis',
        'tit_impcli',
        'hinitm',
        'hfintm',
        'vercod',
        'preview',
        'xp',
        'garzones',
        'blocker',
        'cocinacaja',
        'inactiva',
        'r1o',
        'r2o',
        'r1n',
        'r2n',
        'llave',
        'nroautori',
        'fecha_lim',
        'mesa',
        'llevar',
        'nombase',
        'ordendia',
        'ip',
        'leyenda1',
        'leyenda2',
    ];

    protected function casts(): array
    {
        return [
            'fec_docis' => 'date:Y-m-d',
            'fecha_lim' => 'date:Y-m-d',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Última dosificación registrada para la sucursal (legacy: EMPRESA_MYSQL::obtenerUltimo).
     */
    public static function vigenteDe(int $sucursalId): ?self
    {
        return static::where('sucursal_id', $sucursalId)->latest('id')->first();
    }

    /**
     * Días que faltan para la fecha límite de emisión (negativo si ya venció).
     */
    public function diasParaVencer(DateTimeInterface|string $fecha): ?int
    {
        if (! $this->fecha_lim) {
            return null;
        }

        return (int) Carbon::parse($fecha)->startOfDay()
            ->diffInDays(Carbon::parse($this->fecha_lim)->startOfDay(), false);
    }
}
