<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atención en mesas: mesas del salón y pedidos abiertos por mesa.
 *
 * Origen legacy: mesas_mysql, productos_mesas_mysql (NRO_MESA => mesa_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->string('nro_mesa', 10);
            $table->string('estado', 10)->default('ACTIVO');
            $table->string('uso', 1)->nullable()->comment('S = ocupada');
            $table->integer('orden')->default(0);
            $table->string('colores', 60)->nullable()->comment('Color del mesero que la atiende');
            $table->string('procesando', 1)->nullable()->comment('S = bloqueada mientras se cobra');
            $table->string('deleteado', 1)->nullable();
            $table->string('nropersona', 30)->nullable();
            $table->integer('nroorden')->default(0)->comment('Contador de comandas enviadas');
            $table->string('nom_cli', 60)->nullable();
            $table->timestamps();

            $table->index(['sucursal_id', 'orden']);
        });

        // Ítems pedidos en una mesa abierta, pendientes de cobro
        Schema::create('mesa_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->unsignedBigInteger('factura')->default(0);
            $table->integer('orden')->default(0)->comment('Número de comanda de la mesa');
            $table->integer('item')->default(0);
            $table->unsignedBigInteger('cod_prod')->comment('Id del producto (99999 = atención billar)');
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('pre_costo', 12, 4)->default(0);
            $table->decimal('pre_venta', 12, 2)->default(0);
            $table->decimal('cam_of', 10, 2)->default(0);
            $table->date('f_venta');
            $table->string('dhoraven', 5)->nullable();
            $table->string('nom_prod', 70);
            $table->string('unidad', 4)->nullable();
            $table->string('pendiente', 1)->nullable();
            $table->string('anulado', 1)->nullable();
            $table->string('presa', 15)->nullable();
            $table->string('grupo', 30)->nullable();
            $table->string('cerrado', 1)->nullable()->comment('S = ya cobrado / cerrado');
            $table->string('turno', 6)->nullable();
            $table->string('familia', 20)->nullable();
            $table->decimal('unid', 12, 3)->default(0);
            $table->string('salida', 20)->nullable();
            $table->integer('fortax')->default(0);
            $table->integer('tot_des')->default(0);
            $table->decimal('factor', 10, 5)->default(0);
            $table->string('agrupa1', 20)->nullable()->comment('Subtotal');
            $table->string('agrupa2', 20)->nullable();
            $table->string('agrupa3', 20)->nullable();
            $table->string('cod_grupo', 90)->nullable();
            $table->string('login', 20)->nullable();
            $table->string('nota', 254)->nullable();
            $table->string('item_eli', 4)->nullable()->comment('S = ítem eliminado');
            $table->string('nom_empl', 60)->nullable()->comment('Mesero');
            $table->string('deleteado', 1)->nullable();
            $table->string('tiempo', 4)->nullable();
            $table->string('tiempo_ini', 11)->nullable();
            $table->string('tiempo_fin', 11)->nullable();
            $table->timestamps();

            $table->index(['mesa_id', 'f_venta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_pedidos');
        Schema::dropIfExists('mesas');
    }
};
