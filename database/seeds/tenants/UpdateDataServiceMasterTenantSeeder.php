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
        foreach ($this->tables as $key => $table) {
            try {
                // Verificar si la tabla ya tiene datos antes de intentar cargarlos
                $count = DB::connection('tenant')->table($key)->count();

                if ($count > 0) {
                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Saltando $key - ya contiene $count registros");
                    }
                    continue;
                }

                // Intentar primero con LOAD DATA LOCAL INFILE
                $csvPath = str_replace(DIRECTORY_SEPARATOR, '/', public_path($this->prefix.DIRECTORY_SEPARATOR."{$key}.{$this->prefix}"));

                try {
                    DB::connection('tenant')
                        ->getpdo()
                        ->exec("LOAD DATA LOCAL INFILE '{$csvPath}' INTO TABLE $key({$table['columns']}) SET created_at = NOW(), updated_at = NOW()");

                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Tabla $key poblada correctamente con LOAD DATA");
                    }
                } catch (\Exception $loadError) {
                    // Si LOAD DATA falla, intentar método alternativo: leer CSV y hacer inserts
                    if (method_exists($this->command, 'warn')) {
                        $this->command->warn("LOAD DATA falló para $key, usando método alternativo");
                    }

                    $this->loadFromCsvFile($key, $csvPath);

                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Tabla $key poblada correctamente con método alternativo");
                    }
                }
            } catch (\Exception $e) {
                if (method_exists($this->command, 'error')) {
                    $this->command->error("Error al poblar $key: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Método alternativo para cargar datos desde CSV cuando LOAD DATA LOCAL INFILE falla
     */
    protected function loadFromCsvFile($tableName, $csvPath)
    {
        if (!file_exists($csvPath)) {
            throw new \Exception("Archivo CSV no encontrado: {$csvPath}");
        }

        $file = fopen($csvPath, 'r');
        if (!$file) {
            throw new \Exception("No se pudo abrir el archivo CSV: {$csvPath}");
        }

        $rows = [];
        $batchSize = 100;

        // Determinar las columnas según la tabla
        $columns = $this->getColumnsForTable($tableName);

        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            // Los CSV están separados por tabuladores
            $values = explode("\t", $line);

            if (count($values) !== count($columns)) {
                continue; // Saltar líneas con formato incorrecto
            }

            $row = array_combine($columns, $values);
            $row['created_at'] = now();
            $row['updated_at'] = now();

            $rows[] = $row;

            // Insertar en batch cada 100 registros
            if (count($rows) >= $batchSize) {
                DB::connection('tenant')->table($tableName)->insert($rows);
                $rows = [];
            }
        }

        // Insertar los registros restantes
        if (!empty($rows)) {
            DB::connection('tenant')->table($tableName)->insert($rows);
        }

        fclose($file);
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
