<?php

namespace App\Http\Controllers\Pos;

use App\Exceptions\ErrorPos;
use App\Http\Controllers\Controller;
use App\Models\IngresoUsuario;
use App\Models\Usuario;
use App\Support\PosContexto;
use App\Support\RespuestaPos;
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
        $puntoVenta = $usuario->puntoVenta()->with('sucursal')->first();

        $error = match (true) {
            ! $puntoVenta => 'EL USUARIO NO TIENE UN PUNTO DE VENTA ASIGNADO',
            ! $puntoVenta->puede_vender => 'EL USUARIO NO ESTÁ HABILITADO PARA VENDER',
            (bool) IngresoUsuario::latest('id')->first()?->vfecha?->isAfter(today()) => 'LA FECHA NO ES LA ACTUAL, POR FAVOR COLOQUE LA FECHA CORRECTAMENTE',
            default => null,
        };

        try {
            if ($error) {
                throw new ErrorPos($error);
            }

            DB::transaction(function () use ($usuario, $datos, $pos, $puntoVenta) {
                IngresoUsuario::create([
                    'ingresos' => (int) IngresoUsuario::max('ingresos') + 1,
                    'usuario_id' => $usuario->id,
                    'usuario' => $usuario->usuario,
                    'vfecha' => today(),
                    'hora' => now()->format('G:i'),
                    'cam_of' => $datos['tipoCambio'],
                ]);

                $pos->iniciar($puntoVenta, (float) $datos['tipoCambio']);
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
     * Un administrador (nivel 1) autoriza modificar el tipo de cambio en la pantalla de ingreso.
     */
    public function autorizarTipoCambio(Request $request): JsonResponse
    {
        $usuario = Usuario::where('usuario', mb_strtoupper(trim((string) $request->input('usuario'))))
            ->where('nivel', Usuario::NIVEL_ADMIN)
            ->first();

        $autorizado = $usuario && Hash::check(mb_strtoupper(trim((string) $request->input('password'))), $usuario->password);

        return RespuestaPos::ok($autorizado ? 'habilitado' : 'nohabilitado');
    }
}
