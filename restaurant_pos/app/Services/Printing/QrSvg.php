<?php

namespace App\Services\Printing;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Código QR en SVG (negro sobre blanco) para imprimir en la ticketera.
 */
class QrSvg
{
    public static function make(string $content, int $size = 160): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd)))->writeString($content);

        // Sin la declaración XML, para incrustarlo directo en el HTML del ticket
        return trim(preg_replace('/^<\?xml[^>]*>\s*/', '', $svg));
    }
}
