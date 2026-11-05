<?php

namespace Tests\Unit;

use App\Support\Legacy\ImportadorLegacy;
use Illuminate\Database\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImportadorLegacyTest extends TestCase
{
    public static function columnas(): array
    {
        return [
            'mayúsculas legacy' => ['NOM_PROD', 'nom_prod'],
            'clave primaria' => ['ID', 'id'],
            'clave foránea camelCase' => ['idSucursal', 'sucursal_id'],
            'clave foránea compuesta' => ['idMotivoMovimiento', 'motivo_movimiento_id'],
            'camelCase' => ['precioVentaNormal', 'precio_venta_normal'],
            'ya en minúsculas' => ['pre_venta', 'pre_venta'],
        ];
    }

    #[DataProvider('columnas')]
    public function test_traduce_los_nombres_de_columna_legacy(string $legacy, string $esperado): void
    {
        $conexion = $this->createStub(Connection::class);

        $this->assertSame($esperado, (new ImportadorLegacy($conexion, $conexion))->nombreDestino($legacy));
    }
}
