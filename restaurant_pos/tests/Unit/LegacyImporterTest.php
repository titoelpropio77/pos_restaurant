<?php

namespace Tests\Unit;

use App\Support\Legacy\LegacyImporter;
use Illuminate\Database\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LegacyImporterTest extends TestCase
{
    public static function columns(): array
    {
        return [
            'mayúsculas legacy' => ['NOM_PROD', 'product_name'],
            'clave primaria' => ['ID', 'id'],
            'clave foránea camelCase' => ['idSucursal', 'branch_id'],
            'clave foránea compuesta' => ['idMotivoMovimiento', 'movement_reason_id'],
            'camelCase' => ['precioVentaNormal', 'regular_price'],
            'ya en minúsculas' => ['pre_venta', 'sale_price'],
        ];
    }

    #[DataProvider('columns')]
    public function test_translates_the_names_of_column_legacy(string $legacy, string $expected): void
    {
        $connection = $this->createStub(Connection::class);

        $this->assertSame($expected, (new LegacyImporter($connection, $connection))->nameDestination($legacy));
    }
}
