<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proveedores.
 */
class Proveedor extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'pagina_web',
        'contacto_ref',
        'telefono_contacto',
        'correo_contacto',
        'ciudad_id',
        'cuenta_id',
        'estado',
    ];

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
