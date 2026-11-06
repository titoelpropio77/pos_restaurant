<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de soporte de autenticación de Laravel.
 * Los usuarios del sistema viven en la tabla `usuarios` (ver migración de personal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username', 20)->unique()->comment('Código corto que queda en ventas y gastos; no se usa para ingresar');
            $table->string('email')->unique()->comment('Credencial de ingreso: identifica también al restaurante');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('position', 30)->nullable();
            $table->string('shift', 10)->nullable();
            $table->string('status', 10)->default('ACTIVE');
            $table->rememberToken();
            $table->timestamps();
        });

        // El cajero pertenece a un restaurante; el partner, a varios
        Schema::create('restaurant_user', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['restaurant_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('restaurant_user');
        Schema::dropIfExists('users');
    }
};
