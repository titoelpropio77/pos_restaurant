<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas (facturación), detalle, consumos con tarjeta prepago, clientes y
 * movimientos de caja (gastos / ingresos).
 *
 * Origen legacy: clientes_mysql, ven_gral_mysql, detalles_mysql, dettarjeta_mysql, gastos_mysql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('cod_cli', 10)->nullable();
            $table->string('zona_cli', 3)->nullable();
            $table->string('nom_cli', 100);
            $table->string('tel_cli', 20)->nullable();
            $table->string('cel_cli', 20)->nullable();
            $table->string('nit_cli', 20)->nullable();
            $table->text('dir_cli')->nullable();
            $table->string('mail_cli', 100)->nullable();
            $table->string('ci_cli', 15)->nullable();
            $table->string('foto')->nullable();
            $table->date('fec_nac')->nullable();
            $table->timestamps();

            $table->index(['nom_cli', 'nit_cli']);
            $table->index('tel_cli');
        });

        // Cabecera de venta / factura (legacy: ven_gral_mysql)
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura')->nullable()->comment('Igual al id; también es el nro de tarjeta prepago');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->integer('orden')->default(0)->comment('Número de orden del día');
            $table->date('f_venta');
            $table->string('login', 20)->comment('Usuario que registró la venta');
            $table->string('formapago', 10)->comment('CONTADO, CREDITO, CONSUMO, TARJETA...');
            $table->string('estado', 12)->default('ACTIVO')->comment('ACTIVO / INACTIVO (anulada)');
            $table->string('eliminpor', 20)->nullable();
            $table->string('salida', 20)->nullable()->comment('MESA, LLEVAR, DOMICILIO...');
            $table->string('turno', 6)->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->integer('vendedor')->default(0)->comment('Id del mesero / vendedor');
            $table->string('dhoraven', 5)->nullable()->comment('Hora de venta');
            $table->decimal('cam_of', 10, 3)->default(0)->comment('Tipo de cambio');
            $table->decimal('total_fact', 12, 4)->default(0);
            $table->decimal('tot_deuda', 12, 4)->default(0);
            $table->string('xpagar', 1)->nullable();
            $table->string('pendiente', 1)->nullable();
            $table->text('obser')->nullable();
            $table->string('anulado', 1)->nullable()->comment('S = anulada');
            $table->string('cerrado', 1)->nullable()->comment('S = incluida en cierre de caja');
            $table->integer('fortax')->default(0)->comment('Número de factura (folio)');
            $table->integer('tot_des')->default(0)->comment('Número de factura real (folior)');
            $table->string('alfa', 30)->nullable();
            $table->string('numorden', 15)->nullable()->comment('Prepago seleccionado');
            $table->decimal('pago_us', 12, 3)->default(0);
            $table->decimal('cambio_us', 12, 3)->default(0);
            $table->decimal('pago_bs', 12, 3)->default(0);
            $table->decimal('cambio_bs', 12, 3)->default(0);
            $table->string('moneda', 2)->nullable();
            $table->string('cod_empl', 10)->nullable();
            $table->string('nit_cli', 20)->nullable();
            $table->string('ci_cli', 15)->nullable();
            $table->string('nom_cli', 100)->nullable();
            $table->string('llave', 500)->nullable()->comment('Llave de dosificación usada');
            $table->string('nroautori', 20)->nullable();
            $table->date('fecha_lim')->nullable();
            $table->string('cifrado', 50)->nullable()->comment('Código de control SIN');
            $table->decimal('pre_pago', 12, 2)->nullable()->comment('Saldo de tarjeta prepago');
            $table->string('ordenes', 10)->nullable();
            $table->string('entregado', 1)->nullable();
            $table->timestamps();

            $table->index(['sucursal_id', 'f_venta']);
            $table->index('factura');
            $table->index('login');
        });

        // Detalle de venta (legacy: detalles_mysql; FACTURA => venta_id)
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->integer('orden')->default(0);
            $table->integer('item')->default(0);
            $table->unsignedBigInteger('cod_prod')->comment('Id del producto (99999 = atención billar)');
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('pre_costo', 12, 4)->default(0);
            $table->decimal('pre_venta', 12, 2)->default(0);
            $table->decimal('cam_of', 10, 3)->default(0);
            $table->date('f_venta');
            $table->string('dhoraven', 5)->nullable();
            $table->string('nom_prod', 70);
            $table->string('unidad', 4)->nullable();
            $table->string('pendiente', 1)->nullable();
            $table->string('anulado', 1)->nullable()->comment('S = anulado');
            $table->string('presa', 15)->nullable();
            $table->string('grupo', 30)->nullable();
            $table->string('cerrado', 1)->nullable();
            $table->string('turno', 6)->nullable();
            $table->string('familia', 20)->nullable()->comment('Forma de pago de la venta');
            $table->decimal('unid', 12, 4)->default(0);
            $table->string('salida', 20)->nullable();
            $table->integer('fortax')->default(0);
            $table->integer('tot_des')->default(0);
            $table->decimal('factor', 10, 5)->default(0);
            $table->string('agrupa1', 60)->nullable()->comment('Subtotal de la línea');
            $table->string('agrupa2', 20)->nullable();
            $table->string('agrupa3', 20)->nullable()->comment('Código QR');
            $table->string('cod_grupo', 90)->nullable();
            $table->string('login', 20)->nullable();
            $table->string('ordenes', 10)->nullable();
            $table->string('nota', 254)->nullable();
            $table->string('barcode', 30)->nullable();
            $table->integer('indice')->default(0);
            $table->string('deconsumo', 1)->nullable();
            $table->timestamps();

            $table->index('f_venta');
            $table->index('cod_prod');
        });

        // Consumos cargados a una tarjeta prepago (legacy: dettarjeta_mysql; FACTURA => venta_id de la tarjeta)
        Schema::create('tarjeta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->integer('orden')->default(0);
            $table->integer('item')->default(0);
            $table->unsignedBigInteger('cod_prod');
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('pre_costo', 12, 4)->default(0);
            $table->decimal('pre_venta', 12, 2)->default(0);
            $table->decimal('cam_of', 10, 3)->default(0);
            $table->date('f_venta');
            $table->string('dhoraven', 5)->nullable();
            $table->string('nom_prod', 70);
            $table->string('unidad', 4)->nullable();
            $table->string('pendiente', 1)->nullable();
            $table->string('anulado', 1)->nullable();
            $table->string('presa', 15)->nullable();
            $table->string('grupo', 30)->nullable();
            $table->string('cerrado', 1)->nullable();
            $table->string('turno', 6)->nullable();
            $table->string('familia', 20)->nullable();
            $table->decimal('unid', 12, 4)->default(0);
            $table->string('salida', 20)->nullable();
            $table->integer('fortax')->default(0);
            $table->integer('tot_des')->default(0);
            $table->decimal('factor', 10, 5)->default(0);
            $table->string('agrupa1', 20)->nullable();
            $table->string('agrupa2', 20)->nullable();
            $table->string('agrupa3', 20)->nullable();
            $table->string('cod_grupo', 90)->nullable();
            $table->string('login', 20)->nullable();
            $table->string('ordenes', 10)->nullable();
            $table->timestamps();

            $table->index('f_venta');
        });

        // Movimientos de caja: tipotransaccion 0 = gasto/egreso, 1 = ingreso (legacy: gastos_mysql)
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->integer('nrogasto')->default(0);
            $table->date('fechagasto');
            $table->string('entregadoa', 60)->nullable();
            $table->string('concepto', 150)->nullable();
            $table->string('horagasto', 8)->nullable();
            $table->string('login', 20)->nullable();
            $table->decimal('cam_of', 10, 3)->default(0);
            $table->decimal('monto', 12, 3)->default(0);
            $table->string('turno', 6)->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->smallInteger('tipotransaccion')->default(0)->comment('0 = egreso, 1 = ingreso');
            $table->integer('nrofactura')->nullable();
            $table->date('fecha_factura')->nullable();
            $table->string('nit_proveedor', 25)->nullable();
            $table->string('nro_autorizacion', 25)->nullable();
            $table->string('codigo_control', 15)->nullable();
            $table->smallInteger('con_factura')->default(0);
            $table->decimal('tipo_cambio', 9, 2)->nullable();
            $table->smallInteger('impuesto')->default(0)->comment('1 = entra al libro de compras');
            $table->decimal('credito_fiscal', 12, 2)->nullable();
            $table->smallInteger('saliocaja')->default(1)->comment('1 = afecta el arqueo de caja');
            $table->timestamps();

            $table->index(['sucursal_id', 'fechagasto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('tarjeta_detalles');
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('clientes');
    }
};
