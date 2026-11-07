<?php

namespace App\Support;

/**
 * Monto en letras para notas de venta y facturas: 85.5 → "OCHENTA Y CINCO 50/100 BOLIVIANOS".
 */
class AmountInWords
{
    private const UNITS = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
        'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
        'VEINTIUN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];

    private const TENS = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];

    private const HUNDREDS = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS',
        'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public static function bolivianos(float $amount): string
    {
        $cents = (int) round($amount * 100);
        $integer = intdiv($cents, 100);

        return trim(static::words($integer)).' '.sprintf('%02d', $cents % 100).'/100 BOLIVIANOS';
    }

    public static function words(int $number): string
    {
        if ($number === 0) {
            return 'CERO';
        }

        $parts = [];
        $millions = intdiv($number, 1000000);
        $thousands = intdiv($number % 1000000, 1000);
        $rest = $number % 1000;

        if ($millions > 0) {
            $parts[] = $millions === 1 ? 'UN MILLÓN' : static::hundreds($millions).' MILLONES';
        }
        if ($thousands > 0) {
            $parts[] = $thousands === 1 ? 'MIL' : static::hundreds($thousands).' MIL';
        }
        if ($rest > 0) {
            $parts[] = static::hundreds($rest);
        }

        return implode(' ', $parts);
    }

    private static function hundreds(int $number): string
    {
        if ($number === 100) {
            return 'CIEN';
        }

        $hundred = intdiv($number, 100);
        $rest = $number % 100;
        $text = self::HUNDREDS[$hundred];

        if ($rest > 0) {
            $tens = $rest < 30
                ? self::UNITS[$rest]
                : self::TENS[intdiv($rest, 10)].($rest % 10 ? ' Y '.self::UNITS[$rest % 10] : '');
            $text = trim($text.' '.$tens);
        }

        return $text;
    }
}
