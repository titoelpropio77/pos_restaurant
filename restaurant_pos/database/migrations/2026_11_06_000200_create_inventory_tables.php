<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 30);
            $table->string('abbreviation', 30);
            $table->smallInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 100);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('status', 10)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('movement_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 50);
            $table->string('status', 10)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('status', 10)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('supply_code', 10)->nullable();
            $table->string('supply_name', 60);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->string('measure', 20)->nullable()->comment('Unidad de consumo');
            $table->string('status', 8)->default('ACTIVE');
            $table->string('product_code', 90)->nullable();
            $table->decimal('unit', 12, 3)->default(0);
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->decimal('average_cost', 12, 4)->nullable()->comment('Precio promedio ponderado');
            $table->decimal('last_purchase_price', 12, 4)->nullable();
            $table->timestamps();
            $table->index('supply_name');
        });

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('current_stock', 14, 4)->default(0);
            $table->timestamps();
            $table->unique(['supply_id', 'warehouse_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->decimal('total', 12, 4)->default(0);
            $table->string('type', 15)->nullable();
            $table->string('description', 500)->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('status', 10)->nullable();
            $table->foreignId('movement_reason_id')->nullable()->constrained('movement_reasons')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('date');
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->cascadeOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable()->index()->comment('Documento origen (venta) si aplica');
            $table->foreignId('supply_id')->constrained('supplies');
            $table->string('supply_name', 60)->nullable();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->date('movement_date');
            $table->string('details', 60)->nullable();
            $table->decimal('qty_in', 20, 4)->default(0);
            $table->decimal('qty_out', 15, 4)->default(0);
            $table->decimal('balance', 20, 4)->default(0);
            $table->string('movement_type', 10)->nullable();
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->decimal('average_cost', 12, 4)->nullable();
            $table->string('unit', 20)->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamps();
            $table->index(['supply_id', 'movement_date']);
            $table->index(['warehouse_id', 'supply_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('supplies');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('movement_reasons');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('units');
    }
};
