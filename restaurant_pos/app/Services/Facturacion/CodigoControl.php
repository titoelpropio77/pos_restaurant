<?php

namespace App\Services\Facturacion;

use InvalidArgumentException;

/**
 * Generador del Código de Control de facturación computarizada del SIN (Bolivia), versión 7.
 *
 * Port del legacy abms1/CodigoControl/sin/{ControlCode,AllegedRC4,Base64SIN,Verhoeff}.php.
 * Las sumas de grandes números se hacen con bcmath para evitar desbordes de enteros.
 */
class CodigoControl
{
    private const BASE64_SIN = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/';

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
    public function generar(string $autorizacion, string $factura, string $nit, string $fecha, string|float $monto, string $llave): string
    {
        $monto = (string) $monto;

        foreach (['autorización' => $autorizacion, 'factura' => $factura, 'fecha' => $fecha, 'monto' => $monto, 'llave' => $llave] as $campo => $valor) {
            if ($valor === '') {
                throw new InvalidArgumentException("El campo {$campo} es obligatorio para generar el código de control.");
            }
        }
        foreach ([$autorizacion, $factura, $fecha, $nit, $monto] as $valor) {
            if (! preg_match('/^[0-9,.]+$/', $valor)) {
                throw new InvalidArgumentException("Valor restringido a número: {$valor}");
            }
        }

        $monto = (string) (int) round((float) str_replace(',', '.', $monto), 0, PHP_ROUND_HALF_UP);

        // Paso 1: dos dígitos Verhoeff a cada dato y suma
        $factura = $this->agregarVerhoeff($factura, 2);
        $nit = $this->agregarVerhoeff($nit, 2);
        $fecha = $this->agregarVerhoeff($fecha, 2);
        $monto = $this->agregarVerhoeff($monto, 2);

        $suma = bcadd(bcadd(bcadd($factura, $nit), $fecha), $monto);
        $suma5 = $this->agregarVerhoeff($suma, 5);

        // Paso 2: los 5 dígitos Verhoeff (+1) definen cortes de la llave
        $cincoDigitos = substr($suma5, -5);
        $digitos = array_map(fn ($d) => (int) $d + 1, str_split($cincoDigitos));

        $partes = [];
        $inicio = 0;
        foreach ($digitos as $largo) {
            $partes[] = substr($llave, $inicio, $largo);
            $inicio += $largo;
        }

        $cadena = $autorizacion.$partes[0].$factura.$partes[1].$nit.$partes[2].$fecha.$partes[3].$monto.$partes[4];

        // Paso 3: cifrado AllegedRC4
        $llaveCifrado = $llave.$cincoDigitos;
        $cifrado = $this->allegedRc4($cadena, $llaveCifrado, false);

        // Paso 4: sumatorias ASCII total y parciales
        $total = 0;
        $parciales = [0, 0, 0, 0, 0];
        foreach (str_split($cifrado) as $i => $caracter) {
            $ascii = ord($caracter);
            $total += $ascii;
            $parciales[$i % 5] += $ascii;
        }

        // Paso 5: producto, división entera y Base64 SIN
        $sumaProductos = '0';
        foreach ($parciales as $i => $parcial) {
            $sumaProductos = bcadd($sumaProductos, bcdiv(bcmul((string) $total, (string) $parcial), (string) $digitos[$i], 0));
        }

        // Paso 6: AllegedRC4 con guiones
        return $this->allegedRc4($this->base64Sin($sumaProductos), $llaveCifrado, true);
    }

    private function agregarVerhoeff(string $numero, int $cantidad): string
    {
        for ($i = 0; $i < $cantidad; $i++) {
            $numero .= $this->verhoeff($numero);
        }

        return $numero;
    }

    private function verhoeff(string $numero): int
    {
        $r = 0;
        foreach (array_reverse(str_split($numero)) as $n => $digito) {
            $r = self::VERHOEFF_D[$r][self::VERHOEFF_P[($n + 1) % 8][(int) $digito]];
        }

        return self::VERHOEFF_INV[$r];
    }

    private function allegedRc4(string $mensaje, string $llave, bool $conGuiones): string
    {
        $state = range(0, 255);
        $index1 = 0;
        $index2 = 0;
        $largoLlave = strlen($llave);

        for ($i = 0; $i <= 255; $i++) {
            $index2 = (ord($llave[$index1]) + $state[$i] + $index2) % 256;
            [$state[$i], $state[$index2]] = [$state[$index2], $state[$i]];
            $index1 = ($index1 + 1) % $largoLlave;
        }

        $x = 0;
        $y = 0;
        $bytes = [];
        for ($i = 0, $largo = strlen($mensaje); $i < $largo; $i++) {
            $x = ($x + 1) % 256;
            $y = ($state[$x] + $y) % 256;
            [$state[$x], $state[$y]] = [$state[$y], $state[$x]];
            $nmen = ord($mensaje[$i]) ^ $state[($state[$x] + $state[$y]) % 256];
            $bytes[] = str_pad(strtoupper(dechex($nmen)), 2, '0', STR_PAD_LEFT);
        }

        return implode($conGuiones ? '-' : '', $bytes);
    }

    private function base64Sin(string $valor): string
    {
        $palabra = '';
        do {
            $resto = (int) bcmod($valor, '64');
            $valor = bcdiv($valor, '64', 0);
            $palabra = self::BASE64_SIN[$resto].$palabra;
        } while (bccomp($valor, '0') > 0);

        return $palabra;
    }
}
