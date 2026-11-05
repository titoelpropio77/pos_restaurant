<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario de insumos por almacén, kardex y proveedores.
 *
 * Origen legacy: unidadmedida, almacen, motivomovimiento, proveedores, insumos_mysql,
 * insumoalmacen, inventario, kardexins_mysql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
            $table->string('abreviatura', 30);
            $table->smallInteger('estado')->default(1);
            $table->timestamps();
        });

        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->string('estado', 10)->default('ACTIVO');
            $table->unsignedBigInteger('empleado_responsable_id')->nullable();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('motivos_movimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('estado', 10)->default('ACTIVO');
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('pagina_web')->nullable();
            $table->string('contacto_ref', 100)->nullable();
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('correo_contacto')->nullable();
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudades')->nullOnDelete();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('cod_ins', 10)->nullable();
            $table->string('nom_insumo', 60);
            $table->decimal('stock_min', 12, 3)->default(0);
            $table->decimal('stock_act', 12, 3)->default(0);
            $table->string('medida', 20)->nullable()->comment('Unidad de consumo');
            $table->string('medidam', 20)->nullable()->comment('Unidad media');
            $table->string('ope_mm', 1)->nullable()->comment('Operador de conversión a unidad media');
            $table->decimal('val_for_mm', 12, 3)->default(0);
            $table->string('ope_mx', 1)->nullable()->comment('Operador de conversión a unidad mayor');
            $table->string('medidax', 20)->nullable()->comment('Unidad mayor (compra)');
            $table->decimal('val_for_mx', 12, 3)->default(0);
            $table->string('adicionado', 1)->nullable();
            $table->decimal('can_adi', 12, 3)->default(0);
            $table->string('estado', 8)->default('ACTIVO');
            $table->string('cod_prod', 90)->nullable();
            $table->decimal('unidad', 12, 3)->default(0);
            $table->decimal('pre_costo', 12, 4)->nullable();
            $table->decimal('ppm', 12, 4)->nullable()->comment('Precio promedio ponderado');
            $table->decimal('precio_ultima_compra', 12, 4)->nullable();
            $table->timestamps();

            $table->index('nom_insumo');
        });

        Schema::create('insumo_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->decimal('stock_actual', 14, 4)->default(0);
            $table->timestamps();

            $table->unique(['insumo_id', 'almacen_id']);
        });

        // Cabecera de movimientos de inventario (compras, ventas, traspasos, ajustes)
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora')->nullable();
            $table->decimal('importe', 12, 4)->default(0);
            $table->string('tipo', 15)->nullable();
            $table->string('glosa', 500)->nullable();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->string('estado', 10)->nullable();
            $table->foreignId('motivo_movimiento_id')->nullable()->constrained('motivos_movimiento')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
        });

        Schema::create('kardex_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_id')->nullable()->constrained('inventarios')->cascadeOnDelete();
            $table->unsignedBigInteger('venta_id')->nullable()->index()->comment('Documento origen (venta) si aplica');
            $table->foreignId('insumo_id')->constrained('insumos');
            $table->string('nom_insumo', 60)->nullable();
            $table->decimal('cantidad', 14, 4)->default(0);
            $table->date('fecha_mov');
            $table->string('pormenores', 60)->nullable();
            $table->decimal('entrada', 20, 4)->default(0);
            $table->decimal('salida', 15, 4)->default(0);
            $table->decimal('saldo', 20, 4)->default(0);
            $table->string('movimiento', 10)->nullable();
            $table->decimal('recepcion', 9, 3)->default(0);
            $table->decimal('perdida', 9, 3)->default(0);
            $table->decimal('pre_costo', 12, 4)->nullable();
            $table->decimal('ppm', 12, 4)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->timestamps();

            $table->index(['insumo_id', 'fecha_mov']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_insumos');
        Schema::dropIfExists('inventarios');
        Schema::dropIfExists('insumo_almacen');
        Schema::dropIfExists('insumos');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('motivos_movimiento');
        Schema::dropIfExists('almacenes');
        Schema::dropIfExists('unidades_medida');
    }
};
