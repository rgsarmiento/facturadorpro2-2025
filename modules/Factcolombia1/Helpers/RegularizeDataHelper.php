<?php

namespace Modules\Factcolombia1\Helpers;

use DB;

class RegularizeDataHelper
{

    /**
     * Regularizar data de las tablas con errores
     * Verifica si tiene registros (seeders con errores)
     * Elimina la data
     * Regulariza con la data actualizada del .csv
     *
     * @param  string $table_name
     * @return void
     */
    public static function regularizeDataFromTable($table_name)
    {

        $exist_records = self::countRecords($table_name);

        // si hay registros se eliminan para ejecutar los seeders corregidos
        if($exist_records > 0)
        {
            self::deleteRecords($table_name);
            self::insertDataFromSeeder($table_name);
        }

    }

    public static function insertDataFromSeeder($table_name)
    {

        $tables = [
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
            'co_type_overtime_surcharges' => [
                'columns' => 'id, name, code, percentage, type, @created_at, @updated_at'
            ],
            'co_type_payroll_adjust_notes' => [
                'columns' => 'id, name, code, @created_at, @updated_at'
            ],
            'co_type_generation_transmitions' => [
                'columns' => 'id, name, code, @created_at, @updated_at'
            ],
            'co_service_type_documents' => [
                'columns' => 'id, name, code, cufe_algorithm, prefix, @created_at, @updated_at'
            ],
            'co_health_type_document_identifications' => [
               'columns' => 'id, name, code, @created_at, @updated_at'
            ],
        ];

        $prefix = 'csv';
        $key = $table_name;
        $table = $tables[$table_name];

        // Determinar la conexión correcta (tenant cuando está disponible, default en caso contrario)
        $connection = \Illuminate\Support\Facades\Schema::getConnection()->getName();
        if ($connection === 'tenant' || DB::connection('tenant')->getDatabaseName()) {
            $connection = 'tenant';
        } else {
            $connection = null; // Usar conexión por defecto
        }

        $csvPath = str_replace(DIRECTORY_SEPARATOR, '/', public_path($prefix.DIRECTORY_SEPARATOR."{$key}.{$prefix}"));

        try {
            // Intentar LOAD DATA LOCAL INFILE
            if ($connection) {
                DB::connection($connection)
                    ->getpdo()
                    ->exec("LOAD DATA LOCAL INFILE '{$csvPath}' INTO TABLE $key({$table['columns']}) SET created_at = NOW(), updated_at = NOW()");
            } else {
                DB::connection()
                    ->getpdo()
                    ->exec("LOAD DATA LOCAL INFILE '{$csvPath}' INTO TABLE $key({$table['columns']}) SET created_at = NOW(), updated_at = NOW()");
            }
        } catch (\Exception $e) {
            // Si falla, usar método alternativo
            self::loadFromCsvFileAlternative($table_name, $csvPath, $connection);
        }
    }

    /**
     * Método alternativo para cargar CSV cuando LOAD DATA falla
     */
    protected static function loadFromCsvFileAlternative($tableName, $csvPath, $connection = null)
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

        $columnMap = [
            'co_type_workers' => ['id', 'name', 'code'],
            'co_sub_type_workers' => ['id', 'name', 'code'],
            'co_payroll_type_document_identifications' => ['id', 'name', 'code'],
            'co_type_contracts' => ['id', 'name', 'code'],
            'co_payroll_periods' => ['id', 'name', 'code'],
            'co_type_overtime_surcharges' => ['id', 'name', 'code', 'percentage', 'type'],
            'co_type_payroll_adjust_notes' => ['id', 'name', 'code'],
            'co_type_generation_transmitions' => ['id', 'name', 'code'],
            'co_service_type_documents' => ['id', 'name', 'code', 'cufe_algorithm', 'prefix'],
            'co_health_type_document_identifications' => ['id', 'name', 'code'],
        ];

        $columns = $columnMap[$tableName] ?? [];

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
                if ($connection) {
                    DB::connection($connection)->table($tableName)->insert($rows);
                } else {
                    DB::table($tableName)->insert($rows);
                }
                $rows = [];
            }
        }

        if (!empty($rows)) {
            if ($connection) {
                DB::connection($connection)->table($tableName)->insert($rows);
            } else {
                DB::table($tableName)->insert($rows);
            }
        }

        fclose($file);
    }


    public static function deleteRecords($table)
    {
        // Determinar la conexión correcta
        $connection = \Illuminate\Support\Facades\Schema::getConnection()->getName();
        if ($connection === 'tenant' || DB::connection('tenant')->getDatabaseName()) {
            DB::connection('tenant')->table($table)->delete();
        } else {
            DB::table($table)->delete();
        }
    }


    public static function countRecords($table)
    {
        // Determinar la conexión correcta
        $connection = \Illuminate\Support\Facades\Schema::getConnection()->getName();
        if ($connection === 'tenant' || DB::connection('tenant')->getDatabaseName()) {
            return DB::connection('tenant')->table($table)->count();
        } else {
            return DB::table($table)->count();
        }
    }

}
