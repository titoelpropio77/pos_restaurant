<?php

namespace App\Http\Controllers\Pos;

use App\Exceptions\ErrorPos;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\IngresoUsuario;
use App\Models\Usuario;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
use App\Support\Restaurante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Ingreso y salida del cajero al POS. Port de loginController.php / tipoCambioController.php.
 *
 * Igual que en el sistema original, usuario y contraseña no distinguen mayúsculas.
 */
class SesionController extends Controller
{
    private const TIPO_CAMBIO_AUTORIZADO = 'tipo_cambio_autorizado';

    public function login(Request $request, PosContexto $pos): RedirectResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'tipoCambio' => ['required', 'numeric', 'gt:0'],
        ]);

        $credenciales = [
            'usuario' => mb_strtoupper(trim($datos['usuario'])),
            'password' => mb_strtoupper($datos['password']),
            'estado' => 'ACTIVO',
        ];

        if (! Auth::attempt($credenciales)) {
            return back()->with('error_login', 'COLOQUE SUS DATOS CORRECTAMENTE');
        }

        /** @var Usuario $usuario */
        $usuario = Auth::user();

        // Hasta aquí la petición era de invitado: recién ahora se conoce el restaurante
        Restaurante::usar($usuario->empresa_id);

        $tipoCambio = $this->tipoCambio($request, $usuario, (float) $datos['tipoCambio']);
        $puntoVenta = $usuario->puntoVenta()->with('sucursal')->first();

        $error = match (true) {
            Empresa::find($usuario->empresa_id)?->estado !== 'ACTIVO' => 'EL RESTAURANTE ESTÁ INACTIVO, COMUNÍQUESE CON EL ADMINISTRADOR',
            ! $puntoVenta => 'EL USUARIO NO TIENE UN PUNTO DE VENTA ASIGNADO',
            ! $puntoVenta->puede_vender, ! ($usuario->puede('pos.vender') || $usuario->esAdministrador()) => 'EL USUARIO NO ESTÁ HABILITADO PARA VENDER',
            (bool) IngresoUsuario::latest('id')->first()?->vfecha?->isAfter(today()) => 'LA FECHA NO ES LA ACTUAL, POR FAVOR COLOQUE LA FECHA CORRECTAMENTE',
            default => null,
        };

        try {
            if ($error) {
                throw new ErrorPos($error);
            }

            DB::transaction(function () use ($usuario, $tipoCambio, $pos, $puntoVenta) {
                IngresoUsuario::create([
                    'ingresos' => (int) IngresoUsuario::max('ingresos') + 1,
                    'usuario_id' => $usuario->id,
                    'usuario' => $usuario->usuario,
                    'vfecha' => today(),
                    'hora' => now()->format('G:i'),
                    'cam_of' => $tipoCambio,
                ]);

                $pos->iniciar($puntoVenta, $tipoCambio);
            });
        } catch (ErrorPos $e) {
            Auth::logout();

            return back()->with('error_login', $e->getMessage());
        }

        $request->session()->regenerate();

        return redirect()->route('pos.index');
    }

    public function logout(Request $request, PosContexto $pos): RedirectResponse
    {
        $pos->olvidar();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pos.index');
    }

    /**
     * Un administrador autoriza modificar el tipo de cambio en la pantalla de ingreso.
     * La autorización vale sólo para su restaurante y sólo para el siguiente ingreso.
     */
    public function autorizarTipoCambio(Request $request): JsonResponse
    {
        $usuario = Usuario::withoutGlobalScopes()
            ->where('usuario', mb_strtoupper(trim((string) $request->input('usuario'))))
            ->first();

        $autorizado = $usuario?->esAdministrador()
            && Hash::check(mb_strtoupper(trim((string) $request->input('password'))), $usuario->password);

        if ($autorizado) {
            $request->session()->put(self::TIPO_CAMBIO_AUTORIZADO, $usuario->empresa_id);
        }

        return RespuestaPos::ok($autorizado ? 'habilitado' : 'nohabilitado');
    }

    /**
     * La pantalla de ingreso no sabe de qué restaurante es el usuario, así que no puede mostrar
     * su tipo de cambio: se conserva el último del restaurante salvo que un administrador
     * suyo haya autorizado cambiarlo.
     */
    private function tipoCambio(Request $request, Usuario $usuario, float $enviado): float
    {
        if ($request->session()->pull(self::TIPO_CAMBIO_AUTORIZADO) === $usuario->empresa_id) {
            return $enviado;
        }

        return (float) IngresoUsuario::latest('id')->value('cam_of') ?: $enviado;
    }
}
