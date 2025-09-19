<?php

namespace Modules\Factcolombia1\Http\Controllers\System;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Models\Hostname;

class SystemBackupController extends Controller
{
    /**
     * Display a listing of system backups
     */
    public function index()
    {
        return view('factcolombia1::app.system.backup.index');
    }

    /**
     * Debug tenant information (temporal para diagnóstico)
     */
    public function debugTenants()
    {
        try {
            $websites = Website::all();
            $prefixDatabase = env('PREFIX_DATABASE', 'tenancy');

            $debug = [
                'total_websites' => $websites->count(),
                'prefix_database' => $prefixDatabase,
                'websites' => [],
                'available_databases' => [],
                'mysqldump_test' => null
            ];

            // Obtener todas las bases de datos disponibles
            $databases = DB::select('SHOW DATABASES');
            foreach ($databases as $db) {
                $dbName = $db->Database ?? $db->database;
                if (strpos($dbName, $prefixDatabase) === 0) {
                    $debug['available_databases'][] = $dbName;
                }
            }

            foreach ($websites as $website) {
                $hostname = $website->hostnames()->first();
                $websiteData = [
                    'id' => $website->id,
                    'uuid' => $website->uuid,
                    'hostname' => $hostname ? $hostname->fqdn : null,
                    'tenant_name' => null,
                    'expected_database' => null,
                    'database_exists' => false
                ];

                if ($hostname) {
                    $tenantName = explode('.', $hostname->fqdn)[0];
                    $expectedDatabase = $prefixDatabase . '_' . $tenantName;

                    $websiteData['tenant_name'] = $tenantName;
                    $websiteData['expected_database'] = $expectedDatabase;
                    $websiteData['database_exists'] = in_array($expectedDatabase, $debug['available_databases']);
                }

                $debug['websites'][] = $websiteData;
            }

            // Test mysqldump con la primera base de datos de tenant encontrada
            if (!empty($debug['available_databases'])) {
                $testDatabase = $debug['available_databases'][0];
                $debug['mysqldump_test'] = $this->testMysqldump($testDatabase);
            }

            return response()->json([
                'success' => true,
                'debug' => $debug
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test mysqldump functionality
     */
    private function testMysqldump($database)
    {
        try {
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $port = config("database.connections.{$connection}.port", 3306);

            $command = [
                'mysqldump',
                '--host=' . $host,
                '--port=' . $port,
                '--user=' . $username,
                '--password=' . $password,
                '--single-transaction',
                '--no-data',  // Solo estructura, no datos para el test
                $database
            ];

            $process = new Process($command);
            $process->setTimeout(30); // 30 segundos para el test
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'command' => implode(' ', $command),
                'output_length' => strlen($process->getOutput()),
                'error' => $process->isSuccessful() ? null : $process->getErrorOutput()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all system backups
     */
    public function list()
    {
        try {
            $backupsPath = storage_path('app/system_backups');

            if (!is_dir($backupsPath)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $files = [];
            $backupFiles = glob($backupsPath . '/system_backup_*.zip');

            foreach ($backupFiles as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $date = filemtime($file);

                $files[] = [
                    'filename' => $filename,
                    'size' => $size, // Tamaño en bytes para JavaScript
                    'size_formatted' => $this->formatBytes($size), // Tamaño formateado como respaldo
                    'date' => date('Y-m-d H:i:s', $date),
                    'path' => $file
                ];
            }

            // Ordenar por fecha descendente
            usort($files, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return response()->json([
                'success' => true,
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar los backups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a complete system backup (admin + all tenants)
     */
    public function create()
    {
        try {
            $backupsPath = storage_path('app/system_backups');
            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $systemBackupDir = $backupsPath . '/temp_system_backup_' . $timestamp;

            if (!is_dir($systemBackupDir)) {
                mkdir($systemBackupDir, 0755, true);
            }

            // Configuración de base de datos
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $port = config("database.connections.{$connection}.port", 3306);
            $systemDatabase = config("database.connections.{$connection}.database");

            // 1. Backup de la base de datos del sistema/administrador
            $this->createSystemDatabaseBackup($systemBackupDir, $host, $port, $username, $password, $systemDatabase);

            // 2. Backup de todas las bases de datos de tenants
            $this->createTenantDatabasesBackup($systemBackupDir, $host, $port, $username, $password);

            // Obtener estadísticas de backup
            $systemDbBackupPath = $systemBackupDir . '/system_database.sql';
            $tenantsDir = $systemBackupDir . '/tenants';

            $tenantCount = 0;
            $systemDbExists = file_exists($systemDbBackupPath);
            if (is_dir($tenantsDir)) {
                $tenantFiles = glob($tenantsDir . '/tenant_*.sql');
                $tenantCount = count($tenantFiles);
            }

            // 3. Crear archivo ZIP con todos los backups
            $zipFilename = 'system_backup_' . $timestamp . '.zip';
            $zipPath = $backupsPath . '/' . $zipFilename;

            $this->createZipFile($systemBackupDir, $zipPath);

            // 4. Obtener tamaño real del ZIP creado
            $zipSize = file_exists($zipPath) ? filesize($zipPath) : 0;

            // 5. Limpiar directorio temporal
            $this->deleteDirectory($systemBackupDir);

            return response()->json([
                'success' => true,
                'message' => "Backup completo creado: BD Sistema + {$tenantCount} Tenants",
                'filename' => $zipFilename,
                'details' => [
                    'system_database' => $systemDbExists,
                    'tenant_count' => $tenantCount,
                    'zip_size' => $this->formatBytes($zipSize),
                    'debug_info' => [
                        'zip_path_exists' => file_exists($zipPath),
                        'zip_size_bytes' => $zipSize,
                        'tenants_dir_existed' => is_dir($tenantsDir)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            // Limpiar directorio temporal en caso de error
            if (isset($systemBackupDir) && is_dir($systemBackupDir)) {
                $this->deleteDirectory($systemBackupDir);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el backup del sistema: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a system backup
     */
    public function download($filename)
    {
        try {
            $filePath = storage_path('app/system_backups/' . $filename);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup no existe'
                ], 404);
            }

            return response()->download($filePath);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a system backup
     */
    public function delete($filename)
    {
        try {
            $filePath = storage_path('app/system_backups/' . $filename);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup no existe'
                ], 404);
            }

            unlink($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Backup eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore complete system backup
     */
    public function restore(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|max:1048576' // 1GB max
            ]);

            $file = $request->file('backup_file');
            $backupsPath = storage_path('app/system_backups');

            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            // Guardar archivo ZIP temporal
            $tempZipFile = $backupsPath . '/restore_temp_' . time() . '.zip';
            $file->move($backupsPath, basename($tempZipFile));

            // Extraer ZIP
            $extractDir = $backupsPath . '/extract_temp_' . time();
            $this->extractZipFile($tempZipFile, $extractDir);

            // Configuración de base de datos
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $port = config("database.connections.{$connection}.port", 3306);

            // Restaurar base de datos del sistema
            $this->restoreSystemDatabase($extractDir, $host, $port, $username, $password);

            // Restaurar bases de datos de tenants
            $this->restoreTenantDatabases($extractDir, $host, $port, $username, $password);

            // Limpiar archivos temporales
            unlink($tempZipFile);
            $this->deleteDirectory($extractDir);

            return response()->json([
                'success' => true,
                'message' => 'Sistema restaurado exitosamente desde el backup'
            ]);

        } catch (\Exception $e) {
            // Limpiar archivos temporales en caso de error
            if (isset($tempZipFile) && file_exists($tempZipFile)) {
                unlink($tempZipFile);
            }
            if (isset($extractDir) && is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el sistema: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create system database backup
     */
    private function createSystemDatabaseBackup($backupDir, $host, $port, $username, $password, $database)
    {
        $filename = 'system_database.sql';
        $filePath = $backupDir . '/' . $filename;

        $command = [
            'mysqldump',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--password=' . $password,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--add-drop-table',
            '--disable-keys',
            '--extended-insert',
            '--no-autocommit',
            $database
        ];

        $process = new Process($command);
        $process->setTimeout(600); // 10 minutos
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        file_put_contents($filePath, $process->getOutput());
    }

    /**
     * Create all tenant databases backup
     */
    private function createTenantDatabasesBackup($backupDir, $host, $port, $username, $password)
    {
        $tenantsDir = $backupDir . '/tenants';
        if (!is_dir($tenantsDir)) {
            mkdir($tenantsDir, 0755, true);
        }

        // Obtener todos los websites/tenants
        $websites = Website::all();
        $prefixDatabase = env('PREFIX_DATABASE', 'tenancy');

        \Log::info("SystemBackup: Encontrados " . $websites->count() . " websites");
        \Log::info("SystemBackup: Prefijo de BD: " . $prefixDatabase);
        \Log::info("SystemBackup: Directorio tenants: " . $tenantsDir);

        $successfulBackups = 0;

        foreach ($websites as $website) {
            // Obtener el hostname principal del website
            $hostname = $website->hostnames()->first();
            if (!$hostname) {
                \Log::warning("SystemBackup: Website ID {$website->id} no tiene hostname");
                continue;
            }

            $tenantName = explode('.', $hostname->fqdn)[0];
            $tenantDatabase = $prefixDatabase . '_' . $tenantName;

            \Log::info("SystemBackup: Procesando tenant: {$tenantName}, BD: {$tenantDatabase}");

            // Verificar si la base de datos existe
            try {
                $result = DB::connection('mysql')->select("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenantDatabase]);
                if (empty($result)) {
                    \Log::warning("SystemBackup: BD {$tenantDatabase} no existe");
                    continue;
                }
                \Log::info("SystemBackup: BD {$tenantDatabase} existe, creando backup");
            } catch (\Exception $e) {
                \Log::error("SystemBackup: Error verificando BD {$tenantDatabase}: " . $e->getMessage());
                continue;
            }

            $filename = 'tenant_' . $tenantName . '.sql';
            $filePath = $tenantsDir . '/' . $filename;

            $command = [
                'mysqldump',
                '--host=' . $host,
                '--port=' . $port,
                '--user=' . $username,
                '--password=' . $password,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--add-drop-table',
                '--disable-keys',
                '--extended-insert',
                '--no-autocommit',
                $tenantDatabase
            ];

            $process = new Process($command);
            $process->setTimeout(600);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                if (!empty($output)) {
                    file_put_contents($filePath, $output);
                    $fileSize = filesize($filePath);
                    \Log::info("SystemBackup: Backup de {$tenantDatabase} creado exitosamente. Tamaño: " . $this->formatBytes($fileSize));
                    $successfulBackups++;
                } else {
                    \Log::warning("SystemBackup: mysqldump de {$tenantDatabase} ejecutado pero sin contenido");
                }
            } else {
                \Log::error("SystemBackup: Error creando backup de {$tenantDatabase}: " . $process->getErrorOutput());
            }
        }

        \Log::info("SystemBackup: Completado backup de tenants. {$successfulBackups} exitosos de " . $websites->count() . " total");

        // Verificar archivos creados en el directorio
        if (is_dir($tenantsDir)) {
            $tenantFiles = glob($tenantsDir . '/tenant_*.sql');
            \Log::info("SystemBackup: Archivos SQL de tenants encontrados: " . count($tenantFiles));
            foreach ($tenantFiles as $file) {
                \Log::info("SystemBackup: - " . basename($file) . " (" . $this->formatBytes(filesize($file)) . ")");
            }
        }
    }

    /**
     * Restore system database
     */
    private function restoreSystemDatabase($extractDir, $host, $port, $username, $password)
    {
        $sqlFile = $extractDir . '/system_database.sql';
        if (!file_exists($sqlFile)) {
            throw new \Exception('No se encontró el archivo de backup de la base de datos del sistema');
        }

        $systemDatabase = config("database.connections." . config('database.default') . ".database");

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: usar archivo batch
            $batchFile = $extractDir . '/restore_system_' . time() . '.bat';
            $batchContent = sprintf(
                '@echo off' . "\n" .
                'mysql --force --host=%s --port=%s --user=%s --password=%s %s < "%s"',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($systemDatabase),
                str_replace('/', '\\', $sqlFile)
            );

            file_put_contents($batchFile, $batchContent);
            $command = $batchFile;
        } else {
            // Linux/Unix
            $command = sprintf(
                'mysql --force --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($systemDatabase),
                escapeshellarg($sqlFile)
            );
        }

        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Error al restaurar la base de datos del sistema: ' . implode("\n", $output));
        }

        // Limpiar archivo batch en Windows
        if (isset($batchFile) && file_exists($batchFile)) {
            unlink($batchFile);
        }
    }

    /**
     * Restore tenant databases
     */
    private function restoreTenantDatabases($extractDir, $host, $port, $username, $password)
    {
        $tenantsDir = $extractDir . '/tenants';
        if (!is_dir($tenantsDir)) {
            return; // No hay tenants para restaurar
        }

        $tenantFiles = glob($tenantsDir . '/tenant_*.sql');
        $prefixDatabase = env('PREFIX_DATABASE', 'tenancy');

        foreach ($tenantFiles as $sqlFile) {
            $filename = basename($sqlFile, '.sql');
            $tenantName = str_replace('tenant_', '', $filename);
            $tenantDatabase = $prefixDatabase . '_' . $tenantName;

            // Crear la base de datos si no existe
            try {
                DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$tenantDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (\Exception $e) {
                continue;
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: usar archivo batch
                $batchFile = $extractDir . '/restore_tenant_' . $tenantName . '_' . time() . '.bat';
                $batchContent = sprintf(
                    '@echo off' . "\n" .
                    'mysql --force --host=%s --port=%s --user=%s --password=%s %s < "%s"',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($tenantDatabase),
                    str_replace('/', '\\', $sqlFile)
                );

                file_put_contents($batchFile, $batchContent);
                $command = $batchFile;
            } else {
                // Linux/Unix
                $command = sprintf(
                    'mysql --force --host=%s --port=%s --user=%s --password=%s %s < %s',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($tenantDatabase),
                    escapeshellarg($sqlFile)
                );
            }

            exec($command . ' 2>&1', $output, $returnCode);

            // Limpiar archivo batch en Windows
            if (isset($batchFile) && file_exists($batchFile)) {
                unlink($batchFile);
            }
        }
    }

    /**
     * Create ZIP file
     */
    private function createZipFile($sourceDir, $zipPath)
    {
        \Log::info("SystemBackup: Creando ZIP desde {$sourceDir} hacia {$zipPath}");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $addedFiles = 0;
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
                $addedFiles++;
                \Log::info("SystemBackup: Agregado al ZIP: {$relativePath} (" . $this->formatBytes($file->getSize()) . ")");
            }
        }

        $zip->close();
        \Log::info("SystemBackup: ZIP creado con {$addedFiles} archivos. Tamaño final: " . $this->formatBytes(filesize($zipPath)));
    }

    /**
     * Extract ZIP file
     */
    private function extractZipFile($zipPath, $extractDir)
    {
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            throw new \Exception('No se pudo abrir el archivo ZIP');
        }

        $zip->extractTo($extractDir);
        $zip->close();
    }

    /**
     * Get directory size in bytes
     */
    private function getDirectorySize($directory)
    {
        $size = 0;
        if (is_dir($directory)) {
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file){
                $size += $file->getSize();
            }
        }
        return $size;
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
