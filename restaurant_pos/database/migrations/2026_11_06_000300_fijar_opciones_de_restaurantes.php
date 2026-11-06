<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las opciones del POS pasan del .env a cada restaurante: los existentes conservan las que tenían.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('empresas')->whereNull('opciones')->update(['opciones' => json_encode(config('posgourmet.opciones'))]);
    }

    public function down(): void
    {
        DB::table('empresas')->update(['opciones' => null]);
    }
};
