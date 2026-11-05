<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bancos.
 */
class Banco extends Model
{
    protected $table = 'bancos';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    public function cuentasBancarias(): HasMany
    {
        return $this->hasMany(CuentaBancaria::class);
    }
}
