<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estructura organizacional: empresa, sucursales, dosificación (facturación SIN),
 * correlativos y configuración general.
 *
 * Origen legacy: pais, ciudad, moneda, empresa, sucursal, empresa_mysql,
 * correlativos, ipsucursales, printersetting, modulo, tigomoney.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('ciudades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('pais_id')->nullable()->constrained('paises')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('monedas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
            $table->string('abreviatura', 10)->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('imagen')->nullable();
            $table->string('web')->nullable();
            $table->string('correo')->nullable();
            $table->string('propietario')->nullable();
            $table->string('actividad')->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->boolean('vender_sin_stock')->default(true);
            $table->boolean('registro_diario')->default(false);
            $table->boolean('multilista_precio')->default(false);
            $table->timestamps();
        });

        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion', 250)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('estado', 10)->default('ACTIVO');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudades')->nullOnDelete();
            $table->integer('contador')->default(0);
            $table->string('ip_impresion', 60)->nullable();
            $table->timestamps();
        });

        // Datos fiscales y de dosificación por sucursal (legacy: empresa_mysql)
        Schema::create('dosificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->integer('codemp')->nullable();
            $table->string('nomemp', 100)->nullable()->comment('Nombre de la empresa');
            $table->string('nompro', 60)->nullable()->comment('Nombre del propietario');
            $table->string('ciupai', 60)->nullable()->comment('Ciudad - País');
            $table->string('nitruc', 20)->nullable()->comment('NIT');
            $table->text('logo')->nullable();
            $table->string('dirlog', 150)->nullable();
            $table->string('moneda', 3)->nullable();
            $table->string('sucursal', 60)->nullable()->comment('Nombre de sucursal impreso en factura');
            $table->string('alfa', 30)->nullable();
            $table->string('diremp', 150)->nullable()->comment('Dirección');
            $table->integer('inifac')->nullable()->comment('Factura inicial');
            $table->integer('finfac')->nullable()->comment('Factura final');
            $table->integer('facact')->nullable()->comment('Factura actual');
            $table->integer('facactr')->nullable()->comment('Factura actual real');
            $table->string('telemp', 20)->nullable();
            $table->string('numorden', 15)->nullable();
            $table->string('tit_imposi', 10)->nullable();
            $table->date('fec_docis')->nullable()->comment('Fecha de dosificación');
            $table->string('tit_impcli', 10)->nullable();
            $table->time('hinitm')->nullable()->comment('Hora inicio turno mañana');
            $table->time('hfintm')->nullable()->comment('Hora fin turno mañana');
            $table->integer('vercod')->nullable();
            $table->integer('preview')->nullable();
            $table->integer('xp')->nullable();
            $table->integer('garzones')->nullable();
            $table->integer('blocker')->nullable();
            $table->integer('cocinacaja')->nullable();
            $table->integer('inactiva')->nullable();
            $table->integer('r1o')->nullable();
            $table->integer('r2o')->nullable();
            $table->integer('r1n')->nullable();
            $table->integer('r2n')->nullable();
            $table->string('llave', 500)->nullable()->comment('Llave de dosificación SIN');
            $table->string('nroautori', 20)->nullable()->comment('Número de autorización SIN');
            $table->date('fecha_lim')->nullable()->comment('Fecha límite de emisión');
            $table->integer('mesa')->nullable();
            $table->integer('llevar')->nullable();
            $table->string('nombase', 100)->nullable();
            $table->integer('ordendia')->nullable();
            $table->string('ip', 100)->nullable();
            $table->string('leyenda1', 300)->nullable();
            $table->string('leyenda2', 300)->nullable();
            $table->timestamps();
        });

        // Numeración de facturas / órdenes por sucursal
        Schema::create('correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 50);
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->integer('folio')->default(0)->comment('Último número de factura');
            $table->integer('folior')->default(0)->comment('Último número de factura real/recibo');
            $table->integer('ordendia')->default(0)->comment('Número de orden del día');
            $table->integer('imprimir')->nullable();
            $table->integer('multcon')->nullable();
            $table->integer('mult')->nullable();
            $table->timestamps();

            $table->unique(['tabla', 'sucursal_id']);
        });

        Schema::create('ip_sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('ip_sucursal', 30);
            $table->string('nombre_sucursal', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('printer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ipcliente', 30)->nullable();
            $table->string('ipclienteaux', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('tigo_money_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('llave_identificacion')->nullable();
            $table->string('llave_encriptacion')->nullable();
            $table->string('url_servicio_web')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tigo_money_configuraciones');
        Schema::dropIfExists('modulos');
        Schema::dropIfExists('printer_settings');
        Schema::dropIfExists('ip_sucursales');
        Schema::dropIfExists('correlativos');
        Schema::dropIfExists('dosificaciones');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('empresas');
        Schema::dropIfExists('monedas');
        Schema::dropIfExists('ciudades');
        Schema::dropIfExists('paises');
    }
};
