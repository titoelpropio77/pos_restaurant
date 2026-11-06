<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\Empresa;
use App\Models\IngresoUsuario;
use App\Models\Producto;
use App\Models\ProductoSucursal;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Venta;
use App\Support\Restaurante;
use Database\Seeders\DatosBaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lo que un restaurante no debe poder ver ni tocar de otro.
 */
class MultiRestauranteTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $principal;

    private Empresa $otro;

    private Usuario $dueñoOtro;

    private Sucursal $sucursalOtra;

    /** Tablas comunes a todos los restaurantes. Agregar aquí exige una decisión consciente. */
    private const TABLAS_GLOBALES = [
        'bancos', 'cache', 'cache_locks', 'ciudades', 'empresas', 'failed_jobs', 'job_batches', 'jobs', 'migrations',
        'modulos', 'monedas', 'paises', 'password_reset_tokens', 'sessions',
        'administradores', 'auditoria', 'permiso_rol', 'permisos', 'roles',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([DatosBaseSeeder::class, DemoSeeder::class]);
        $this->principal = Empresa::first();

        $this->otro = Empresa::create(['nombre' => 'OTRO RESTAURANTE']);
        Restaurante::usar($this->otro->id);

        $this->sucursalOtra = Sucursal::create(['nombre' => 'MATRIZ OTRO', 'estado' => 'ACTIVO']);
        $this->sucursalOtra->forceFill(['es_matriz' => true])->save();
        Dosificacion::create(['sucursal_id' => $this->sucursalOtra->id, 'nomemp' => 'OTRO', 'hinitm' => '06:00', 'hfintm' => '16:00']);
        $almacen = Almacen::create(['nombre' => 'ALMACEN OTRO', 'sucursal_id' => $this->sucursalOtra->id]);
        $categoria = Categoria::create(['nom_grupo' => 'CATEGORIA OTRO']);
        $producto = Producto::create(['nom_prod' => 'PLATO SECRETO OTRO', 'categoria_id' => $categoria->id, 'pre_venta' => 10]);
        ProductoSucursal::create(['producto_id' => $producto->id, 'sucursal_id' => $this->sucursalOtra->id, 'categoria_id' => $categoria->id]);
        Cliente::create(['nom_cli' => 'CLIENTE SECRETO OTRO']);

        $this->dueñoOtro = Usuario::create([
            'usuario' => 'dueno2', 'nombres' => 'DUEÑO DOS', 'password' => 'CLAVE',
            'rol_id' => Rol::delRestaurante()->where('nombre', 'Dueño')->value('id'),
        ]);
        PuntoVenta::create([
            'usuario_id' => $this->dueñoOtro->id, 'sucursal_id' => $this->sucursalOtra->id,
            'almacen_por_defecto_id' => $almacen->id, 'fecha_inicio' => today(),
        ]);

        Restaurante::usar($this->principal->id);
    }

    private function comoOtro(): void
    {
        $this->post('/login', ['usuario' => 'dueno2', 'password' => 'clave', 'tipoCambio' => '6.96'])
            ->assertRedirect(route('pos.index'));
    }

    public function test_toda_tabla_de_negocio_tiene_empresa_id_obligatorio(): void
    {
        $sinEmpresa = collect(DB::select(<<<'SQL'
            SELECT t.table_name FROM information_schema.tables t
            LEFT JOIN information_schema.columns c
              ON c.table_schema = t.table_schema AND c.table_name = t.table_name
             AND c.column_name = 'empresa_id' AND c.is_nullable = 'NO'
            WHERE t.table_schema = current_schema() AND t.table_type = 'BASE TABLE' AND c.column_name IS NULL
        SQL))->pluck('table_name')->diff(self::TABLAS_GLOBALES)->values()->all();

        $this->assertSame([], $sinEmpresa);
    }

    public function test_todo_modelo_con_empresa_id_esta_filtrado(): void
    {
        foreach (glob(app_path('Models/*.php')) as $archivo) {
            $clase = 'App\\Models\\'.basename($archivo, '.php');
            /** @var Model $modelo */
            $modelo = new $clase;

            if (in_array($modelo->getTable(), self::TABLAS_GLOBALES, true)) {
                continue;
            }

            $this->assertStringContainsString('empresa_id', $clase::query()->toSql(), "{$clase} no está filtrado por restaurante");
        }
    }

    public function test_sin_restaurante_en_contexto_una_peticion_no_ve_nada(): void
    {
        Restaurante::actual()->iniciarPeticion(null);

        $this->assertSame(0, Producto::count());
        $this->assertSame(0, Usuario::count());
    }

    public function test_el_panel_no_muestra_datos_de_otro_restaurante(): void
    {
        $this->actingAs(Usuario::where('usuario', 'ADMIN')->first());

        foreach ([
            route('admin.productos.index'),
            route('admin.catalogos.index', 'clientes'),
            route('admin.catalogos.index', 'categorias'),
            route('admin.catalogos.index', 'sucursales'),
            route('admin.usuarios.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('SECRETO OTRO')->assertDontSee('CATEGORIA OTRO')
                ->assertDontSee('MATRIZ OTRO')->assertDontSee('DUEÑO DOS');
        }
    }

    public function test_el_pos_del_otro_restaurante_sigue_viendo_lo_suyo(): void
    {
        $this->comoOtro();

        $this->getJson(route('pos.categorias.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJsonFragment(['nom_grupo' => 'CATEGORIA OTRO']);
        $this->get(route('admin.productos.index'))->assertOk()
            ->assertSee('PLATO SECRETO OTRO')->assertDontSee('PIQUE MACHO');
    }

    public function test_no_abre_registros_de_otro_restaurante_por_url(): void
    {
        Restaurante::usar($this->principal->id);
        $venta = Venta::first() ?? null;
        $usuario = Usuario::where('usuario', 'CAJERO')->first();
        $producto = Producto::first();

        $this->comoOtro();

        $this->get(route('admin.usuarios.edit', $usuario))->assertNotFound();
        $this->get(route('admin.productos.edit', $producto))->assertNotFound();
        $this->get(route('admin.facturacion.edit', Sucursal::withoutGlobalScopes()->where('nombre', 'CASA MATRIZ')->first()))->assertNotFound();
        $this->get(route('admin.catalogos.edit', ['clientes', Cliente::withoutGlobalScopes()->where('nom_cli', '<>', 'CLIENTE SECRETO OTRO')->value('id') ?? 0]))->assertNotFound();

        if ($venta) {
            $this->get(route('admin.ventas.show', $venta))->assertNotFound();
        }
    }

    public function test_no_acepta_referencias_a_datos_de_otro_restaurante(): void
    {
        $this->comoOtro();

        $this->post(route('admin.productos.store'), [
            'nom_prod' => 'NUEVO', 'categoria_id' => Categoria::withoutGlobalScopes()->where('empresa_id', $this->principal->id)->value('id'),
            'pre_venta' => 5, 'estado' => 'ACTIVO',
        ])->assertSessionHasErrors('categoria_id');
    }

    public function test_el_nombre_de_producto_se_repite_entre_restaurantes_pero_no_dentro_de_uno(): void
    {
        $this->comoOtro();
        $categoria = Categoria::withoutGlobalScopes()->where('empresa_id', $this->otro->id)->value('id');

        $datos = ['categoria_id' => $categoria, 'pre_venta' => 5, 'cantidad' => 1, 'unid' => 1, 'estado' => 'ACTIVO', 'tipo' => 'P'];

        $this->post(route('admin.productos.store'), $datos + ['nom_prod' => 'PIQUE MACHO'])->assertSessionDoesntHaveErrors('nom_prod');
        $this->post(route('admin.productos.store'), $datos + ['nom_prod' => 'PLATO SECRETO OTRO'])->assertSessionHasErrors('nom_prod');
    }

    public function test_lo_creado_queda_en_el_restaurante_del_usuario(): void
    {
        $this->comoOtro();

        $this->post(route('admin.catalogos.store', 'categorias'), ['nom_grupo' => 'NUEVA DEL OTRO', 'estado' => 'ACTIVO', 'orden' => 1]);

        $categoria = Categoria::withoutGlobalScopes()->where('nom_grupo', 'NUEVA DEL OTRO')->first();

        $this->assertNotNull($categoria);
        $this->assertSame($this->otro->id, $categoria->empresa_id);
    }

    public function test_un_restaurante_inactivo_no_ingresa_y_pierde_la_sesion_abierta(): void
    {
        $this->comoOtro();
        $this->get(route('admin.dashboard'))->assertOk();

        $this->otro->update(['estado' => 'INACTIVO']);

        $this->get(route('admin.dashboard'))->assertRedirect(route('pos.index'));
        $this->assertGuest();

        $this->post('/login', ['usuario' => 'dueno2', 'password' => 'clave', 'tipoCambio' => '6.96'])
            ->assertSessionHas('error_login');
        $this->assertGuest();
    }

    public function test_cada_restaurante_conserva_su_tipo_de_cambio(): void
    {
        Restaurante::usar($this->otro->id);
        IngresoUsuario::create(['usuario' => 'DUENO2', 'vfecha' => today(), 'cam_of' => 7.50]);
        Restaurante::usar($this->principal->id);

        // Sin autorización no cambia, aunque el formulario envíe otro valor
        $this->post('/login', ['usuario' => 'dueno2', 'password' => 'clave', 'tipoCambio' => '6.96']);
        $this->assertDatabaseHas('ingresos_usuario', ['usuario' => 'DUENO2', 'cam_of' => 7.50, 'empresa_id' => $this->otro->id]);
        $this->post('/logout');

        // Autorizado por un administrador de otro restaurante tampoco
        $this->postJson(route('pos.tipo-cambio.autorizar'), ['usuario' => 'admin', 'password' => 'admin']);
        $this->post('/login', ['usuario' => 'dueno2', 'password' => 'clave', 'tipoCambio' => '8']);
        $this->assertDatabaseMissing('ingresos_usuario', ['usuario' => 'DUENO2', 'cam_of' => 8]);
        $this->post('/logout');

        // Autorizado por el suyo, sí
        $this->postJson(route('pos.tipo-cambio.autorizar'), ['usuario' => 'dueno2', 'password' => 'clave']);
        $this->post('/login', ['usuario' => 'dueno2', 'password' => 'clave', 'tipoCambio' => '8']);
        $this->assertDatabaseHas('ingresos_usuario', ['usuario' => 'DUENO2', 'cam_of' => 8]);
    }

    public function test_un_restaurante_tiene_una_sola_casa_matriz(): void
    {
        $this->expectException(QueryException::class);

        Restaurante::usar($this->otro->id);
        Sucursal::create(['nombre' => 'OTRA'])->forceFill(['es_matriz' => true])->save();
    }

    public function test_la_casa_matriz_no_se_da_de_baja(): void
    {
        $this->actingAs(Usuario::where('usuario', 'ADMIN')->first());
        $matriz = Sucursal::where('es_matriz', true)->first();

        $this->delete(route('admin.catalogos.destroy', ['sucursales', $matriz->id]))->assertSessionHas('error');

        $this->assertSame('ACTIVO', $matriz->fresh()->estado);
    }

    public function test_el_rol_limita_las_secciones_del_panel(): void
    {
        $encargado = Usuario::create([
            'usuario' => 'encargado', 'nombres' => 'E', 'password' => 'CLAVE',
            'rol_id' => Rol::delRestaurante()->where('nombre', 'Encargado')->value('id'),
        ]);

        $this->actingAs($encargado->fresh());

        $this->get(route('admin.productos.index'))->assertOk();
        $this->get(route('admin.usuarios.index'))->assertForbidden();
        $this->get(route('admin.facturacion.index'))->assertForbidden();
    }

    public function test_una_venta_queda_en_su_restaurante_con_numeracion_propia(): void
    {
        Restaurante::usar($this->otro->id);
        Correlativo::create(['tabla' => 'ventas', 'sucursal_id' => $this->sucursalOtra->id, 'folio' => 0, 'folior' => 0, 'ordendia' => 0]);
        $dosificacion = Dosificacion::where('sucursal_id', $this->sucursalOtra->id)->first();
        $dosificacion->update(['nroautori' => '29040011007', 'llave' => '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A', 'fecha_lim' => today()->addYear()->toDateString(), 'inifac' => 1]);
        $producto = Producto::where('nom_prod', 'PLATO SECRETO OTRO')->first();
        Restaurante::usar($this->principal->id);

        $this->comoOtro();

        $respuesta = $this->postJson(route('pos.ventas.store'), [
            'idProducto' => [$producto->id], 'cantidad' => [2], 'precio' => [10], 'subTotal' => [20], 'nota' => [''], 'qr' => [''],
            'nombreProduc' => ['PLATO SECRETO OTRO'], 'nombreCli' => 'SIN NOMBRE', 'nitCli' => '', 'totalBs' => 20, 'totalFacturaBs' => 20,
            'cambioBs' => 0, 'formaPago' => 'CONTADO', 'turno' => 'NOCHE', 'salidaProducto' => 'LLEVAR', 'fechaVenta' => today()->toDateString(),
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJson(['error' => ''])->json();

        $this->assertSame(1, $respuesta['result2']);
        $this->assertDatabaseHas('ventas', ['id' => $respuesta['result'], 'empresa_id' => $this->otro->id, 'sucursal_id' => $this->sucursalOtra->id]);
        $this->assertDatabaseHas('venta_detalles', ['venta_id' => $respuesta['result'], 'empresa_id' => $this->otro->id]);
        $this->assertSame(0, Correlativo::withoutGlobalScopes()->where('sucursal_id', Sucursal::withoutGlobalScopes()->where('nombre', 'CASA MATRIZ')->value('id'))->value('ordendia'));

        // El reporte agrupado por vista SQL tampoco cruza restaurantes
        $this->assertSame(0, DB::table('vista_grupo_producto_ventas')->where('empresa_id', $this->principal->id)->count());
    }

    public function test_un_rol_sin_permiso_de_venta_no_vende(): void
    {
        $rol = Rol::create(['nombre' => 'Solo consulta', 'ambito' => 'RESTAURANTE']);
        $this->dueñoOtro->forceFill(['rol_id' => $rol->id])->save();

        $this->assertFalse($this->dueñoOtro->fresh()->puede('pos.vender'));
        $this->post('/login', ['usuario' => 'dueno2', 'password' => 'clave', 'tipoCambio' => '6.96'])->assertSessionHas('error_login');
        $this->assertGuest();
    }
}
