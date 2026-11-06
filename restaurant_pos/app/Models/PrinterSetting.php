<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * IP del servidor local de impresión.
 */
class PrinterSetting extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'printer_settings';

    protected $fillable = [
        'ipcliente',
        'ipclienteaux',
    ];
}
