<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles y permisos de las dos aplicaciones, y cuentas del sistema administrador.
 *
 * ambito SISTEMA: roles de `administradores` (app administrador).
 * ambito RESTAURANTE: roles de `usuarios` (app restaurant_pos), comunes a todos los restaurantes.
 * Los registros `protegido` son los que el código consulta: no se pueden eliminar.
 */
return new class extends Migration
{
    private const PERMISOS = [
        // clave, nombre, módulo, ámbito
        ['restaurantes.ver', 'Ver restaurantes y sucursales', 'Restaurantes', 'SISTEMA'],
        ['restaurantes.gestionar', 'Crear y editar restaurantes y sucursales', 'Restaurantes', 'SISTEMA'],
        ['usuarios.ver', 'Ver usuarios de restaurantes', 'Usuarios', 'SISTEMA'],
        ['usuarios.gestionar', 'Crear y editar usuarios de restaurantes', 'Usuarios', 'SISTEMA'],
        ['administradores.gestionar', 'Administrar cuentas del sistema', 'Seguridad', 'SISTEMA'],
        ['roles.gestionar', 'Administrar roles y permisos', 'Seguridad', 'SISTEMA'],
        ['catalogos.gestionar', 'Administrar catálogos generales', 'Catálogos', 'SISTEMA'],
        ['auditoria.ver', 'Ver la bitácora de auditoría', 'Seguridad', 'SISTEMA'],

        ['pos.vender', 'Vender en el punto de venta', 'Punto de venta', 'RESTAURANTE'],
        ['panel.acceder', 'Entrar al panel del restaurante', 'Panel', 'RESTAURANTE'],
        ['panel.reportes', 'Ver reportes', 'Panel', 'RESTAURANTE'],
        ['panel.productos', 'Administrar productos', 'Panel', 'RESTAURANTE'],
        ['panel.catalogos', 'Administrar catálogos, mesas, personal y sucursales', 'Panel', 'RESTAURANTE'],
        ['panel.inventario', 'Administrar inventario', 'Panel', 'RESTAURANTE'],
        ['panel.ventas', 'Ver y anular ventas', 'Panel', 'RESTAURANTE'],
        ['panel.usuarios', 'Administrar usuarios del restaurante', 'Panel', 'RESTAURANTE'],
        ['panel.facturacion', 'Administrar la facturación', 'Panel', 'RESTAURANTE'],
    ];

    private const ROLES = [
        // nombre, descripción, ámbito, permisos ('*' = todos los del ámbito)
        ['Superadministrador', 'Control total del sistema', 'SISTEMA', '*'],
        ['Soporte', 'Consulta restaurantes y atiende usuarios', 'SISTEMA', ['restaurantes.ver', 'usuarios.ver', 'usuarios.gestionar']],
        ['Dueño', 'Control total de su restaurante', 'RESTAURANTE', '*'],
        ['Encargado', 'Opera el restaurante sin tocar usuarios ni facturación', 'RESTAURANTE', [
            'pos.vender', 'panel.acceder', 'panel.reportes', 'panel.productos', 'panel.catalogos', 'panel.inventario', 'panel.ventas',
        ]],
        ['Cajero', 'Vende en el punto de venta', 'RESTAURANTE', ['pos.vender']],
    ];

    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('nombre', 120);
            $table->string('modulo', 60);
            $table->string('ambito', 15);
            $table->boolean('protegido')->default(false);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('descripcion', 200)->nullable();
            $table->string('ambito', 15);
            $table->boolean('protegido')->default(false);
            $table->timestamps();
            $table->unique(['nombre', 'ambito']);
        });

        Schema::create('permiso_rol', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->primary(['rol_id', 'permiso_id']);
        });

        Schema::create('administradores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('correo', 150)->unique();
            $table->string('password');
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestamp('ultimo_ingreso')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrador_id')->nullable()->constrained('administradores')->nullOnDelete();
            $table->string('accion', 30);
            $table->string('entidad', 40);
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('descripcion', 250);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entidad', 'entidad_id']);
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->constrained('roles')->nullOnDelete();
        });

        $this->sembrar();
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
        });

        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('administradores');
        Schema::dropIfExists('permiso_rol');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permisos');
    }

    private function sembrar(): void
    {
        $ahora = now();
        $ids = [];

        foreach (self::PERMISOS as [$clave, $nombre, $modulo, $ambito]) {
            $ids[$ambito][$clave] = DB::table('permisos')->insertGetId([
                'clave' => $clave, 'nombre' => $nombre, 'modulo' => $modulo, 'ambito' => $ambito,
                'protegido' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        foreach (self::ROLES as [$nombre, $descripcion, $ambito, $permisos]) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => $nombre, 'descripcion' => $descripcion, 'ambito' => $ambito,
                'protegido' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);

            $claves = $permisos === '*' ? array_keys($ids[$ambito]) : $permisos;

            DB::table('permiso_rol')->insert(array_map(
                fn ($clave) => ['rol_id' => $rolId, 'permiso_id' => $ids[$ambito][$clave]],
                $claves,
            ));
        }

        // Los usuarios existentes conservan su acceso actual
        $rol = fn (string $nombre) => DB::table('roles')->where('nombre', $nombre)->where('ambito', 'RESTAURANTE')->value('id');
        DB::table('usuarios')->where('nivel', 1)->update(['rol_id' => $rol('Dueño')]);
        DB::table('usuarios')->where('nivel', '<>', 1)->update(['rol_id' => $rol('Cajero')]);
    }
};
