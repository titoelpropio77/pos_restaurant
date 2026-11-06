<?php

namespace App\Console\Commands;

use App\Support\Legacy\ImportadorLegacy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Migra los datos de la base MySQL del sistema original (conexión "legacy") a PostgreSQL.
 */
class ImportarLegacy extends Command
{
    protected $signature = 'legacy:importar
        {--tabla=* : Importar sólo estas tablas destino (por defecto todas)}
        {--vaciar : Vacía las tablas destino antes de importar (afecta a TODOS los restaurantes)}';

    protected $description = 'Importa los datos del POS Gourmet original (MySQL) al nuevo esquema PostgreSQL';

    public function handle(): int
    {
        try {
            DB::connection('legacy')->getPdo();
        } catch (Throwable $e) {
            $this->error('No se pudo conectar a la base legacy: '.$e->getMessage());
            $this->line('Revise las variables LEGACY_DB_* o levante el contenedor: docker compose --profile legacy up -d legacy-db');

            return self::FAILURE;
        }

        if ($this->option('vaciar') && ! $this->confirm('Se borrarán los datos de TODOS los restaurantes en las tablas destino. ¿Continuar?', ! $this->input->isInteractive())) {
            return self::FAILURE;
        }

        $importador = new ImportadorLegacy(
            DB::connection('legacy'),
            DB::connection(),
            fn (string $mensaje) => $this->line("  {$mensaje}"),
        );

        $this->info('Importando desde '.DB::connection('legacy')->getDatabaseName().'...');

        try {
            $resumen = DB::transaction(fn () => $importador->importar($this->option('tabla'), $this->option('vaciar')));
        } catch (Throwable $e) {
            $this->error('La importación se canceló y no se guardó ningún cambio: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Tabla', 'Leídas', 'Importadas', 'Omitidas'],
            collect($resumen)->map(fn ($r, $tabla) => [$tabla, $r['leidas'], $r['importadas'], $r['omitidas']])->values(),
        );

        foreach ($resumen as $tabla => $r) {
            foreach ($r['avisos'] as $aviso) {
                $this->warn("[{$tabla}] {$aviso}");
            }
        }

        $this->info('Importación finalizada.');

        return self::SUCCESS;
    }
}
