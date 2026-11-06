<?php

namespace Tests\Unit;

use App\Services\Facturacion\CodigoControl;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CodigoControlTest extends TestCase
{
    /**
     * Ejemplos de la especificación del Código de Control v7 de Impuestos Nacionales.
     */
    public static function casosOficiales(): array
    {
        return [
            ['29040011007', '1503', '4189179011', '20070702', '2500', '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A', '6A-DC-53-05-14'],
            ['79040011859', '152', '1026469026', '20070728', '135', 'A3Fs4s$)2cvD(eY667A5C4A2rsdf53kw9654E2B23s24df35F5', 'FB-A6-E4-78'],
        ];
    }

    #[DataProvider('casosOficiales')]
    public function test_genera_el_codigo_de_los_casos_oficiales(string $autorizacion, string $factura, string $nit, string $fecha, string $monto, string $llave, string $esperado): void
    {
        $this->assertSame($esperado, (new CodigoControl)->generar($autorizacion, $factura, $nit, $fecha, $monto, $llave));
    }

    public function test_redondea_el_monto_antes_de_calcular(): void
    {
        $codigo = new CodigoControl;
        $llave = '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A';

        $this->assertSame(
            $codigo->generar('29040011007', '1503', '4189179011', '20070702', '2500', $llave),
            $codigo->generar('29040011007', '1503', '4189179011', '20070702', '2499.5', $llave),
        );
    }

    public function test_rechaza_datos_no_numericos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CodigoControl)->generar('29040011007', 'ABC', '0', '20070702', '10', 'llave');
    }
}
