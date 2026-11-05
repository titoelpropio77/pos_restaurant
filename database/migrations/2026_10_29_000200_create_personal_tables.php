<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usuarios del sistema, bitácora de ingresos y personal (meseros / empleados).
 *
 * Origen legacy: usuarios_mysql, inguser_mysql, empleados_mysql, empleado, cargo, turno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->integer('coduser')->default(0);
            $table->string('nombres', 100);
            $table->string('cargo', 30)->nullable();
            $table->string('usuario', 20)->unique()->comment('Login, siempre en mayúsculas');
            $table->string('password');
            $table->string('estado', 10)->default('ACTIVO');
            $table->unsignedSmallInteger('nivel')->default(2)->comment('1 = administrador, 2 = cajero');
            $table->string('turno', 10)->nullable();
            $table->string('horain', 5)->nullable();
            $table->string('horasal', 5)->nullable();
            $table->string('manprods', 1)->nullable()->comment('Puede mantener productos');
            $table->string('maninven', 1)->nullable()->comment('Puede mantener inventario');
            $table->rememberToken();
            $table->timestamps();
        });

        // Bitácora de ingresos al POS con el tipo de cambio vigente
        Schema::create('ingresos_usuario', function (Blueprint $table) {
            $table->id();
            $table->integer('ingresos')->default(0);
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('usuario', 20);
            $table->date('vfecha');
            $table->string('hora', 8)->nullable();
            $table->decimal('cam_of', 9, 2)->default(0)->comment('Tipo de cambio oficial');
            $table->timestamps();

            $table->index('vfecha');
        });

        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->integer('minutos_tolerancia')->default(0);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 1)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('doc_identidad', 20)->nullable();
            $table->string('correo_electronico')->nullable();
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->nullOnDelete();
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->string('estado', 10)->default('ACTIVO');
            $table->decimal('comision', 9, 2)->default(0);
            $table->timestamps();
        });

        // Personal operativo del POS: meseros, cocineros, etc. (legacy: empleados_mysql)
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('cod_empl', 10)->nullable();
            $table->string('nom_empl', 60);
            $table->string('dir_empl', 100)->nullable();
            $table->string('ci_empl', 15)->nullable();
            $table->string('cargo', 30)->nullable();
            $table->decimal('sueld_empl', 10, 2)->default(0);
            $table->string('estado', 10)->default('ACTIVO');
            $table->integer('porcentaje')->default(0)->comment('% de comisión sobre venta');
            $table->string('coloremp', 60)->nullable();
            $table->string('codigo', 10)->nullable()->comment('Clave del mesero para tomar pedidos');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->timestamps();

            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('turnos');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('ingresos_usuario');
        Schema::dropIfExists('usuarios');
    }
};
