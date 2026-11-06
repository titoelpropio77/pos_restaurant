<?php

namespace App\Services\Billing;

use InvalidArgumentException;

/**
 * Generador del Código de Control de facturación computarizada del SIN (Bolivia), versión 7.
 *
 * Port del legacy legacy/CodigoControl/sin/{ControlCode,AllegedRC4,Base64SIN,Verhoeff}.php.
 * Las sumas de grandes números se hacen con bcmath para evitar desbordes de enteros.
 */
class ControlCode
{
    private const BASE64_WITHOUT = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/';

    private const VERHOEFF_D = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    private const VERHOEFF_P = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
    ];

    private const VERHOEFF_INV = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

    /**
     * @param  string  $autorizacion  Número de autorización de la dosificación
     * @param  string  $factura  Número de factura
     * @param  string  $nit  NIT / CI del cliente (0 si no tiene)
     * @param  string  $fecha  Fecha de la transacción en formato AAAAMMDD
     * @param  string|float  $monto  Monto total de la transacción
     * @param  string  $llave  Llave de dosificación
     * @return string Código de control, p. ej. "6A-DC-53-05-14"
     */
    public function generate(string $authorization, string $invoice_ref, string $tax_id, string $date, string|float $amount, string $auth_key): string
    {
        $amount = (string) $amount;

        foreach (['autorización' => $authorization, 'invoice_ref' => $invoice_ref, 'date' => $date, 'amount' => $amount, 'auth_key' => $auth_key] as $field => $value) {
            if ($value === '') {
                throw new InvalidArgumentException("El campo {$field} es obligatorio para generar el código de control.");
            }
        }
        foreach ([$authorization, $invoice_ref, $date, $tax_id, $amount] as $value) {
            if (! preg_match('/^[0-9,.]+$/', $value)) {
                throw new InvalidArgumentException("Valor restringido a número: {$value}");
            }
        }

        $amount = (string) (int) round((float) str_replace(',', '.', $amount), 0, PHP_ROUND_HALF_UP);

        // Paso 1: dos dígitos Verhoeff a cada dato y suma
        $invoice_ref = $this->addVerhoeff($invoice_ref, 2);
        $tax_id = $this->addVerhoeff($tax_id, 2);
        $date = $this->addVerhoeff($date, 2);
        $amount = $this->addVerhoeff($amount, 2);

        $adds = bcadd(bcadd(bcadd($invoice_ref, $tax_id), $date), $amount);
        $suma5 = $this->addVerhoeff($adds, 5);

        // Paso 2: los 5 dígitos Verhoeff (+1) definen cortes de la llave
        $fiveDigits = substr($suma5, -5);
        $digits = array_map(fn ($d) => (int) $d + 1, str_split($fiveDigits));

        $parts = [];
        $start = 0;
        foreach ($digits as $length) {
            $parts[] = substr($auth_key, $start, $length);
            $start += $length;
        }

        $string = $authorization.$parts[0].$invoice_ref.$parts[1].$tax_id.$parts[2].$date.$parts[3].$amount.$parts[4];

        // Paso 3: cifrado AllegedRC4
        $keyEncrypted = $auth_key.$fiveDigits;
        $control_code = $this->allegedRc4($string, $keyEncrypted, false);

        // Paso 4: sumatorias ASCII total y parciales
        $total = 0;
        $partial = [0, 0, 0, 0, 0];
        foreach (str_split($control_code) as $i => $character) {
            $ascii = ord($character);
            $total += $ascii;
            $partial[$i % 5] += $ascii;
        }

        // Paso 5: producto, división entera y Base64 SIN
        $addsProducts = '0';
        foreach ($partial as $i => $partial) {
            $addsProducts = bcadd($addsProducts, bcdiv(bcmul((string) $total, (string) $partial), (string) $digits[$i], 0));
        }

        // Paso 6: AllegedRC4 con guiones
        return $this->allegedRc4($this->base64Without($addsProducts), $keyEncrypted, true);
    }

    private function addVerhoeff(string $number, int $quantity): string
    {
        for ($i = 0; $i < $quantity; $i++) {
            $number .= $this->verhoeff($number);
        }

        return $number;
    }

    private function verhoeff(string $number): int
    {
        $r = 0;
        foreach (array_reverse(str_split($number)) as $n => $digit) {
            $r = self::VERHOEFF_D[$r][self::VERHOEFF_P[($n + 1) % 8][(int) $digit]];
        }

        return self::VERHOEFF_INV[$r];
    }

    private function allegedRc4(string $message, string $auth_key, bool $withHyphens): string
    {
        $state = range(0, 255);
        $index1 = 0;
        $index2 = 0;
        $lengthKey = strlen($auth_key);

        for ($i = 0; $i <= 255; $i++) {
            $index2 = (ord($auth_key[$index1]) + $state[$i] + $index2) % 256;
            [$state[$i], $state[$index2]] = [$state[$index2], $state[$i]];
            $index1 = ($index1 + 1) % $lengthKey;
        }

        $x = 0;
        $y = 0;
        $bytes = [];
        for ($i = 0, $length = strlen($message); $i < $length; $i++) {
            $x = ($x + 1) % 256;
            $y = ($state[$x] + $y) % 256;
            [$state[$x], $state[$y]] = [$state[$y], $state[$x]];
            $nmen = ord($message[$i]) ^ $state[($state[$x] + $state[$y]) % 256];
            $bytes[] = str_pad(strtoupper(dechex($nmen)), 2, '0', STR_PAD_LEFT);
        }

        return implode($withHyphens ? '-' : '', $bytes);
    }

    private function base64Without(string $value): string
    {
        $word = '';
        do {
            $remainder = (int) bcmod($value, '64');
            $value = bcdiv($value, '64', 0);
            $word = self::BASE64_WITHOUT[$remainder].$word;
        } while (bccomp($value, '0') > 0);

        return $word;
    }
}
