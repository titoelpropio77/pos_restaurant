<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale');
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->decimal('opening_amount', 12, 2);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable()->comment('Efectivo calculado al cerrar');
            $table->decimal('counted_cash', 12, 2)->nullable()->comment('Efectivo contado por quien cierra');
            $table->decimal('difference', 12, 2)->nullable()->comment('Contado menos esperado');
            $table->string('notes', 500)->nullable();
            $table->string('status', 10)->default('OPEN')->comment('OPEN o CLOSED');
            $table->timestamps();
            $table->index(['branch_id', 'status']);
        });

        // Cada punto de venta tiene una sola caja abierta a la vez
        DB::statement("CREATE UNIQUE INDEX cash_sessions_one_open ON cash_sessions (point_of_sale_id) WHERE status = 'OPEN'");

        foreach (['sales', 'expenses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['sales', 'expenses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('cash_session_id');
            });
        }

        Schema::dropIfExists('cash_sessions');
    }
};
