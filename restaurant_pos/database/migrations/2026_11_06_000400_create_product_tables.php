<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('group_code', 10)->nullable();
            $table->string('group_name', 60);
            $table->integer('category_group')->default(0);
            $table->string('status', 10)->default('ACTIVE');
            $table->string('color', 15)->nullable();
            $table->string('image', 254)->nullable();
            $table->integer('order_number')->default(0);
            $table->string('image_path', 254)->nullable();
            $table->string('image_url', 254)->nullable();
            $table->string('day_code', 1)->nullable();
            $table->timestamps();
            $table->index('order_number');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->integer('product_code')->nullable();
            $table->string('product_name', 70);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('sale_price', 13, 2)->default(0);
            $table->decimal('unit_factor', 20, 3)->default(1);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('available', 2)->default('SI');
            $table->string('status', 10)->default('ACTIVE');
            $table->string('options_mode', 15)->nullable();
            $table->string('family', 20)->nullable();
            $table->string('unit', 4)->nullable();
            $table->decimal('cost_price', 13, 3)->default(0);
            $table->string('color', 40)->nullable();
            $table->string('is_new', 1)->nullable();
            $table->decimal('stock', 12, 2)->nullable();
            $table->decimal('factor', 12, 2)->nullable();
            $table->string('subtotal', 20)->nullable();
            $table->string('group_field', 20)->nullable();
            $table->string('qr_code', 20)->nullable();
            $table->string('group_code', 90)->nullable();
            $table->string('supply_code', 90)->nullable();
            $table->string('image_url', 200)->nullable();
            $table->string('barcode', 30)->nullable();
            $table->string('orders_ref', 10)->nullable();
            $table->string('is_consumption', 1)->nullable();
            $table->date('expires_on')->nullable()->default('2999-12-29');
            $table->integer('index_number')->nullable();
            $table->string('day_code', 40)->nullable();
            $table->string('timed', 1)->nullable()->comment('T = producto cobrado por tiempo (billar)');
            $table->string('time_start', 10)->nullable();
            $table->string('time_end', 10)->nullable();
            $table->string('print_kitchen', 1)->nullable()->comment('x = imprime en cocina');
            $table->string('print_station_1', 1)->nullable();
            $table->string('print_station_2', 1)->nullable();
            $table->string('print_station_3', 1)->nullable();
            $table->string('print_station_4', 1)->nullable();
            $table->string('image_path', 200)->nullable();
            $table->string('mon', 1)->nullable();
            $table->string('tue', 1)->nullable();
            $table->string('wed', 1)->nullable();
            $table->string('thu', 1)->nullable();
            $table->string('fri', 1)->nullable();
            $table->string('sat', 1)->nullable();
            $table->string('sun', 1)->nullable();
            $table->string('start_time', 5)->nullable();
            $table->string('end_time', 5)->nullable();
            $table->timestamps();
            $table->index('product_name');
            $table->index('barcode');
        });

        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('suggestion', 10)->nullable();
            $table->decimal('sale_price', 13, 2)->default(0);
            $table->decimal('regular_price', 13, 2)->default(0);
            $table->decimal('wholesale_price', 13, 2)->nullable();
            $table->string('mon', 1)->nullable();
            $table->string('tue', 1)->nullable();
            $table->string('wed', 1)->nullable();
            $table->string('thu', 1)->nullable();
            $table->string('fri', 1)->nullable();
            $table->string('sat', 1)->nullable();
            $table->string('sun', 1)->nullable();
            $table->string('start_time', 5)->nullable();
            $table->string('end_time', 5)->nullable();
            $table->string('print_kitchen', 1)->nullable();
            $table->string('print_station_1', 1)->nullable();
            $table->string('print_station_2', 1)->nullable();
            $table->string('print_station_3', 1)->nullable();
            $table->string('print_station_4', 1)->nullable();
            $table->date('expires_on')->default('2999-12-29');
            $table->smallInteger('status')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'branch_id']);
            $table->index(['branch_id', 'category_id']);
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name', 70)->nullable();
            $table->string('option_name', 60);
            $table->string('status', 10)->default('ACTIVE');
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('report_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('group_code', 10)->nullable();
            $table->string('group_name', 30);
            $table->integer('factor')->default(0);
            $table->integer('order_number')->default(0);
            $table->timestamps();
        });

        Schema::create('product_report_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('report_group_id')->constrained('report_groups')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->nullable();
            $table->decimal('unit', 12, 3)->nullable();
            $table->decimal('factor', 10, 2)->nullable();
            $table->string('product_name', 70)->nullable();
            $table->integer('order_number')->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'supply_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('product_report_group');
        Schema::dropIfExists('report_groups');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('branch_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
