<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('table_number', 10);
            $table->string('status', 10)->default('ACTIVE');
            $table->string('occupied', 1)->nullable()->comment('S = ocupada');
            $table->integer('order_number')->default(0);
            $table->string('color', 60)->nullable()->comment('Color del mesero que la atiende');
            $table->string('processing', 1)->nullable()->comment('S = bloqueada mientras se cobra');
            $table->string('deleted_flag', 1)->nullable();
            $table->string('people_count', 30)->nullable();
            $table->integer('ticket_count')->default(0)->comment('Contador de comandas enviadas');
            $table->string('customer_name', 60)->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'order_number']);
        });

        Schema::create('table_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('dining_table_id')->constrained('dining_tables')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedBigInteger('invoice_ref')->default(0);
            $table->integer('order_number')->default(0)->comment('Número de comanda de la mesa');
            $table->integer('item')->default(0);
            $table->unsignedBigInteger('product_code')->comment('Id del producto (99999 = atención billar)');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 4)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('exchange_rate', 10, 2)->default(0);
            $table->date('sale_date');
            $table->string('sale_time', 5)->nullable();
            $table->string('product_name', 70);
            $table->string('unit', 4)->nullable();
            $table->string('pending', 1)->nullable();
            $table->string('voided', 1)->nullable();
            $table->string('options_mode', 15)->nullable();
            $table->string('category_group', 30)->nullable();
            $table->string('closed', 1)->nullable()->comment('S = ya cobrado / cerrado');
            $table->string('shift', 6)->nullable();
            $table->string('family', 20)->nullable();
            $table->decimal('unit_factor', 12, 3)->default(0);
            $table->string('service_type', 20)->nullable();
            $table->integer('invoice_number')->default(0);
            $table->integer('real_invoice_number')->default(0);
            $table->decimal('factor', 10, 5)->default(0);
            $table->string('subtotal', 20)->nullable()->comment('Subtotal');
            $table->string('group_field', 20)->nullable();
            $table->string('qr_code', 20)->nullable();
            $table->string('group_code', 90)->nullable();
            $table->string('login', 20)->nullable();
            $table->string('note', 254)->nullable();
            $table->string('item_deleted', 4)->nullable()->comment('S = ítem eliminado');
            $table->string('staff_name', 60)->nullable()->comment('Mesero');
            $table->string('deleted_flag', 1)->nullable();
            $table->string('timed', 4)->nullable();
            $table->string('time_start', 11)->nullable();
            $table->string('time_end', 11)->nullable();
            $table->timestamps();
            $table->index(['dining_table_id', 'sale_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_orders');
        Schema::dropIfExists('dining_tables');
    }
};
