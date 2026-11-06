<?php

namespace Tests\Unit;

use App\Services\Billing\ControlCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ControlCodeTest extends TestCase
{
    /**
     * Ejemplos de la especificación del Código de Control v7 de Impuestos Nacionales.
     */
    public static function casesOfficial(): array
    {
        return [
            ['29040011007', '1503', '4189179011', '20070702', '2500', '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A', '6A-DC-53-05-14'],
            ['79040011859', '152', '1026469026', '20070728', '135', 'A3Fs4s$)2cvD(eY667A5C4A2rsdf53kw9654E2B23s24df35F5', 'FB-A6-E4-78'],
        ];
    }

    #[DataProvider('casesOfficial')]
    public function test_generates_the_code_of_the_cases_official(string $authorization, string $invoice_ref, string $tax_id, string $date, string $amount, string $auth_key, string $expected): void
    {
        $this->assertSame($expected, (new ControlCode)->generate($authorization, $invoice_ref, $tax_id, $date, $amount, $auth_key));
    }

    public function test_rounds_the_amount_before_of_calculate(): void
    {
        $code = new ControlCode;
        $auth_key = '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A';

        $this->assertSame(
            $code->generate('29040011007', '1503', '4189179011', '20070702', '2500', $auth_key),
            $code->generate('29040011007', '1503', '4189179011', '20070702', '2499.5', $auth_key),
        );
    }

    public function test_rejects_data_not_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ControlCode)->generate('29040011007', 'ABC', '0', '20070702', '10', 'auth_key');
    }
}
