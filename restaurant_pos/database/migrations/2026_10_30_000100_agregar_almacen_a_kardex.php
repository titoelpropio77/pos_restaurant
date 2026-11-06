<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El kardex registra el almacén afectado: necesario para traspasos (salida en el origen,
 * entrada en el destino) y para consultar el kardex por almacén.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kardex_insumos', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('insumo_id')->constrained('almacenes')->nullOnDelete();
            $table->index(['almacen_id', 'insumo_id']);
        });

        DB::statement('update kardex_insumos k set almacen_id = coalesce(i.almacen_id, i.almacen_destino_id) from inventarios i where i.id = k.inventario_id');
    }

    public function down(): void
    {
        Schema::table('kardex_insumos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('almacen_id');
        });
    }
};
