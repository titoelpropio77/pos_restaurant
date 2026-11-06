<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_of_sale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('multi_branch_sales')->default(false)->comment('Permite cambiar de sucursal desde el POS');
            $table->foreignId('default_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->boolean('can_sell')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'restaurant_id']);
        });

        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->integer('login_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username', 20);
            $table->date('login_date');
            $table->string('time', 8)->nullable();
            $table->decimal('exchange_rate', 9, 2)->default(0)->comment('Tipo de cambio oficial');
            $table->timestamps();
            $table->index('login_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logins');
        Schema::dropIfExists('points_of_sale');
    }
};
