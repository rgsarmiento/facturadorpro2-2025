<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDatabaseIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-indexes {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica los índices en las tablas críticas para performance del dashboard';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $table = $this->argument('table');

        $tables_to_check = [
            'documents_pos' => [
                'idx_documents_pos_establishment_currency_date',
                'PRIMARY'
            ],
            'documents' => [
                'environment_prefix_number_unique',
                'PRIMARY'
            ],
            'document_payments' => [
                'PRIMARY'
            ]
        ];

        if ($table && !isset($tables_to_check[$table])) {
            $this->error("Tabla '{$table}' no está en la lista de tablas críticas");
            $this->info('Tablas disponibles: ' . implode(', ', array_keys($tables_to_check)));
            return 1;
        }

        $tables = $table ? [$table => $tables_to_check[$table]] : $tables_to_check;

        foreach ($tables as $table_name => $required_indexes) {
            $this->info("\n📋 Verificando tabla: {$table_name}");

            try {
                $indexes = DB::select("SHOW INDEX FROM {$table_name}");

                if (empty($indexes)) {
                    $this->warn("  ⚠️  No hay índices en la tabla");
                    continue;
                }

                $index_names = collect($indexes)->pluck('Key_name')->unique();

                $this->line("  Índices encontrados:");
                foreach ($index_names as $index_name) {
                    $index_data = collect($indexes)->where('Key_name', $index_name)->toArray();
                    $columns = collect($index_data)->pluck('Column_name')->implode(', ');

                    if (in_array($index_name, $required_indexes)) {
                        $this->line("    ✅ {$index_name} ({$columns})");
                    } else {
                        $this->line("    ℹ️  {$index_name} ({$columns})");
                    }
                }

                // Verificar si faltan índices requeridos
                $missing = array_diff($required_indexes, $index_names->toArray());
                if (!empty($missing)) {
                    $this->warn("  ⚠️  Faltan índices requeridos: " . implode(', ', $missing));
                }

                // Estadísticas de la tabla
                $stats = DB::select("SELECT
                    TABLE_ROWS as row_count,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                    FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = SCHEMA() AND TABLE_NAME = '{$table_name}'");

                if (!empty($stats)) {
                    $row_count = $stats[0]->row_count ?? 0;
                    $size_mb = $stats[0]->size_mb ?? 0;
                    $this->info("  📊 Registros: " . number_format($row_count) . ", Tamaño: {$size_mb}MB");
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
            }
        }

        $this->info("\n✨ Verificación completada");
        return 0;
    }
}
