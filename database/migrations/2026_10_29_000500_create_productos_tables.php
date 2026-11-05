<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo: categorías del menú, productos, precios/configuración por sucursal,
 * opciones de producto, recetas (insumos) y grupos de reporte.
 *
 * Origen legacy: menugrupo_mysql, productos_mysql, productosucursal, opproductos_mysql,
 * grupos_mysql, relprogru_mysql, relproins_mysql.
 */
return new class extends Migration
{
    private const DIAS = ['lun', 'mar', 'mier', 'jue', 'vie', 'sab', 'dom'];

    public function up(): void
    {
        // Botones de categoría del menú en el POS (legacy: menugrupo_mysql)
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('cod_grupo', 10)->nullable();
            $table->string('nom_grupo', 60);
            $table->integer('grupo')->default(0);
            $table->string('estado', 10)->default('ACTIVO');
            $table->string('colores', 15)->nullable();
            $table->string('colorletra', 15)->nullable();
            $table->string('imagen', 254)->nullable();
            $table->integer('orden')->default(0);
            $table->string('imgnormal', 254)->nullable();
            $table->string('imgpresion', 254)->nullable();
            $table->string('imgsobre', 254)->nullable();
            $table->string('imgnodispo', 254)->nullable();
            $table->string('img', 254)->nullable();
            $table->string('invisible', 1)->nullable();
            $table->string('dia', 1)->nullable();
            $table->timestamps();

            $table->index('orden');
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->integer('cod_prod')->nullable();
            $table->string('nom_prod', 70);
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->decimal('pre_venta', 13, 2)->default(0);
            $table->decimal('unid', 20, 3)->default(1);
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('disponible', 2)->default('SI');
            $table->string('estado', 10)->default('ACTIVO');
            $table->string('presa', 15)->nullable();
            $table->string('familia', 20)->nullable();
            $table->string('unidad', 4)->nullable();
            $table->decimal('pre_costo', 13, 3)->default(0);
            $table->string('colores', 40)->nullable();
            $table->string('nuevo', 1)->nullable();
            $table->decimal('stock', 12, 2)->nullable();
            $table->decimal('cantidadac', 12, 2)->nullable();
            $table->decimal('factor', 12, 2)->nullable();
            $table->string('agrupa1', 20)->nullable();
            $table->string('agrupa2', 20)->nullable();
            $table->string('agrupa3', 20)->nullable();
            $table->string('cod_grupo', 90)->nullable();
            $table->string('cod_ins', 90)->nullable();
            $table->integer('posicion')->nullable();
            $table->string('img', 200)->nullable();
            $table->string('colorletra', 16)->nullable();
            $table->string('barcode', 30)->nullable();
            $table->string('ordenes', 10)->nullable();
            $table->string('ordenes1', 10)->nullable();
            $table->string('deconsumo', 1)->nullable();
            $table->string('invisible', 1)->nullable();
            $table->string('codeqr', 254)->nullable();
            $table->date('f_vence')->nullable()->default('2999-12-29');
            $table->string('grupocmds', 10)->nullable();
            $table->string('grupocmds1', 10)->nullable();
            $table->integer('indice')->nullable();
            $table->string('dia', 40)->nullable();
            $table->string('tiempo', 1)->nullable()->comment('T = producto cobrado por tiempo (billar)');
            $table->string('tiempo_ini', 10)->nullable();
            $table->string('tiempo_fin', 10)->nullable();
            $table->string('impcoc', 1)->nullable()->comment('x = imprime en cocina');
            $table->string('imppunt1', 1)->nullable();
            $table->string('imppunt2', 1)->nullable();
            $table->string('imppunt3', 1)->nullable();
            $table->string('imppunt4', 1)->nullable();
            $table->string('imgnormal', 200)->nullable();
            foreach (self::DIAS as $dia) {
                $table->string($dia, 1)->nullable();
            }
            $table->string('hora_inicio', 5)->nullable();
            $table->string('hora_fin', 5)->nullable();
            $table->timestamps();

            $table->index('nom_prod');
            $table->index('barcode');
        });

        // Precio y disponibilidad de cada producto en cada sucursal (legacy: productosucursal)
        Schema::create('producto_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('sugerencia', 10)->nullable();
            $table->decimal('precio_venta', 13, 2)->default(0);
            $table->decimal('precio_venta_normal', 13, 2)->default(0);
            $table->decimal('precio_venta_mayorista', 13, 2)->nullable();
            $table->decimal('precio_venta3', 13, 2)->nullable();
            $table->decimal('precio_venta4', 13, 2)->nullable();
            foreach (self::DIAS as $dia) {
                $table->string($dia, 1)->nullable();
            }
            $table->string('hora_inicio', 5)->nullable();
            $table->string('hora_fin', 5)->nullable();
            $table->string('impcoc', 1)->nullable();
            $table->string('imppunt1', 1)->nullable();
            $table->string('imppunt2', 1)->nullable();
            $table->string('imppunt3', 1)->nullable();
            $table->string('imppunt4', 1)->nullable();
            $table->date('f_vence')->default('2999-12-29');
            $table->smallInteger('estado')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'sucursal_id']);
            $table->index(['sucursal_id', 'categoria_id']);
        });

        // Opciones / modificadores de un producto (legacy: opproductos_mysql)
        Schema::create('producto_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nom_prod', 70)->nullable();
            $table->string('opcion', 60);
            $table->string('estado', 10)->default('ACTIVO');
            $table->decimal('pre_venta', 12, 2)->nullable();
            $table->timestamps();
        });

        // Grupos de reporte, p. ej. conteo de presas de pollo (legacy: grupos_mysql)
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->string('cod_grupo', 10)->nullable();
            $table->string('nom_grupo', 30);
            $table->integer('factor')->default(0);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('grupo_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 3)->nullable();
            $table->decimal('unidad', 12, 3)->nullable();
            $table->decimal('factor', 10, 2)->nullable();
            $table->string('nom_prod', 70)->nullable();
            $table->decimal('cangrupo', 12, 3)->nullable();
            $table->integer('orden')->nullable();
            $table->timestamps();
        });

        // Receta: insumos que consume un producto (legacy: relproins_mysql)
        Schema::create('insumo_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumo_producto');
        Schema::dropIfExists('grupo_producto');
        Schema::dropIfExists('grupos');
        Schema::dropIfExists('producto_opciones');
        Schema::dropIfExists('producto_sucursal');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};
