<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // En la BD y no en disco: lo sube el sistema administrador y lo muestra el POS, que no comparten carpeta pública
            $table->text('logo')->nullable()->comment('Logo del restaurante como data URI (data:image/...;base64,...)');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
