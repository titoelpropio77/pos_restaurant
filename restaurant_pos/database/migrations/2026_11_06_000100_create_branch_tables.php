<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('address', 250)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('status', 10)->default('ACTIVE');
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->integer('counter')->default(0);
            $table->string('print_ip', 60)->nullable();
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });

        Schema::create('billing_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('business_name', 100)->nullable()->comment('Nombre de la empresa');
            $table->string('owner_name', 60)->nullable()->comment('Nombre del propietario');
            $table->string('city_country', 60)->nullable()->comment('Ciudad - País');
            $table->string('tax_id', 20)->nullable()->comment('NIT');
            $table->text('logo')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('branch', 60)->nullable()->comment('Nombre de sucursal impreso en factura');
            $table->string('business_address', 150)->nullable()->comment('Dirección');
            $table->integer('first_invoice_number')->nullable()->comment('Factura inicial');
            $table->integer('current_invoice')->nullable()->comment('Factura actual');
            $table->integer('current_real_invoice')->nullable()->comment('Factura actual real');
            $table->string('business_phone', 20)->nullable();
            $table->string('prepaid_ref', 15)->nullable();
            $table->date('authorization_date')->nullable()->comment('Fecha de dosificación');
            $table->time('morning_start')->nullable()->comment('Hora inicio turno mañana');
            $table->time('morning_end')->nullable()->comment('Hora fin turno mañana');
            $table->integer('inactive')->nullable();
            $table->string('auth_key', 500)->nullable()->comment('Llave de dosificación SIN');
            $table->string('authorization_number', 20)->nullable()->comment('Número de autorización SIN');
            $table->date('limit_date')->nullable()->comment('Fecha límite de emisión');
            $table->integer('diningTable')->nullable();
            $table->integer('takeaway')->nullable();
            $table->integer('daily_order')->nullable();
            $table->string('ip', 100)->nullable();
            $table->string('legend_1', 300)->nullable();
            $table->string('legend_2', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('table_name', 50);
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->integer('folio')->default(0)->comment('Último número de factura');
            $table->integer('real_folio')->default(0)->comment('Último número de factura real/recibo');
            $table->integer('daily_order')->default(0)->comment('Número de orden del día');
            $table->integer('print_flag')->nullable();
            $table->integer('receipt_counter')->nullable();
            $table->integer('receipt_multiplier')->nullable();
            $table->timestamps();
            $table->unique(['table_name', 'branch_id']);
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('staff_code', 10)->nullable();
            $table->string('staff_name', 60);
            $table->string('staff_address', 100)->nullable();
            $table->string('staff_id_number', 15)->nullable();
            $table->string('position', 30)->nullable();
            $table->decimal('salary', 10, 2)->default(0);
            $table->string('status', 10)->default('ACTIVE');
            $table->integer('commission_rate')->default(0)->comment('% de comisión sobre venta');
            $table->string('staff_color', 60)->nullable();
            $table->string('code', 10)->nullable()->comment('Clave del mesero para tomar pedidos');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
            $table->index('code');
        });
        DB::statement('CREATE UNIQUE INDEX branches_one_main_per_restaurant ON branches (restaurant_id) WHERE is_main');
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('invoice_sequences');
        Schema::dropIfExists('billing_authorizations');
        Schema::dropIfExists('branches');
    }
};
