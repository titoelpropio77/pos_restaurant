<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Menú digital con QR: cada sucursal tiene un link público propio y los productos una descripción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('menu_token', 16)->nullable()->unique()->comment('Identificador del link público del menú (/menu/{token})');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('description', 300)->nullable()->comment('Se muestra en el menú digital');
        });

        foreach (DB::table('branches')->whereNull('menu_token')->pluck('id') as $id) {
            DB::table('branches')->where('id', $id)->update(['menu_token' => Str::random(12)]);
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('menu_token');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
