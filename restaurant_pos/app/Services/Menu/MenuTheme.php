<?php

namespace App\Services\Menu;

/**
 * Colores del menú digital a partir del fondo de la tienda (restaurants.background_color).
 *
 * El texto es oscuro o claro según cuál contraste más con el fondo (WCAG); el resto de los tonos
 * (texto secundario, tarjetas, líneas) se derivan en el CSS mezclando texto y fondo.
 */
class MenuTheme
{
    public const DEFAULT_BACKGROUND = '#FAF7F2';

    private const DARK_INK = '#1B1C22';

    private const LIGHT_INK = '#FBF8F3';

    public function __construct(
        public readonly string $background,
        public readonly string $ink,
        public readonly bool $dark,
    ) {}

    public static function from(?string $background): self
    {
        $background = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $background) ? strtoupper($background) : self::DEFAULT_BACKGROUND;

        $withDark = self::contrast($background, self::DARK_INK);
        $withLight = self::contrast($background, self::LIGHT_INK);
        $dark = $withLight > $withDark;

        return new self($background, $dark ? self::LIGHT_INK : self::DARK_INK, $dark);
    }

    public static function contrast(string $a, string $b): float
    {
        [$high, $low] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($high + 0.05) / ($low + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
