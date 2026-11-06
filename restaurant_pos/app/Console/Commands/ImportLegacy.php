<?php

namespace App\Console\Commands;

use App\Support\Legacy\LegacyImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Migra los datos de la base MySQL del sistema original (conexión "legacy") a PostgreSQL.
 */
class ImportLegacy extends Command
{
    protected $signature = 'legacy:import
        {--table_name=* : Import sólo estas tables destination (por default all)}
        {--truncate : Vacía las tables destination before de import (afecta a ALL los restaurants)}';

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

        if ($this->option('truncate') && ! $this->confirm('Se borrarán los datos de TODOS los restaurantes en las tablas destino. ¿Continuar?', ! $this->input->isInteractive())) {
            return self::FAILURE;
        }

        $importer = new LegacyImporter(
            DB::connection('legacy'),
            DB::connection(),
            fn (string $message) => $this->line("  {$message}"),
        );

        $this->info('Importando desde '.DB::connection('legacy')->getDatabaseName().'...');

        try {
            $summary = DB::transaction(fn () => $importer->import($this->option('table_name'), $this->option('truncate')));
        } catch (Throwable $e) {
            $this->error('La importación se canceló y no se guardó ningún cambio: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Tabla', 'Leídas', 'Importadas', 'Omitidas'],
            collect($summary)->map(fn ($r, $table_name) => [$table_name, $r['read'], $r['imported'], $r['skipped']])->values(),
        );

        foreach ($summary as $table_name => $r) {
            foreach ($r['warnings'] as $warning) {
                $this->warn("[{$table_name}] {$warning}");
            }
        }

        $this->info('Importación finalizada.');

        return self::SUCCESS;
    }
}
