<?php

namespace App\Support;

use App\Exceptions\ErrorPos;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\PuntoVenta;
use App\Models\Sucursal;
use Illuminate\Contracts\Session\Session;

/**
 * Datos de trabajo del cajero guardados en sesión al ingresar al POS
 * (sucursal, almacén, tipo de cambio, horario de turnos y orden del día).
 *
 * Reemplaza las variables $_SESSION del sistema legacy (idSucursal, camoff, HINITM...).
 */
class PosContexto
{
    private const CLAVE = 'pos';

    public function __construct(private Session $session) {}

    public function iniciar(PuntoVenta $puntoVenta, float $tipoCambio): void
    {
        $this->session->put(self::CLAVE, [
            'tipo_cambio' => $tipoCambio,
            'venta_multialmacen' => $puntoVenta->venta_multialmacen,
            'almacen_por_defecto_id' => $puntoVenta->almacen_por_defecto_id,
            'mesa_procesando_id' => null,
        ]);

        $this->cambiarSucursal($puntoVenta->sucursal, $puntoVenta->almacen_por_defecto_id);
    }

    /**
     * Cambia la sucursal de trabajo; exige que tenga dosificación registrada.
     */
    public function cambiarSucursal(Sucursal $sucursal, ?int $almacenId = null): void
    {
        $dosificacion = Dosificacion::vigenteDe($sucursal->id);

        if (! $dosificacion) {
            throw new ErrorPos('NO HAY DOSIFICACIÓN PARA ESTA SUCURSAL');
        }

        $almacenId ??= $sucursal->almacenes()->value('id');

        $this->session->put(self::CLAVE, array_merge($this->datos(), [
            'sucursal_id' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre,
            'almacen_por_defecto_id' => $almacenId,
            'hora_inicio_manana' => substr((string) $dosificacion->hinitm, 0, 5),
            'hora_fin_manana' => substr((string) $dosificacion->hfintm, 0, 5),
            'empresa' => $dosificacion->nomemp,
            'orden_dia' => Correlativo::deVentas($sucursal->id)?->ordendia ?? 0,
        ]));
    }

    public function activo(): bool
    {
        return isset($this->datos()['sucursal_id']);
    }

    public function sucursalId(): int
    {
        return (int) ($this->datos()['sucursal_id'] ?? 0);
    }

    public function sucursalNombre(): string
    {
        return (string) ($this->datos()['sucursal_nombre'] ?? '');
    }

    public function almacenId(): ?int
    {
        return $this->datos()['almacen_por_defecto_id'] ?? null;
    }

    public function ventaMultialmacen(): bool
    {
        return (bool) ($this->datos()['venta_multialmacen'] ?? false);
    }

    public function tipoCambio(): float
    {
        return (float) ($this->datos()['tipo_cambio'] ?? 0);
    }

    public function horaInicioManana(): string
    {
        return (string) ($this->datos()['hora_inicio_manana'] ?? '');
    }

    public function horaFinManana(): string
    {
        return (string) ($this->datos()['hora_fin_manana'] ?? '');
    }

    public function ordenDia(): int
    {
        return (int) ($this->datos()['orden_dia'] ?? 0);
    }

    public function setOrdenDia(int $orden): void
    {
        $this->set('orden_dia', $orden);
    }

    public function mesaProcesandoId(): ?int
    {
        return $this->datos()['mesa_procesando_id'] ?? null;
    }

    public function setMesaProcesando(?int $mesaId): void
    {
        $this->set('mesa_procesando_id', $mesaId);
    }

    public function olvidar(): void
    {
        $this->session->forget(self::CLAVE);
    }

    private function set(string $clave, mixed $valor): void
    {
        $this->session->put(self::CLAVE.'.'.$clave, $valor);
    }

    private function datos(): array
    {
        return $this->session->get(self::CLAVE, []);
    }
}
