<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Códigos QR en SVG (vectorial: se imprime nítido en cualquier tamaño).
 */
class QrCode
{
    /**
     * Corrección de errores media: el código se sigue leyendo aunque el afiche se manche o arrugue un poco.
     */
    public static function svg(string $text, int $size = 320, int $margin = 1): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd));

        return $writer->writeString($text, Encoder::DEFAULT_BYTE_MODE_ECODING, ErrorCorrectionLevel::M());
    }

    /** Sin la declaración XML, para insertarlo dentro de una página HTML. */
    public static function inlineSvg(string $text, int $size = 320, int $margin = 1): string
    {
        return preg_replace('/^<\?xml[^>]*>\s*/', '', self::svg($text, $size, $margin));
    }
}
