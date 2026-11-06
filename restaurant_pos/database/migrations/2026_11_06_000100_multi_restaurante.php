<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sistema multirestaurante: `empresas` es el restaurante y todo dato de negocio le pertenece.
 *
 * Cada tabla recibe su propio empresa_id (también las que ya llegaban a él por sucursal) para que
 * el aislamiento sea uniforme y auditable: ver App\Models\Concerns\PerteneceAEmpresa.
 * Los datos existentes quedan en el primer restaurante y su sucursal más antigua pasa a ser la matriz.
 *
 * Se excluyen sólo los catálogos comunes a todo el sistema: paises, ciudades, monedas, modulos y bancos.
 */
return new class extends Migration
{
    public const TABLAS = [
        'almacenes', 'asiento_detalles', 'asientos', 'cajas', 'cargos', 'categorias', 'centros_costo', 'clientes',
        'correlativos', 'cuenta_grupos', 'cuenta_impuestos', 'cuentas_automaticas', 'cuentas_bancarias',
        'cuentas_contables', 'dosificaciones', 'empleados', 'gastos', 'gestiones', 'grupo_producto', 'grupos',
        'impuestos', 'ingresos_usuario', 'insumo_almacen', 'insumo_producto', 'insumos', 'inventarios',
        'ip_sucursales', 'kardex_insumos', 'listas_precio', 'mesa_pedidos', 'mesas', 'motivos_movimiento',
        'personal', 'printer_settings', 'producto_opciones', 'producto_sucursal', 'productos', 'proveedores',
        'punto_venta_cajas', 'punto_venta_cuentas', 'puntos_venta', 'tarjeta_detalles',
        'tigo_money_configuraciones', 'tipos_cliente', 'tipos_egreso', 'tipos_gasto_compra', 'tipos_ingreso',
        'turnos', 'unidades_medida', 'usuarios', 'venta_detalles', 'ventas',
    ];

    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('nit', 20)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 250)->nullable();
            $table->json('opciones')->nullable()->comment('Opciones del POS que reemplazan a config/posgourmet.php');
        });

        Schema::table('sucursales', function (Blueprint $table) {
            $table->boolean('es_matriz')->default(false);
        });

        $empresaId = $this->restaurantePrincipal();

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->index()->constrained('empresas')->restrictOnDelete();
            });

            if ($empresaId) {
                DB::table($tabla)->update(['empresa_id' => $empresaId]);
            }

            DB::statement("ALTER TABLE {$tabla} ALTER COLUMN empresa_id SET NOT NULL");
        }

        // Una sucursal siempre es de un restaurante, y un restaurante con historia no se borra en cascada
        if ($empresaId) {
            DB::table('sucursales')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
        }

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
        DB::statement('ALTER TABLE sucursales ALTER COLUMN empresa_id SET NOT NULL');
        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreign('empresa_id')->references('id')->on('empresas')->restrictOnDelete();
        });

        DB::statement('UPDATE sucursales SET es_matriz = true WHERE id IN (SELECT min(id) FROM sucursales GROUP BY empresa_id)');
        DB::statement('CREATE UNIQUE INDEX sucursales_una_matriz_por_empresa ON sucursales (empresa_id) WHERE es_matriz');

        $this->vistaGrupos();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vista_grupo_producto_ventas');

        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('empresa_id');
            });
        }

        DB::statement('DROP INDEX IF EXISTS sucursales_una_matriz_por_empresa');
        DB::statement('ALTER TABLE sucursales ALTER COLUMN empresa_id DROP NOT NULL');

        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('es_matriz');
        });
        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreign('empresa_id')->references('id')->on('empresas')->nullOnDelete();
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['nit', 'telefono', 'direccion', 'opciones']);
        });

        (require database_path('migrations/2026_10_29_000800_create_reporte_views.php'))->up();
    }

    /**
     * Restaurante dueño de los datos actuales; si hay datos sin empresa se crea uno.
     */
    private function restaurantePrincipal(): ?int
    {
        $empresaId = DB::table('empresas')->orderBy('id')->value('id');
        $hayDatos = collect([...self::TABLAS, 'sucursales'])->contains(fn ($t) => DB::table($t)->exists());

        if ($empresaId || ! $hayDatos) {
            return $empresaId;
        }

        return DB::table('empresas')->insertGetId([
            'nombre' => 'MI RESTAURANTE', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function vistaGrupos(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW vista_grupo_producto_ventas AS
            SELECT SUM(d.cantidad * d.unid * gp.factor) AS cantidad,
                   g.nom_grupo, g.cod_grupo, g.id AS grupo_id,
                   MAX(gp.cangrupo) AS cangrupo, MAX(gp.orden) AS orden,
                   MAX(gp.unidad) AS unidad, MAX(gp.factor) AS factor,
                   d.f_venta, d.login, d.turno, v.sucursal_id, g.empresa_id
            FROM grupo_producto gp
            JOIN grupos g ON g.id = gp.grupo_id
            JOIN venta_detalles d ON d.cod_prod = gp.producto_id
            JOIN ventas v ON v.id = d.venta_id
            WHERE COALESCE(d.anulado, '') <> 'S'
            GROUP BY g.id, g.nom_grupo, g.cod_grupo, d.f_venta, d.login, d.turno, v.sucursal_id, g.empresa_id
            UNION ALL
            SELECT SUM(t.cantidad * t.unid * gp.factor) AS cantidad,
                   g.nom_grupo, g.cod_grupo, g.id AS grupo_id,
                   MAX(gp.cangrupo), MAX(gp.orden), MAX(gp.unidad), MAX(gp.factor),
                   t.f_venta, t.login, t.turno, t.sucursal_id, g.empresa_id
            FROM grupo_producto gp
            JOIN grupos g ON g.id = gp.grupo_id
            JOIN tarjeta_detalles t ON t.cod_prod = gp.producto_id
            GROUP BY g.id, g.nom_grupo, g.cod_grupo, t.f_venta, t.login, t.turno, t.sucursal_id, g.empresa_id
        SQL);
    }
};
