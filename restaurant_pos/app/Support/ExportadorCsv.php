<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga CSV que Excel abre correctamente en configuración regional española
 * (UTF-8 con BOM y separador punto y coma).
 */
class ExportadorCsv
{
    /**
     * @param  string[]  $encabezados
     * @param  iterable<array>  $filas
     */
    public static function descargar(string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $encabezados, ';');
            foreach ($filas as $fila) {
                fputcsv($salida, array_map(fn ($v) => is_float($v) ? number_format($v, 2, ',', '') : $v, array_values($fila)), ';');
            }
            fclose($salida);
        }, $nombre.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
