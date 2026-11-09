<?php

namespace App\Support;

/**
 * URL de un archivo de public/ con ?v=<fecha de modificación>, para que el navegador
 * descargue la versión nueva cada vez que el archivo cambia.
 */
class VersionedAsset
{
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
