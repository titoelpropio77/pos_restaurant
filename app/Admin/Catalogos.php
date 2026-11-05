<?php

namespace App\Admin;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Models\Correlativo;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\Insumo;
use App\Models\Mesa;
use App\Models\MotivoMovimiento;
use App\Models\Personal;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Definición declarativa de los catálogos simples del panel (CatalogoController).
 *
 * Cada catálogo indica:
 *  - modelo, titulo, singular, icono, descripcion
 *  - buscar:    columnas para la búsqueda de texto
 *  - orden:     [columna, dirección]
 *  - sucursal:  true si se filtra por sucursal (columna sucursal_id)
 *  - columnas:  columnas del listado [campo => [etiqueta, formato, valor?]]
 *               formatos: texto, estado, color, moneda, numero
 *  - campos:    campos del formulario [campo => [etiqueta, tipo, reglas, opciones?, ancho?, ayuda?]]
 *  - eliminar:  'borrar' o 'estado:VALOR' (baja lógica)
 *  - consulta:  ajuste de la consulta del listado
 *  - despues:   acción luego de guardar (fn (Model $modelo, bool $nuevo))
 */
class Catalogos
{
    public static function todos(): array
    {
        $estados = ['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo'];
        $sucursales = fn () => Sucursal::orderBy('nombre')->pluck('nombre', 'id')->all();

        return [
            'categorias' => [
                'modelo' => Categoria::class,
                'titulo' => 'Categorías del menú',
                'singular' => 'categoría',
                'icono' => 'fa-th-large',
                'descripcion' => 'Botones del menú del POS. El orden define la posición en pantalla.',
                'buscar' => ['nom_grupo'],
                'orden' => ['orden', 'asc'],
                'columnas' => [
                    'nom_grupo' => ['Nombre', 'texto'],
                    'colores' => ['Color', 'color'],
                    'orden' => ['Orden', 'numero'],
                    'productos_count' => ['Productos', 'numero'],
                    'estado' => ['Estado', 'estado'],
                ],
                'consulta' => fn (Builder $q) => $q->withCount('productos'),
                'campos' => [
                    'nom_grupo' => ['Nombre', 'text', 'required|string|max:60', 'ancho' => 'sm:col-span-2'],
                    'colores' => ['Color del botón', 'color', 'nullable|string|max:15'],
                    'orden' => ['Orden', 'number', 'nullable|integer|min:0'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
                'despues' => fn (Model $m) => $m->updateQuietly(['cod_grupo' => (string) $m->id, 'grupo' => $m->id]),
            ],

            'grupos' => [
                'modelo' => Grupo::class,
                'titulo' => 'Grupos de reporte',
                'singular' => 'grupo',
                'icono' => 'fa-object-group',
                'descripcion' => 'Agrupan productos para contar unidades en los reportes (p. ej. presas de pollo vendidas).',
                'buscar' => ['nom_grupo', 'cod_grupo'],
                'orden' => ['orden', 'asc'],
                'columnas' => [
                    'cod_grupo' => ['Código', 'texto'],
                    'nom_grupo' => ['Nombre', 'texto'],
                    'orden' => ['Orden', 'numero'],
                    'productos_count' => ['Productos', 'numero'],
                ],
                'consulta' => fn (Builder $q) => $q->withCount('productos'),
                'campos' => [
                    'cod_grupo' => ['Código', 'text', 'nullable|string|max:10'],
                    'nom_grupo' => ['Nombre', 'text', 'required|string|max:30'],
                    'orden' => ['Orden', 'number', 'nullable|integer|min:0'],
                ],
                'eliminar' => 'borrar',
            ],

            'clientes' => [
                'modelo' => Cliente::class,
                'titulo' => 'Clientes',
                'singular' => 'cliente',
                'icono' => 'fa-address-book-o',
                'descripcion' => 'Se registran automáticamente al facturar; aquí puede corregir sus datos.',
                'buscar' => ['nom_cli', 'nit_cli', 'tel_cli', 'cel_cli'],
                'orden' => ['nom_cli', 'asc'],
                'columnas' => [
                    'nom_cli' => ['Nombre / razón social', 'texto'],
                    'nit_cli' => ['NIT / CI', 'texto'],
                    'tel_cli' => ['Teléfono', 'texto'],
                    'cel_cli' => ['Celular', 'texto'],
                    'ventas_count' => ['Compras', 'numero'],
                ],
                'consulta' => fn (Builder $q) => $q->withCount('ventas'),
                'campos' => [
                    'nom_cli' => ['Nombre / razón social', 'text', 'required|string|max:100', 'ancho' => 'sm:col-span-2'],
                    'nit_cli' => ['NIT', 'text', 'nullable|regex:/^[0-9]*$/|max:20'],
                    'ci_cli' => ['CI', 'text', 'nullable|string|max:15'],
                    'tel_cli' => ['Teléfono', 'text', 'nullable|string|max:20'],
                    'cel_cli' => ['Celular', 'text', 'nullable|string|max:20'],
                    'mail_cli' => ['Correo', 'email', 'nullable|email|max:100'],
                    'fec_nac' => ['Fecha de nacimiento', 'date', 'nullable|date'],
                    'dir_cli' => ['Dirección', 'textarea', 'nullable|string|max:500', 'ancho' => 'sm:col-span-4'],
                ],
                'eliminar' => 'borrar',
            ],

            'mesas' => [
                'modelo' => Mesa::class,
                'titulo' => 'Mesas',
                'singular' => 'mesa',
                'icono' => 'fa-square-o',
                'descripcion' => 'Mesas del salón de cada sucursal, en el orden en que aparecen en el POS.',
                'buscar' => ['nro_mesa'],
                'orden' => ['orden', 'asc'],
                'sucursal' => true,
                'columnas' => [
                    'nro_mesa' => ['Mesa', 'texto'],
                    'sucursal' => ['Sucursal', 'texto', fn ($m) => $m->sucursal?->nombre],
                    'orden' => ['Orden', 'numero'],
                    'uso' => ['Situación', 'texto', fn ($m) => $m->estaOcupada() ? 'OCUPADA' : 'LIBRE'],
                    'estado' => ['Estado', 'estado'],
                ],
                'consulta' => fn (Builder $q) => $q->with('sucursal')->where('estado', '<>', 'ELIMINADO'),
                'campos' => [
                    'nro_mesa' => ['Número / nombre', 'text', 'required|string|max:10'],
                    'sucursal_id' => ['Sucursal', 'select', 'required|exists:sucursales,id', $sucursales],
                    'orden' => ['Orden', 'number', 'nullable|integer|min:0'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:ELIMINADO',
            ],

            'personal' => [
                'modelo' => Personal::class,
                'titulo' => 'Meseros y personal',
                'singular' => 'persona',
                'icono' => 'fa-id-badge',
                'descripcion' => 'Personal que atiende mesas. El color identifica las mesas de cada mesero y el código se pide al abrir una mesa.',
                'buscar' => ['nom_empl', 'ci_empl', 'cargo'],
                'orden' => ['nom_empl', 'asc'],
                'sucursal' => true,
                'columnas' => [
                    'nom_empl' => ['Nombre', 'texto'],
                    'cargo' => ['Cargo', 'texto'],
                    'coloremp' => ['Color', 'color'],
                    'sucursal' => ['Sucursal', 'texto', fn ($m) => $m->sucursal?->nombre ?? 'Todas'],
                    'estado' => ['Estado', 'estado'],
                ],
                'consulta' => fn (Builder $q) => $q->with('sucursal'),
                'campos' => [
                    'nom_empl' => ['Nombre completo', 'text', 'required|string|max:60', 'ancho' => 'sm:col-span-2'],
                    'cargo' => ['Cargo', 'text', 'nullable|string|max:30'],
                    'ci_empl' => ['CI', 'text', 'nullable|string|max:15'],
                    'dir_empl' => ['Dirección', 'text', 'nullable|string|max:100', 'ancho' => 'sm:col-span-2'],
                    'sueld_empl' => ['Sueldo (Bs)', 'decimal', 'nullable|numeric|min:0'],
                    'porcentaje' => ['Comisión (%)', 'number', 'nullable|integer|min:0|max:100'],
                    'coloremp' => ['Color', 'color', 'nullable|string|max:60'],
                    'codigo' => ['Código para abrir mesa', 'text', 'nullable|string|max:10'],
                    'sucursal_id' => ['Sucursal', 'select', 'nullable|exists:sucursales,id', $sucursales, 'vacio' => 'Todas las sucursales'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
            ],

            'insumos' => [
                'modelo' => Insumo::class,
                'titulo' => 'Insumos',
                'singular' => 'insumo',
                'icono' => 'fa-lemon-o',
                'descripcion' => 'Materia prima que descuentan las recetas de los productos. El stock se gestiona en Movimientos de inventario.',
                'buscar' => ['nom_insumo', 'cod_ins'],
                'orden' => ['nom_insumo', 'asc'],
                'columnas' => [
                    'nom_insumo' => ['Insumo', 'texto'],
                    'medida' => ['Unidad', 'texto'],
                    'stock_total' => ['Stock total', 'numero'],
                    'stock_min' => ['Stock mínimo', 'numero'],
                    'ppm' => ['Costo prom. (Bs)', 'moneda', fn ($m) => $m->ppm ?? $m->pre_costo],
                    'estado' => ['Estado', 'estado'],
                ],
                'consulta' => fn (Builder $q) => $q->withSum('stocks as stock_total', 'stock_actual'),
                'campos' => [
                    'nom_insumo' => ['Nombre', 'text', 'required|string|max:60', 'ancho' => 'sm:col-span-2'],
                    'medida' => ['Unidad de medida', 'select', 'required|string|max:20', fn () => UnidadMedida::orderBy('nombre')->pluck('nombre', 'abreviatura')->all()],
                    'stock_min' => ['Stock mínimo', 'decimal', 'nullable|numeric|min:0'],
                    'pre_costo' => ['Costo unitario de referencia (Bs)', 'decimal', 'nullable|numeric|min:0'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
                'despues' => fn (Model $m) => $m->updateQuietly(['cod_ins' => (string) $m->id]),
            ],

            'almacenes' => [
                'modelo' => Almacen::class,
                'titulo' => 'Almacenes',
                'singular' => 'almacén',
                'icono' => 'fa-archive',
                'buscar' => ['nombre'],
                'orden' => ['nombre', 'asc'],
                'sucursal' => true,
                'columnas' => [
                    'nombre' => ['Almacén', 'texto'],
                    'sucursal' => ['Sucursal', 'texto', fn ($m) => $m->sucursal?->nombre],
                    'estado' => ['Estado', 'estado'],
                ],
                'consulta' => fn (Builder $q) => $q->with('sucursal'),
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100', 'ancho' => 'sm:col-span-2'],
                    'sucursal_id' => ['Sucursal', 'select', 'required|exists:sucursales,id', $sucursales],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
            ],

            'proveedores' => [
                'modelo' => Proveedor::class,
                'titulo' => 'Proveedores',
                'singular' => 'proveedor',
                'icono' => 'fa-truck',
                'buscar' => ['nombre', 'contacto_ref', 'telefono'],
                'orden' => ['nombre', 'asc'],
                'columnas' => [
                    'nombre' => ['Proveedor', 'texto'],
                    'telefono' => ['Teléfono', 'texto'],
                    'contacto_ref' => ['Contacto', 'texto'],
                    'telefono_contacto' => ['Tel. contacto', 'texto'],
                    'estado' => ['Estado', 'estado'],
                ],
                'campos' => [
                    'nombre' => ['Nombre / razón social', 'text', 'required|string|max:150', 'ancho' => 'sm:col-span-2'],
                    'telefono' => ['Teléfono', 'text', 'nullable|string|max:30'],
                    'ciudad_id' => ['Ciudad', 'select', 'nullable|exists:ciudades,id', fn () => Ciudad::orderBy('nombre')->pluck('nombre', 'id')->all(), 'vacio' => '—'],
                    'direccion' => ['Dirección', 'text', 'nullable|string|max:255', 'ancho' => 'sm:col-span-2'],
                    'contacto_ref' => ['Persona de contacto', 'text', 'nullable|string|max:100'],
                    'telefono_contacto' => ['Teléfono de contacto', 'text', 'nullable|string|max:30'],
                    'correo_contacto' => ['Correo', 'email', 'nullable|email|max:255'],
                    'pagina_web' => ['Página web', 'text', 'nullable|string|max:255'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
            ],

            'unidades' => [
                'modelo' => UnidadMedida::class,
                'titulo' => 'Unidades de medida',
                'singular' => 'unidad',
                'icono' => 'fa-balance-scale',
                'buscar' => ['nombre', 'abreviatura'],
                'orden' => ['nombre', 'asc'],
                'columnas' => [
                    'nombre' => ['Unidad', 'texto'],
                    'abreviatura' => ['Abreviatura', 'texto'],
                    'estado' => ['Estado', 'estado'],
                ],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:30'],
                    'abreviatura' => ['Abreviatura', 'text', 'required|string|max:30'],
                    'estado' => ['Estado', 'select', 'required|in:0,1', ['1' => 'Activo', '0' => 'Inactivo']],
                ],
                'eliminar' => 'estado:0',
            ],

            'motivos' => [
                'modelo' => MotivoMovimiento::class,
                'titulo' => 'Motivos de movimiento',
                'singular' => 'motivo',
                'icono' => 'fa-tags',
                'descripcion' => 'Clasifican los movimientos de inventario (compra, merma, traspaso...). VENTA lo usa el sistema al descontar recetas.',
                'buscar' => ['nombre'],
                'orden' => ['nombre', 'asc'],
                'columnas' => [
                    'nombre' => ['Motivo', 'texto'],
                    'estado' => ['Estado', 'estado'],
                ],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:50'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
            ],

            'sucursales' => [
                'modelo' => Sucursal::class,
                'titulo' => 'Sucursales',
                'singular' => 'sucursal',
                'icono' => 'fa-building-o',
                'descripcion' => 'Locales del restaurante. Cada sucursal necesita una dosificación (Facturación SIN) para poder vender.',
                'buscar' => ['nombre', 'direccion'],
                'orden' => ['nombre', 'asc'],
                'columnas' => [
                    'nombre' => ['Sucursal', 'texto'],
                    'direccion' => ['Dirección', 'texto'],
                    'telefono' => ['Teléfono', 'texto'],
                    'ip_impresion' => ['IP de impresión', 'texto'],
                    'estado' => ['Estado', 'estado'],
                ],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100', 'ancho' => 'sm:col-span-2'],
                    'telefono' => ['Teléfono', 'text', 'nullable|string|max:20'],
                    'ciudad_id' => ['Ciudad', 'select', 'nullable|exists:ciudades,id', fn () => Ciudad::orderBy('nombre')->pluck('nombre', 'id')->all(), 'vacio' => '—'],
                    'direccion' => ['Dirección', 'text', 'nullable|string|max:250', 'ancho' => 'sm:col-span-2'],
                    'ip_impresion' => ['IP del agente de impresión', 'text', 'nullable|string|max:60', 'ayuda' => 'Equipo de la caja que imprime facturas y comandas (puerto 8080).'],
                    'estado' => ['Estado', 'select', 'required|in:ACTIVO,INACTIVO', $estados],
                ],
                'eliminar' => 'estado:INACTIVO',
                'despues' => function (Model $m, bool $nuevo) {
                    if ($nuevo) {
                        $m->updateQuietly(['empresa_id' => Empresa::value('id')]);
                        Correlativo::firstOrCreate(['tabla' => config('posgourmet.correlativo_ventas'), 'sucursal_id' => $m->id]);
                    }
                },
            ],
        ];
    }

    public static function obtener(string $slug): array
    {
        $catalogos = static::todos();
        abort_unless(isset($catalogos[$slug]), 404);

        return $catalogos[$slug] + ['slug' => $slug];
    }
}
