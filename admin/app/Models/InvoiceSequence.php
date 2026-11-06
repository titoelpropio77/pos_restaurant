<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Numeración de ventas de una sucursal.
 */
class InvoiceSequence extends Model
{
    protected $table = 'invoice_sequences';

    protected $guarded = ['id'];
}
