<?php

use Illuminate\Database\Seeder;

class UpdateDataServiceMasterTenantSeeder extends Seeder
{
    /**
     * Prefix.
     *
     * @var string
     */
    public $prefix = 'csv';

    /**
     * Tables.
     *
     * @var array
     */
    public $tables = [
        'co_type_workers' => [
            'columns' => 'id, name, code, @created_at, @updated_at'
        ],
        'co_sub_type_workers' => [
            'columns' => 'id, name, code, @created_at, @updated_at'
        ],
        'co_payroll_type_document_identifications' => [
            'columns' => 'id, name, code, @created_at, @updated_at'
        ],
        'co_type_contracts' => [
            'columns' => 'id, name, code, @created_at, @updated_at'
        ],
        'co_payroll_periods' => [
            'columns' => 'id, name, code, @created_at, @updated_at'
        ],
        'co_type_law_deductions' => [
            'columns' => 'id, name, code, percentage, @created_at, @updated_at'
        ],
        'co_type_disabilities' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_type_overtime_surcharges' => [
            'columns' => 'id, name, code, percentage, type, @created_at, @updated_at'
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        \Log::info('UpdateDataServiceMasterTenantSeeder iniciando', ['tables' => array_keys($this->tables)]);

        foreach ($this->tables as $key => $table) {
            try {
                // Verificar si la tabla ya tiene datos antes de intentar cargarlos
                $count = DB::connection('tenant')->table($key)->count();
                \Log::info("Verificando tabla $key", ['registros_existentes' => $count]);

                if ($count > 0) {
                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Saltando $key - ya contiene $count registros");
                    }
                    \Log::info("Saltando $key - ya tiene datos", ['count' => $count]);
                    continue;
                }

                // Intentar primero con LOAD DATA LOCAL INFILE
                $csvPath = str_replace(DIRECTORY_SEPARATOR, '/', public_path($this->prefix.DIRECTORY_SEPARATOR."{$key}.{$this->prefix}"));
                \Log::info("Intentando poblar $key", ['csv_path' => $csvPath, 'exists' => file_exists($csvPath)]);

                try {
                    DB::connection('tenant')
                        ->getpdo()
                        ->exec("LOAD DATA LOCAL INFILE '{$csvPath}' INTO TABLE $key({$table['columns']}) SET created_at = NOW(), updated_at = NOW()");

                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Tabla $key poblada correctamente con LOAD DATA");
                    }
                    \Log::info("$key poblada con LOAD DATA correctamente");
                } catch (\Exception $loadError) {
                    // Si LOAD DATA falla, intentar método alternativo: leer CSV y hacer inserts
                    \Log::warning("LOAD DATA falló para $key", ['error' => $loadError->getMessage()]);

                    if (method_exists($this->command, 'warn')) {
                        $this->command->warn("LOAD DATA falló para $key, usando método alternativo: " . $loadError->getMessage());
                    }

                    $this->loadFromCsvFile($key, $csvPath);

                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Tabla $key poblada correctamente con método alternativo");
                    }
                    \Log::info("$key poblada con método alternativo correctamente");
                }
            } catch (\Exception $e) {
                \Log::error("Error crítico al poblar $key", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

                if (method_exists($this->command, 'error')) {
                    $this->command->error("Error al poblar $key: " . $e->getMessage());
                }
            }
        }

        \Log::info('UpdateDataServiceMasterTenantSeeder finalizado');
    }

    /**
     * Método alternativo para cargar datos desde CSV cuando LOAD DATA LOCAL INFILE falla
     */
    protected function loadFromCsvFile($tableName, $csvPath)
    {
        \Log::info("loadFromCsvFile iniciando para $tableName", ['csv_path' => $csvPath]);

        if (!file_exists($csvPath)) {
            \Log::error("Archivo CSV no encontrado", ['csv_path' => $csvPath]);
            throw new \Exception("Archivo CSV no encontrado: {$csvPath}");
        }

        $file = fopen($csvPath, 'r');
        if (!$file) {
            \Log::error("No se pudo abrir archivo CSV", ['csv_path' => $csvPath]);
            throw new \Exception("No se pudo abrir el archivo CSV: {$csvPath}");
        }

        $rows = [];
        $batchSize = 100;
        $lineCount = 0;
        $insertedCount = 0;

        // Determinar las columnas según la tabla
        $columns = $this->getColumnsForTable($tableName);
        \Log::info("Columnas para $tableName", ['columns' => $columns]);

        while (($line = fgets($file)) !== false) {
            $lineCount++;
            $line = trim($line);
            if (empty($line)) continue;

            // Los CSV están separados por tabuladores
            $values = explode("\t", $line);

            if (count($values) !== count($columns)) {
                \Log::warning("Línea $lineCount ignorada - columnas no coinciden", [
                    'expected' => count($columns),
                    'found' => count($values),
                    'line' => substr($line, 0, 100)
                ]);
                continue; // Saltar líneas con formato incorrecto
            }

            $row = array_combine($columns, $values);
            $row['created_at'] = now();
            $row['updated_at'] = now();

            $rows[] = $row;

            // Insertar en batch cada 100 registros
            if (count($rows) >= $batchSize) {
                try {
                    DB::connection('tenant')->table($tableName)->insert($rows);
                    $insertedCount += count($rows);
                    \Log::info("Batch insertado en $tableName", ['rows' => count($rows), 'total' => $insertedCount]);
                } catch (\Exception $e) {
                    \Log::error("Error insertando batch en $tableName", ['error' => $e->getMessage()]);
                    throw $e;
                }
                $rows = [];
            }
        }

        // Insertar los registros restantes
        if (!empty($rows)) {
            try {
                DB::connection('tenant')->table($tableName)->insert($rows);
                $insertedCount += count($rows);
                \Log::info("Últimos registros insertados en $tableName", ['rows' => count($rows), 'total' => $insertedCount]);
            } catch (\Exception $e) {
                \Log::error("Error insertando últimos registros en $tableName", ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        fclose($file);
        \Log::info("loadFromCsvFile completado para $tableName", [
            'lines_processed' => $lineCount,
            'rows_inserted' => $insertedCount
        ]);
    }

    /**
     * Obtener las columnas para cada tabla
     */
    protected function getColumnsForTable($tableName)
    {
        $columnMap = [
            'co_type_workers' => ['id', 'name', 'code'],
            'co_sub_type_workers' => ['id', 'name', 'code'],
            'co_payroll_type_document_identifications' => ['id', 'name', 'code'],
            'co_type_contracts' => ['id', 'name', 'code'],
            'co_payroll_periods' => ['id', 'name', 'code'],
            'co_type_law_deductions' => ['id', 'name', 'code', 'percentage'],
            'co_type_disabilities' => ['id', 'name', 'code'],
            'co_type_overtime_surcharges' => ['id', 'name', 'code', 'percentage', 'type'],
        ];

        return $columnMap[$tableName] ?? [];
    }
}
