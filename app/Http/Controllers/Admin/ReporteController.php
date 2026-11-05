<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Reportes\Filtros;
use App\Admin\Reportes\Registro;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Support\ExportadorCsv;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportes del panel (definidos en App\Admin\Reportes).
 */
class ReporteController extends Controller
{
    public function index(): View
    {
        return view('admin.reportes.index', ['grupos' => Registro::agrupados()]);
    }

    public function show(Request $request, string $reporte): View|Response
    {
        $definicion = Registro::obtener($reporte);
        $filtros = Filtros::desde($request);
        $filas = collect($definicion->filas($filtros))->map(fn ($fila) => (array) $fila)->values()->all();
        $totales = $definicion->totales($filas);

        if ($request->query('exportar') === 'csv') {
            $columnas = $definicion->columnas();

            return ExportadorCsv::descargar($reporte,
                array_map(fn ($c) => $c[0], $columnas),
                array_map(fn ($fila) => array_map(fn ($clave) => $this->valorPlano($fila[$clave] ?? null, $columnas[$clave][1]), array_keys($columnas)), $filas),
            );
        }

        return view('admin.reportes.show', [
            'slug' => $reporte,
            'reporte' => $definicion,
            'filtros' => $filtros,
            'filas' => $filas,
            'totales' => $totales,
            'opciones' => $this->opcionesFiltros($definicion->filtros()),
        ]);
    }

    private function opcionesFiltros(array $usados): array
    {
        return [
            'sucursal' => in_array('sucursal', $usados) ? Sucursal::orderBy('nombre')->pluck('nombre', 'id') : collect(),
            'cajero' => in_array('cajero', $usados) ? Venta::distinct()->orderBy('login')->pluck('login', 'login') : collect(),
            'formapago' => in_array('formapago', $usados) ? Venta::distinct()->orderBy('formapago')->pluck('formapago', 'formapago') : collect(),
            'categoria' => in_array('categoria', $usados) ? Categoria::orderBy('orden')->pluck('nom_grupo', 'id') : collect(),
        ];
    }

    private function valorPlano(mixed $valor, string $formato): mixed
    {
        return match (true) {
            $valor === null => '',
            $formato === 'fecha' => ($valor instanceof DateTimeInterface ? Carbon::instance($valor) : Carbon::parse($valor))->format('d/m/Y'),
            in_array($formato, ['moneda', 'numero', 'porcentaje'], true) => (float) $valor,
            default => $valor,
        };
    }
}
