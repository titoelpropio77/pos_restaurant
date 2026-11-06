<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('status', 10)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('image')->nullable();
            $table->string('web')->nullable();
            $table->string('email')->nullable();
            $table->string('activity')->nullable();
            $table->string('status', 10)->default('ACTIVE');
            $table->string('tax_id', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 250)->nullable();
            $table->json('options')->nullable()->comment('Opciones del POS que reemplazan a config/pos.php');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
