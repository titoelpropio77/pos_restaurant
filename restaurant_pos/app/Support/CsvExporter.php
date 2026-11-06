<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga CSV que Excel abre correctamente en configuración regional española
 * (UTF-8 con BOM y separador punto y coma).
 */
class CsvExporter
{
    /**
     * @param  string[]  $encabezados
     * @param  iterable<array>  $filas
     */
    public static function download(string $name, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $service_type = fopen('php://output', 'w');
            fwrite($service_type, "\xEF\xBB\xBF");
            fputcsv($service_type, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($service_type, array_map(fn ($v) => is_float($v) ? number_format($v, 2, ',', '') : $v, array_values($row)), ';');
            }
            fclose($service_type);
        }, $name.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
