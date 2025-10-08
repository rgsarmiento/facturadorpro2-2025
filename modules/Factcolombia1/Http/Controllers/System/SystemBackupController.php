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
     * Safe logging method that handles permission issues
     */
    private function safeLog($message)
    {
        try {
            \Log::info($message);
        } catch (\Exception $e) {
            // Si falla el log normal, usar archivo temporal
            $logFile = storage_path('app/backup_debug.log');
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Display a listing of system backups
     */
    public function index()
    {
        return view('factcolombia1::app.system.backup.index');
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
            // Configurar tiempo de ejecución y memoria para instalaciones con muchas empresas
            ini_set('max_execution_time', 7200); // 2 horas
            ini_set('memory_limit', '2G');

            // Logging alternativo para evitar problemas de permisos
            $this->safeLog('BACKUP: Iniciando proceso de backup del sistema completo');

            $backupsPath = storage_path('app/system_backups');
            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $systemBackupDir = $backupsPath . '/temp_system_backup_' . $timestamp;

            if (!is_dir($systemBackupDir)) {
                mkdir($systemBackupDir, 0755, true);
            }

            $this->safeLog('BACKUP: Directorio temporal creado: ' . $systemBackupDir);

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

            // 3. Backup de carpetas storage y public
            $this->backupStorageAndPublicFolders($systemBackupDir);

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
            $this->safeLog('BACKUP: Creando archivo ZIP final');
            $zipFilename = 'system_backup_' . $timestamp . '.zip';
            $zipPath = $backupsPath . '/' . $zipFilename;

            $this->createZipFile($systemBackupDir, $zipPath);

            // 4. Obtener tamaño real del ZIP creado
            $zipSize = file_exists($zipPath) ? filesize($zipPath) : 0;
            $this->safeLog('BACKUP: Archivo ZIP creado exitosamente - Tamaño: ' . $this->formatBytes($zipSize));

            // 5. Limpiar directorio temporal
            $this->safeLog('BACKUP: Limpiando directorio temporal');
            $this->deleteDirectory($systemBackupDir);

            $this->safeLog('BACKUP: Proceso de backup completado exitosamente');

            return response()->json([
                'success' => true,
                'message' => "Backup completo creado: BD Sistema + {$tenantCount} Tenants + Carpetas",
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
     * Start asynchronous full system backup
     */
    public function startAsync()
    {
        try {
            $id = date('YmdHis').'_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'),0,6);
            // Lanzar comando artisan en background (sin bloquear petición)
            $artisan = base_path('artisan');
            // Crear archivo de progreso inicial para evitar 404 en el primer polling
            $progressStub = [
                'id' => $id,
                'status' => 'starting',
                'step' => 'spawn',
                'message' => 'Inicializando proceso en background',
                'started_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'current' => 0,
                'successful' => 0,
                'total_tenants' => \Hyn\Tenancy\Models\Website::count(),
                'log' => [],
                'filename' => null,
                'errors' => []
            ];
            $progressPath = storage_path('app/system_backups');
            if(!is_dir($progressPath)) mkdir($progressPath,0755,true);
            file_put_contents($progressPath.'/progress_'.$id.'.json', json_encode($progressStub, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: start proceso separado
                pclose(popen('start /B php "'.$artisan.'" system:full-backup '.$id.' > NUL 2>&1', 'r'));
            } else {
                // Linux
                exec('php "'.$artisan.'" system:full-backup '.$id.' > /dev/null 2>&1 &');
            }
            return response()->json([
                'success' => true,
                'backup_id' => $id,
                'message' => 'Backup iniciado'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar el backup: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Poll async backup progress
     */
    public function progress($id)
    {
        $file = storage_path('app/system_backups/progress_'.$id.'.json');
        if(!file_exists($file)){
            return response()->json([
                'success' => false,
                'message' => 'No existe progreso para este ID'
            ], 404);
        }
        $json = json_decode(file_get_contents($file), true);
        return response()->json([
            'success' => true,
            'data' => $json
        ]);
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
            // Configurar tiempo de ejecución y memoria para restore con muchas empresas
            ini_set('max_execution_time', 7200); // 2 horas
            ini_set('memory_limit', '2G');

            $this->safeLog('RESTORE: Iniciando proceso de restauración del sistema');

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

            $this->safeLog('RESTORE: Archivo ZIP guardado temporalmente: ' . basename($tempZipFile));

            // Extraer ZIP
            $extractDir = $backupsPath . '/extract_temp_' . time();
            $this->safeLog('RESTORE: Extrayendo archivo ZIP a: ' . $extractDir);
            $this->extractZipFile($tempZipFile, $extractDir);

            // Configuración de base de datos
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $port = config("database.connections.{$connection}.port", 3306);

            $this->safeLog('RESTORE: Configuración de BD establecida - Host: ' . $host . ', Puerto: ' . $port);

            // Restaurar base de datos del sistema
            $this->safeLog('RESTORE: Iniciando restauración de base de datos del sistema');
            $this->restoreSystemDatabase($extractDir, $host, $port, $username, $password);

            // Ajustar configuración de tenants para la nueva instalación
            $this->safeLog('RESTORE: Ajustando configuración de tenants');
            $this->adjustTenantConfiguration();

            // Restaurar bases de datos de tenants
            $this->safeLog('RESTORE: Iniciando restauración de bases de datos de tenants');
            $this->restoreTenantDatabases($extractDir, $host, $port, $username, $password);

            // Restaurar carpetas storage y public
            $this->safeLog('RESTORE: Restaurando carpetas storage y public');
            $this->restoreStorageAndPublicFolders($extractDir);

            // Limpiar archivos temporales
            $this->safeLog('RESTORE: Limpiando archivos temporales');
            unlink($tempZipFile);
            $this->deleteDirectory($extractDir);

            $this->safeLog('RESTORE: Proceso de restauración completado exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Sistema restaurado exitosamente: BD + Carpetas + Tenants'
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
        $totalTenants = $websites->count();
        $batchSize = 10; // Procesar de 10 en 10 para evitar timeout
        $processed = 0;

        \Log::info("BACKUP: Iniciando backup de {$totalTenants} tenants en lotes de {$batchSize}");

        $successfulBackups = 0;

        // Procesar en lotes
        $batches = $websites->chunk($batchSize);
        $batchNumber = 1;

        foreach ($batches as $batch) {
            $this->safeLog("BACKUP: Procesando lote {$batchNumber}/" . $batches->count() . " (" . $batch->count() . " tenants)");

            foreach ($batch as $website) {
                // Obtener el hostname principal del website
                $hostname = $website->hostnames()->first();
                if (!$hostname) {
                    $processed++;
                    continue;
                }

                $tenantName = explode('.', $hostname->fqdn)[0];
                $tenantDatabase = $prefixDatabase . '_' . $tenantName;
                $processed++;

                $this->safeLog("BACKUP: [{$processed}/{$totalTenants}] Procesando tenant: {$tenantName}");

                // Verificar si la base de datos existe
                try {
                    $result = DB::connection('mysql')->select("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenantDatabase]);
                    if (empty($result)) {
                        $this->safeLog("BACKUP: Base de datos {$tenantDatabase} no existe, omitiendo...");
                        continue;
                    }
                } catch (\Exception $e) {
                    $this->safeLog("BACKUP: Error verificando DB {$tenantDatabase}: " . $e->getMessage());
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
                        $successfulBackups++;
                        $this->safeLog("BACKUP: [{$processed}/{$totalTenants}] Tenant {$tenantName} backup exitoso");
                    } else {
                        $this->safeLog("BACKUP: Tenant {$tenantName} produjo output vacío");
                    }
                } else {
                    $this->safeLog("BACKUP: Error en backup de tenant {$tenantName}: " . $process->getErrorOutput());
                }
            }

            $this->safeLog("BACKUP: Lote {$batchNumber} completado");
            $batchNumber++;
        }

        $this->safeLog("BACKUP: Backup de tenants completado ({$successfulBackups}/{$totalTenants} exitosos)");
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
            $this->safeLog('RESTORE: No hay directorio de tenants para restaurar');
            return; // No hay tenants para restaurar
        }

        $tenantFiles = glob($tenantsDir . '/tenant_*.sql');
        $prefixDatabase = env('PREFIX_DATABASE', 'tenancy');
        $totalTenants = count($tenantFiles);
        $batchSize = 10; // Procesar de 10 en 10 para evitar timeout
        $processed = 0;

        $this->safeLog("RESTORE: Iniciando restauración de {$totalTenants} tenants en lotes de {$batchSize}");

        // Procesar en lotes
        $batches = array_chunk($tenantFiles, $batchSize);
        $batchNumber = 1;

        foreach ($batches as $batch) {
            $this->safeLog("RESTORE: Procesando lote {$batchNumber}/" . count($batches) . " (" . count($batch) . " tenants)");

            foreach ($batch as $sqlFile) {
                $filename = basename($sqlFile, '.sql');
                $tenantName = str_replace('tenant_', '', $filename);
                $tenantDatabase = $prefixDatabase . '_' . $tenantName;
                $processed++;

                try {
                    $this->safeLog("RESTORE: [{$processed}/{$totalTenants}] Restaurando tenant: {$tenantName}");

                    // Crear la base de datos si no existe
                    DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$tenantDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    // Buscar el website correspondiente usando el nuevo UUID después del ajuste
                    $newUuid = $prefixDatabase . '_' . $tenantName;
                    $website = Website::where('uuid', $newUuid)->first();

                    if ($website) {
                        // Crear usuario de base de datos con contraseña calculada
                        $this->createTenantDatabaseUser($newUuid, $tenantDatabase, $website);
                    } else {
                        $this->safeLog("RESTORE: Website not found for UUID: {$newUuid}");
                    }

                    // Restaurar datos desde el archivo SQL
                    $this->restoreTenantSqlFile($sqlFile, $tenantDatabase, $host, $port, $username, $password, $extractDir, $tenantName);

                    $this->safeLog("RESTORE: [{$processed}/{$totalTenants}] Tenant {$tenantName} restaurado exitosamente");

                } catch (\Exception $e) {
                    // Log error pero continúa con otros tenants
                    $this->safeLog("RESTORE: Error restaurando tenant {$tenantName}: " . $e->getMessage());
                    continue;
                }
            }

            $this->safeLog("RESTORE: Lote {$batchNumber} completado");
            $batchNumber++;
        }

        $this->safeLog("RESTORE: Restauración de todos los tenants completada ({$processed} procesados)");
    }

    /**
     * Create ZIP file
     */
    private function createZipFile($sourceDir, $zipPath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }    /**
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

    /**
     * Create tenant database user with calculated password
     */
    private function createTenantDatabaseUser($tenantUuid, $databaseName, $website)
    {
        $password = $this->calculatePassword($website);

        try {
            // Verificar si el usuario ya existe
            $existingUsers = $this->checkExistingUsers($tenantUuid);

            // Crear usuario para localhost si no existe
            if (!in_array('localhost', $existingUsers)) {
                $createQuery = "CREATE USER `{$tenantUuid}`@`localhost` IDENTIFIED WITH mysql_native_password BY '{$password}'";
                DB::connection('mysql')->statement($createQuery);

                $grantQuery = "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO `{$tenantUuid}`@`localhost`";
                DB::connection('mysql')->statement($grantQuery);
            } else {
                // Actualizar contraseña si ya existe
                $alterQuery = "ALTER USER `{$tenantUuid}`@`localhost` IDENTIFIED BY '{$password}'";
                DB::connection('mysql')->statement($alterQuery);
            }

            // Crear usuario para % (cualquier host) si no existe
            if (!in_array('%', $existingUsers)) {
                $createQuery = "CREATE USER `{$tenantUuid}`@`%` IDENTIFIED WITH mysql_native_password BY '{$password}'";
                DB::connection('mysql')->statement($createQuery);

                $grantQuery = "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO `{$tenantUuid}`@`%`";
                DB::connection('mysql')->statement($grantQuery);
            } else {
                // Actualizar contraseña si ya existe
                $alterQuery = "ALTER USER `{$tenantUuid}`@`%` IDENTIFIED BY '{$password}'";
                DB::connection('mysql')->statement($alterQuery);
            }

            // Aplicar cambios
            DB::connection('mysql')->statement("FLUSH PRIVILEGES");

        } catch (\Exception $e) {
            \Log::error("Error creando usuario de BD para tenant {$tenantUuid}: " . $e->getMessage());
        }
    }

    /**
     * Restore SQL file for tenant database
     */
    private function restoreTenantSqlFile($sqlFile, $tenantDatabase, $host, $port, $username, $password, $extractDir, $tenantName)
    {
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

    /**
     * Calculate password for tenant (same logic as TenantPasswords command)
     */
    private function calculatePassword($website)
    {
        return md5(sprintf(
            '%s.%d',
            config('app.key'),
            $website->id
        ));
    }

    /**
     * Check which users exist for a given username
     */
    private function checkExistingUsers($username)
    {
        try {
            $result = DB::connection('mysql')->select("SELECT User, Host FROM mysql.user WHERE User = ?", [$username]);
            return collect($result)->pluck('Host')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Adjust tenant configuration after system restore to match new installation settings
     */
    private function adjustTenantConfiguration()
    {
        try {
            $newPrefixDatabase = env('PREFIX_DATABASE', 'tenancy');
            $newAppUrlBase = env('APP_URL_BASE', 'nodomain.com');

            // Obtener todos los websites
            $websites = Website::all();

            foreach ($websites as $website) {
                $oldUuid = $website->uuid;

                // Extraer el nombre del tenant del UUID actual
                // Si el UUID es "oldprefix_torres", extraemos "torres"
                $tenantName = $this->extractTenantNameFromUuid($oldUuid);

                if ($tenantName) {
                    // Construir nuevo UUID con el nuevo prefijo
                    $newUuid = $newPrefixDatabase . '_' . $tenantName;

                    // Solo actualizar si el UUID cambió
                    if ($oldUuid !== $newUuid) {
                        $website->uuid = $newUuid;
                        $website->save();

                        \Log::info("Updated website UUID from {$oldUuid} to {$newUuid}");
                    }

                    // Actualizar hostnames para este website
                    $hostnames = $website->hostnames;
                    foreach ($hostnames as $hostname) {
                        $oldFqdn = $hostname->fqdn;

                        // Construir nuevo FQDN: tenantName.newAppUrlBase
                        $newFqdn = $tenantName . '.' . $newAppUrlBase;

                        // Solo actualizar si el FQDN cambió
                        if ($oldFqdn !== $newFqdn) {
                            $hostname->fqdn = $newFqdn;
                            $hostname->save();

                            \Log::info("Updated hostname FQDN from {$oldFqdn} to {$newFqdn}");
                        }
                    }
                }
            }

            \Log::info("Tenant configuration adjustment completed successfully");

        } catch (\Exception $e) {
            \Log::error("Error adjusting tenant configuration: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract tenant name from UUID by removing the prefix
     */
    private function extractTenantNameFromUuid($uuid)
    {
        // Buscar el último underscore para separar prefijo del nombre del tenant
        $lastUnderscorePos = strrpos($uuid, '_');

        if ($lastUnderscorePos !== false) {
            // Extraer la parte después del último underscore
            return substr($uuid, $lastUnderscorePos + 1);
        }

        // Si no hay underscore, retornar el UUID completo como nombre del tenant
        return $uuid;
    }

    /**
     * Backup storage and public folders
     */
    private function backupStorageAndPublicFolders($backupDir)
    {
        try {
            \Log::info("Starting backup of storage and public folders");

            $foldersDir = $backupDir . '/folders';
            if (!is_dir($foldersDir)) {
                mkdir($foldersDir, 0755, true);
            }

            // Backup storage folder (excluding logs and framework cache)
            $storageSource = storage_path();
            $storageBackup = $foldersDir . '/storage';

            if (is_dir($storageSource)) {
                $this->copyDirectorySelective($storageSource, $storageBackup, [
                    'logs',
                    'framework/cache',
                    'framework/sessions',
                    'framework/views',
                    'app/system_backups' // Evitar recursión
                ]);
                \Log::info("Storage folder backed up successfully");
            }

            // Backup public folder (excluding large cache files)
            $publicSource = public_path();
            $publicBackup = $foldersDir . '/public';

            if (is_dir($publicSource)) {
                $this->copyDirectorySelective($publicSource, $publicBackup, [
                    'hot',
                    'mix-manifest.json'
                ]);
                \Log::info("Public folder backed up successfully");
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("Error backing up folders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Copy directory with exclusions
     */
    private function copyDirectorySelective($source, $destination, $excludePatterns = [])
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath); // Normalizar separadores

            // Verificar si debe excluir este archivo/directorio
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }

        return true;
    }

    /**
     * Restore storage and public folders
     */
    private function restoreStorageAndPublicFolders($extractedDir)
    {
        try {
            \Log::info("Starting restoration of storage and public folders");

            $foldersDir = $extractedDir . '/folders';

            if (!is_dir($foldersDir)) {
                \Log::info("No folders directory found in backup, skipping folder restoration");
                return true;
            }

            // Restore storage folder
            $storageBackup = $foldersDir . '/storage';
            $storageTarget = storage_path();

            if (is_dir($storageBackup)) {
                $this->restoreDirectorySelective($storageBackup, $storageTarget, [
                    'logs',
                    'framework/cache',
                    'framework/sessions',
                    'framework/views',
                    'app/system_backups'
                ]);
                \Log::info("Storage folder restored successfully");
            }

            // Restore public folder
            $publicBackup = $foldersDir . '/public';
            $publicTarget = public_path();

            if (is_dir($publicBackup)) {
                $this->restoreDirectorySelective($publicBackup, $publicTarget, [
                    'hot',
                    'mix-manifest.json'
                ]);
                \Log::info("Public folder restored successfully");
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("Error restoring folders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restore directory with exclusions and safety checks
     */
    private function restoreDirectorySelective($source, $destination, $excludePatterns = [])
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath); // Normalizar separadores

            // Verificar si debe excluir este archivo/directorio
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                // Solo copiar si el archivo no existe o es diferente
                if (!file_exists($targetPath) || filemtime($item->getPathname()) > filemtime($targetPath)) {
                    copy($item->getPathname(), $targetPath);
                }
            }
        }

        return true;
    }
}
