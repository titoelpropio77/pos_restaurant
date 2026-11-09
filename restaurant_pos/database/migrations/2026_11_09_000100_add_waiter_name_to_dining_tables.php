<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesero que abrió la mesa: una mesa puede abrirse sin productos y las comandas
 * posteriores necesitan saber quién la atiende.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dining_tables', function (Blueprint $table) {
            $table->string('waiter_name', 60)->nullable()->comment('Mesero que abrió la mesa');
        });
    }

    public function down(): void
    {
        Schema::table('dining_tables', function (Blueprint $table) {
            $table->dropColumn('waiter_name');
        });
    }
};
