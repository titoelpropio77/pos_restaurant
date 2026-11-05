<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo contable: plan de cuentas, gestiones, asientos, bancos, cajas e impuestos.
 *
 * Origen legacy: cuentacontable, gestion, centro_costo, cuenta_grupo, cuenta_impuesto,
 * cuentaautomatica, impuestos, asiento, det_asiento_cuenta, banco, cuentabancaria,
 * cajacuenta, tipoingreso, tipoegreso, tipogastocompra, tipocliente, listaprecio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_contables', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 25);
            $table->unsignedBigInteger('padre_id')->default(0)->comment('0 = cuenta raíz');
            $table->integer('hijo')->default(0);
            $table->string('nombre', 100);
            $table->smallInteger('estado')->default(1);
            $table->smallInteger('utilizable')->default(0)->comment('1 = cuenta de movimiento');
            $table->foreignId('moneda_id')->nullable()->constrained('monedas')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('padre_id');
        });

        Schema::create('gestiones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->smallInteger('estado')->default(1);
            $table->string('nombre_gestion', 25)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('centros_costo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('abreviatura', 11)->nullable();
            $table->string('estado', 11)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('cuenta_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_contable_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->string('descripcion', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('cuenta_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_contable_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->decimal('porcentaje_des', 9, 4)->nullable();
            $table->string('descripcion', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('cuentas_automaticas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('cuenta_id')->constrained('cuentas_contables');
            $table->string('estado', 10)->nullable();
            $table->string('modulo', 60)->nullable();
            $table->string('tipo_parametro', 60)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->decimal('valor', 9, 4)->default(0)->comment('Porcentaje');
            $table->boolean('eliminado')->default(false);
            $table->timestamps();
        });

        Schema::create('asientos', function (Blueprint $table) {
            $table->id();
            $table->integer('nro_asiento')->nullable();
            $table->string('glosa', 500)->nullable();
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->dateTime('fecha_transaccion')->nullable();
            $table->decimal('cambio_tipo', 6, 2);
            $table->smallInteger('estado')->nullable();
            $table->string('motivo_anulado', 200)->nullable();
            $table->foreignId('gestion_id')->nullable()->constrained('gestiones')->nullOnDelete();
            $table->string('categoria_asiento', 60)->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('documento', 200)->nullable();
            $table->decimal('total_bs', 12, 2)->nullable();
            $table->decimal('total_sus', 12, 2)->nullable();
            $table->string('relacionado_a', 100)->nullable();
            $table->string('tipo_asiento', 50)->nullable();
            $table->string('origen', 11)->nullable();
            $table->timestamps();
        });

        Schema::create('asiento_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asiento_id')->constrained('asientos')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained('cuentas_contables');
            $table->integer('nro_detalle')->nullable();
            $table->smallInteger('tipo')->comment('1 = debe, 2 = haber');
            $table->decimal('monto_sus', 15, 2)->default(0);
            $table->decimal('monto_bs', 15, 2)->default(0);
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_id')->constrained('bancos');
            $table->string('nro_cuenta', 40);
            $table->string('tipo_cuenta', 30)->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->string('razon_social', 150)->nullable();
            $table->string('moneda', 10)->nullable();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->boolean('tarjeta_debito')->default(false);
            $table->boolean('tarjeta_credito')->default(false);
            $table->boolean('chequera')->default(false);
            $table->timestamps();
        });

        // Cajas asociadas a una cuenta contable (legacy: cajacuenta)
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->string('estado', 10)->default('ACTIVO');
            $table->boolean('eliminado')->default(false);
            $table->string('moneda', 10)->nullable();
            $table->timestamps();
        });

        foreach (['tipos_ingreso', 'tipos_egreso', 'tipos_gasto_compra'] as $tabla) {
            Schema::create($tabla, function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 60);
                $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
                $table->smallInteger('estado')->default(1);
                $table->timestamps();
            });
        }

        Schema::create('tipos_cliente', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('abreviatura', 10)->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('listas_precio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->foreignId('moneda_id')->nullable()->constrained('monedas')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listas_precio');
        Schema::dropIfExists('tipos_cliente');
        Schema::dropIfExists('tipos_gasto_compra');
        Schema::dropIfExists('tipos_egreso');
        Schema::dropIfExists('tipos_ingreso');
        Schema::dropIfExists('cajas');
        Schema::dropIfExists('cuentas_bancarias');
        Schema::dropIfExists('bancos');
        Schema::dropIfExists('asiento_detalles');
        Schema::dropIfExists('asientos');
        Schema::dropIfExists('impuestos');
        Schema::dropIfExists('cuentas_automaticas');
        Schema::dropIfExists('cuenta_impuestos');
        Schema::dropIfExists('cuenta_grupos');
        Schema::dropIfExists('centros_costo');
        Schema::dropIfExists('gestiones');
        Schema::dropIfExists('cuentas_contables');
    }
};
