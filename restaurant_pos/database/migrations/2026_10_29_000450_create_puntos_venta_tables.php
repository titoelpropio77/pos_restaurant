<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punto de venta: asigna cada usuario (cajero) a una sucursal y almacén por defecto.
 *
 * Origen legacy: puntoventa, puntoventacaja, puntoventacuenta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('venta_multialmacen')->default(false)->comment('Permite cambiar de sucursal desde el POS');
            $table->foreignId('almacen_por_defecto_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->boolean('puede_vender')->default(true);
            $table->timestamps();

            $table->unique('usuario_id');
        });

        Schema::create('punto_venta_cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punto_venta_id')->constrained('puntos_venta')->cascadeOnDelete();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('punto_venta_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punto_venta_id')->constrained('puntos_venta')->cascadeOnDelete();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuentas_bancarias')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punto_venta_cuentas');
        Schema::dropIfExists('punto_venta_cajas');
        Schema::dropIfExists('puntos_venta');
    }
};
