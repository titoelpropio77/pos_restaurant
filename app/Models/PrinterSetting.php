<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IP del servidor local de impresión.
 */
class PrinterSetting extends Model
{
    protected $table = 'printer_settings';

    protected $fillable = [
        'ipcliente',
        'ipclienteaux',
    ];
}
