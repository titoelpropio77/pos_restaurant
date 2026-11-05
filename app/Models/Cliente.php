<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Clientes.
 */
class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'cod_cli',
        'zona_cli',
        'nom_cli',
        'tel_cli',
        'cel_cli',
        'nit_cli',
        'dir_cli',
        'mail_cli',
        'ci_cli',
        'foto',
        'fec_nac',
    ];

    protected function casts(): array
    {
        return [
            'fec_nac' => 'date:Y-m-d',
        ];
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
