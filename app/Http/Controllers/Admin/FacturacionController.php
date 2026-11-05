<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Datos fiscales, dosificación SIN y numeración de facturas por sucursal.
 * Legacy: frmDocificacion.php / docificacionController.
 */
class FacturacionController extends Controller
{
    public function index(): View
    {
        return view('admin.facturacion.index', [
            'sucursales' => Sucursal::orderBy('nombre')->get()->map(fn (Sucursal $s) => [
                'sucursal' => $s,
                'dosificacion' => $d = Dosificacion::vigenteDe($s->id),
                'dias' => $d?->diasParaVencer(today()),
                'correlativo' => Correlativo::deVentas($s->id),
            ]),
        ]);
    }

    public function edit(Sucursal $sucursal): View
    {
        return view('admin.facturacion.form', [
            'sucursal' => $sucursal,
            'dosificacion' => Dosificacion::vigenteDe($sucursal->id) ?? new Dosificacion(['hinitm' => '06:00', 'hfintm' => '16:00', 'sucursal' => $sucursal->nombre]),
            'correlativo' => Correlativo::deVentas($sucursal->id) ?? new Correlativo,
            'historial' => Dosificacion::where('sucursal_id', $sucursal->id)->latest('id')->get(),
        ]);
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $datos = $request->validate([
            'nomemp' => ['required', 'string', 'max:100'],
            'nitruc' => ['required', 'regex:/^[0-9]+$/', 'max:20'],
            'nompro' => ['nullable', 'string', 'max:60'],
            'sucursal' => ['nullable', 'string', 'max:60'],
            'diremp' => ['nullable', 'string', 'max:150'],
            'telemp' => ['nullable', 'string', 'max:20'],
            'ciupai' => ['nullable', 'string', 'max:60'],
            'nroautori' => ['required', 'regex:/^[0-9]+$/', 'max:20'],
            'llave' => ['required', 'string', 'max:500', 'regex:/^[A-Za-z0-9=#()*+\-_@\[\]{}%$]+$/'],
            'fec_docis' => ['nullable', 'date'],
            'fecha_lim' => ['required', 'date'],
            'inifac' => ['nullable', 'integer', 'min:0'],
            'hinitm' => ['required', 'date_format:H:i'],
            'hfintm' => ['required', 'date_format:H:i', 'after:hinitm'],
            'leyenda1' => ['nullable', 'string', 'max:300'],
            'leyenda2' => ['nullable', 'string', 'max:300'],
            'nueva' => ['boolean'],
            'folio' => ['required', 'integer', 'min:0'],
            'folior' => ['required', 'integer', 'min:0'],
            'mult' => ['nullable', 'integer', 'min:0'],
        ], [
            'llave.regex' => 'La llave de dosificación contiene caracteres no permitidos.',
            'hfintm.after' => 'La hora de fin del turno mañana debe ser posterior a la de inicio.',
        ], [
            'nomemp' => 'razón social', 'nitruc' => 'NIT', 'nroautori' => 'número de autorización',
            'llave' => 'llave de dosificación', 'fecha_lim' => 'fecha límite de emisión',
            'hinitm' => 'inicio del turno mañana', 'hfintm' => 'fin del turno mañana',
        ]);

        DB::transaction(function () use ($request, $sucursal, $datos) {
            $campos = collect($datos)->except(['nueva', 'folio', 'folior', 'mult'])->all();
            $actual = Dosificacion::vigenteDe($sucursal->id);

            // Una nueva dosificación se registra aparte para conservar el historial
            if (! $actual || $request->boolean('nueva')) {
                Dosificacion::create($campos + ['sucursal_id' => $sucursal->id, 'facact' => $datos['folio'], 'facactr' => $datos['folior']]);
            } else {
                $actual->update($campos);
            }

            Correlativo::updateOrCreate(
                ['tabla' => config('posgourmet.correlativo_ventas'), 'sucursal_id' => $sucursal->id],
                ['folio' => $datos['folio'], 'folior' => $datos['folior'], 'mult' => ($datos['mult'] ?? null) ?: null],
            );
        });

        return redirect()->route('admin.facturacion.index')->with('ok', "Facturación de {$sucursal->nombre} actualizada.");
    }
}
