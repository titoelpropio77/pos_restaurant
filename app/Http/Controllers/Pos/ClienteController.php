<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\RespuestaPos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Búsqueda y mantenimiento rápido de clientes desde el POS. Port de clienteController y auxController.
 */
class ClienteController extends Controller
{
    /**
     * Sugerencias para el autocompletado por nombre (formato del plugin jquery.autocomplete).
     */
    public function sugerenciasNombre(Request $request): JsonResponse
    {
        $sugerencias = $this->buscarPor('nom_cli', (string) $request->query('term'), 50)
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'label' => $c->nom_cli,
                'value' => $c->nom_cli.'-'.$c->nit_cli,
                'number' => $c->nit_cli ?? '',
                'CEL_CLI' => $c->cel_cli ?? '',
            ]);

        return response()->json(['suggestions' => $sugerencias]);
    }

    public function sugerenciasNit(Request $request): JsonResponse
    {
        $sugerencias = $this->buscarPor('nit_cli', (string) $request->query('term'), 50)
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'label' => $c->nit_cli,
                'value' => $c->nit_cli.'-'.$c->nom_cli,
                'cliente' => $c->nom_cli,
                'CEL_CLI' => $c->cel_cli ?? '',
            ]);

        return response()->json(['suggestions' => $sugerencias]);
    }

    /**
     * Sugerencias para el envío a domicilio, por teléfono, nombre o NIT.
     */
    public function sugerenciasDomicilio(Request $request): JsonResponse
    {
        $columna = match ($request->query('campo')) {
            'nombre' => 'nom_cli',
            'nit' => 'nit_cli',
            default => 'tel_cli',
        };

        $sugerencias = $this->buscarPor($columna, (string) $request->query('term'), 50)
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'label' => match ($columna) {
                    'tel_cli' => $c->tel_cli,
                    'nit_cli' => $c->nom_cli.' - '.$c->nit_cli,
                    default => $c->nom_cli,
                },
                'value' => $columna === 'nit_cli' ? $c->nit_cli : $c->nom_cli.'-'.$c->tel_cli,
                'nomCli' => $c->nom_cli,
                'nitCli' => $c->nit_cli ?? '',
                'TEL_CLI' => $c->tel_cli ?? '',
                'DIRECCION' => $c->dir_cli ?? '',
            ]);

        return response()->json(['suggestions' => $sugerencias]);
    }

    public function buscarNombre(Request $request): JsonResponse
    {
        return RespuestaPos::ok(RespuestaPos::mayusculas($this->buscarPor('nom_cli', (string) $request->input('nombre'))));
    }

    public function buscarNit(Request $request): JsonResponse
    {
        return RespuestaPos::ok(RespuestaPos::mayusculas($this->buscarPor('nit_cli', (string) $request->input('nit'))));
    }

    public function update(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'idCliente' => ['required', 'integer', 'exists:clientes,id'],
            'nomCli' => ['required', 'string', 'max:100'],
            'nitCli' => ['nullable', 'string', 'max:20'],
            'dirCliA' => ['nullable', 'string'],
            'telCli' => ['nullable', 'string', 'max:20'],
            'celCli' => ['nullable', 'string', 'max:20'],
        ]);

        Cliente::whereKey($datos['idCliente'])->update([
            'nom_cli' => $datos['nomCli'],
            'nit_cli' => $datos['nitCli'] ?? '',
            'dir_cli' => $datos['dirCliA'] ?? '',
            'tel_cli' => $datos['telCli'] ?? '',
            'cel_cli' => $datos['celCli'] ?? '',
        ]);

        return RespuestaPos::ok();
    }

    public function destroy(Request $request): JsonResponse
    {
        $cliente = Cliente::findOrFail($request->integer('idCliente'));

        if ($cliente->ventas()->exists()) {
            return RespuestaPos::error('EL CLIENTE TIENE VENTAS REGISTRADAS, NO SE PUEDE ELIMINAR');
        }

        $cliente->delete();

        return RespuestaPos::ok();
    }

    private function buscarPor(string $columna, string $texto, int $limite = 1000)
    {
        return Cliente::where($columna, 'ilike', '%'.trim($texto).'%')
            ->orderBy('nom_cli')
            ->limit($limite)
            ->get();
    }
}
