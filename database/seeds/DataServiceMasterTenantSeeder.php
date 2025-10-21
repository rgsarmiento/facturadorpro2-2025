<?php

use Illuminate\Database\Seeder;

class DataServiceMasterTenantSeeder extends Seeder
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
        'co_service_type_organizations' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_countries' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_departments' => [
            'columns' => 'id, country_id, name, code, @created_at, @updated_at',
        ],
        'co_service_municipalities' => [
            'columns' => 'id, department_id, name, code, @created_at, @updated_at',
        ],
        'co_service_type_document_identifications' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_taxes' => [
            'columns' => 'id, name, description, code, @created_at, @updated_at',
        ],
        'co_service_type_regimes' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_type_liabilities' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_type_currencies' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_type_operations' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_type_environments' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_service_languages' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_payment_forms' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
        'co_payment_methods' => [
            'columns' => 'id, name, code, @created_at, @updated_at',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        foreach ($this->tables as $key => $table) {
            // Verificar si la tabla ya tiene datos
            $count = DB::connection('tenant')->table($key)->count();
            if ($count > 0) {
                if (method_exists($this->command, 'info')) {
                    $this->command->info("Saltando $key - ya contiene $count registros");
                }
                continue;
            }

            try {
                $csvPath = str_replace(DIRECTORY_SEPARATOR, '/', public_path($this->prefix.DIRECTORY_SEPARATOR."{$key}.{$this->prefix}"));

                try {
                    DB::connection('tenant')
                        ->getpdo()
                        ->exec("LOAD DATA LOCAL INFILE '{$csvPath}' INTO TABLE $key({$table['columns']}) SET created_at = NOW(), updated_at = NOW()");

                    if (method_exists($this->command, 'info')) {
                        $this->command->info("Tabla $key poblada correctamente con LOAD DATA");
                    }
                } catch (\Exception $loadError) {
                    // Si LOAD DATA falla, usar método alternativo
                    if (method_exists($this->command, 'warn')) {
                        $this->command->warn("LOAD DATA falló para $key, usando método alternativo: " . $loadError->getMessage());
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
     * Método alternativo para cargar datos desde CSV
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
        $columns = $this->getColumnsForTable($tableName);

        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            $values = explode("\t", $line);

            if (count($values) !== count($columns)) {
                continue;
            }

            $row = array_combine($columns, $values);
            $row['created_at'] = now();
            $row['updated_at'] = now();

            $rows[] = $row;

            if (count($rows) >= $batchSize) {
                DB::connection('tenant')->table($tableName)->insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            DB::connection('tenant')->table($tableName)->insert($rows);
        }

        fclose($file);
    }

    /**
     * Obtener columnas para cada tabla
     */
    protected function getColumnsForTable($tableName)
    {
        $columnMap = [
            'co_service_type_organizations' => ['id', 'name', 'code'],
            'co_service_countries' => ['id', 'name', 'code'],
            'co_service_departments' => ['id', 'country_id', 'name', 'code'],
            'co_service_municipalities' => ['id', 'department_id', 'name', 'code'],
            'co_service_type_document_identifications' => ['id', 'name', 'code'],
            'co_service_taxes' => ['id', 'name', 'description', 'code'],
            'co_service_type_regimes' => ['id', 'name', 'code'],
            'co_service_type_liabilities' => ['id', 'name', 'code'],
            'co_service_type_currencies' => ['id', 'name', 'code'],
            'co_service_type_operations' => ['id', 'name', 'code'],
            'co_service_type_environments' => ['id', 'name', 'code'],
            'co_service_languages' => ['id', 'name', 'code'],
            'co_payment_forms' => ['id', 'name', 'code'],
            'co_payment_methods' => ['id', 'name', 'code'],
        ];

        return $columnMap[$tableName] ?? [];
    }
}
