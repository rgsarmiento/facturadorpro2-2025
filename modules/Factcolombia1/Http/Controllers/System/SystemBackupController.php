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
     * Restore complete system backup from server file
     */
    public function restoreFromServer(Request $request)
    {
        try {
            // Configurar tiempo de ejecución y memoria para restore con muchas empresas
            ini_set('max_execution_time', 36000); // 10 horas
            ini_set('memory_limit', '2G');

            $this->safeLog('RESTORE FROM SERVER: Iniciando proceso de restauración del sistema');

            $request->validate([
                'filename' => 'required|string'
            ]);

            $backupsPath = storage_path('app/system_backups');
            $tempZipFile = $backupsPath . '/' . $request->filename;

            if (!file_exists($tempZipFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup no existe en el servidor'
                ], 404);
            }

            $this->safeLog('RESTORE FROM SERVER: Usando archivo: ' . $request->filename);

            return $this->performRestore($tempZipFile, false); // false = no eliminar el archivo original

        } catch (\Throwable $e) {
            $this->safeLog('RESTORE FROM SERVER ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el sistema: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Restore complete system backup from uploaded file
     */
    public function restore(Request $request)
    {
        try {
            // Configurar tiempo de ejecución y memoria para restore con muchas empresas
            ini_set('max_execution_time', 86400); // 24 horas
            ini_set('memory_limit', '4G');
            set_time_limit(86400); // 24 horas

            $this->safeLog('RESTORE: Iniciando proceso de restauración del sistema');

            // Aumentar límite permitido. Nota: la directiva 'max' está en kilobytes.
            // 50 GB = 50 * 1024 * 1024 KB = 52428800
            $request->validate([
                'backup_file' => 'required|file|max:52428800' // 50GB max (en KB)
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

            return $this->performRestore($tempZipFile, true); // true = eliminar archivo temporal después

        } catch (\Throwable $e) {
            $this->safeLog('RESTORE ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            // Limpiar archivo temporal en caso de error
            if (isset($tempZipFile) && file_exists($tempZipFile)) {
                $this->safeUnlinkWithRetry($tempZipFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el sistema: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Perform the actual restore process
     */
    private function performRestore($tempZipFile, $deleteTempFile = true)
    {
        $backupsPath = storage_path('app/system_backups');
        $extractDir = null;

        try {
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

            // Sincronizar contraseñas de tenants (crítico para que funcionen después del restore)
            $this->safeLog('RESTORE: Sincronizando contraseñas de usuarios de base de datos de tenants');
            $this->syncTenantPasswordsUsingCommand();

            // Limpiar archivos temporales
            $this->safeLog('RESTORE: Limpiando archivos temporales');

            // En Windows, ZipArchive puede mantener el archivo bloqueado brevemente después de extractTo()
            // Esperar un momento y reintentar si es necesario
            if ($deleteTempFile && file_exists($tempZipFile)) {
                $this->safeUnlinkWithRetry($tempZipFile);
            }
            $this->deleteDirectory($extractDir);

            $this->safeLog('RESTORE: Proceso de restauración completado exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Sistema restaurado exitosamente: BD + Carpetas + Tenants'
            ]);

        } catch (\Throwable $e) {
            // Log detallado para diagnóstico
            $this->safeLog('RESTORE ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            // Limpiar archivos temporales en caso de error
            if ($deleteTempFile && isset($tempZipFile) && file_exists($tempZipFile)) {
                $this->safeUnlinkWithRetry($tempZipFile);
            }
            if (isset($extractDir) && is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el sistema: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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

        // Verificar y crear bases de datos faltantes
        $this->createMissingTenantDatabases();
    }

    /**
     * Create missing tenant databases after restore
     * This handles cases where SQL files were missing from backup
     */
    private function createMissingTenantDatabases()
    {
        try {
            $this->safeLog("RESTORE: Verificando bases de datos faltantes de tenants");

            $websites = Website::all();
            $missingDatabases = [];
            $referenceDatabaseName = null;

            // Encontrar tenants con bases de datos faltantes y una base de referencia
            foreach ($websites as $website) {
                $databaseName = $website->uuid;
                $result = DB::select(
                    "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?",
                    [$databaseName]
                );

                if (empty($result)) {
                    $missingDatabases[] = $website;
                } else if (!$referenceDatabaseName) {
                    $referenceDatabaseName = $databaseName;
                }
            }

            if (empty($missingDatabases)) {
                $this->safeLog("RESTORE: Todas las bases de datos de tenants existen");
                return;
            }

            if (!$referenceDatabaseName) {
                $this->safeLog("RESTORE WARNING: No hay base de datos de referencia para copiar estructura");
                return;
            }

            $this->safeLog("RESTORE: Encontradas " . count($missingDatabases) . " bases de datos faltantes");
            $this->safeLog("RESTORE: Usando '{$referenceDatabaseName}' como plantilla");

            foreach ($missingDatabases as $website) {
                $databaseName = $website->uuid;

                try {
                    $this->safeLog("RESTORE: Creando base de datos faltante: {$databaseName}");

                    // Crear base de datos
                    DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    // Deshabilitar verificación de foreign keys temporalmente
                    DB::statement("SET FOREIGN_KEY_CHECKS=0");

                    // Copiar estructura
                    $tables = DB::select("SHOW TABLES FROM `{$referenceDatabaseName}`");
                    $tableKey = "Tables_in_{$referenceDatabaseName}";

                    // Cambiar a la base de datos destino
                    DB::statement("USE `{$databaseName}`");

                    foreach ($tables as $table) {
                        $tableName = $table->$tableKey;

                        // Obtener CREATE TABLE statement
                        $createTableResult = DB::select("SHOW CREATE TABLE `{$referenceDatabaseName}`.`{$tableName}`");
                        $createTableStatement = $createTableResult[0]->{'Create Table'};

                        // Crear tabla en la nueva base de datos
                        DB::statement($createTableStatement);
                    }

                    // Volver a habilitar verificación de foreign keys
                    DB::statement("SET FOREIGN_KEY_CHECKS=1");

                    // Volver a la base de datos del sistema
                    DB::statement("USE `" . config('database.connections.system.database') . "`");

                    // Copiar datos críticos
                    $this->copyEssentialTenantData($databaseName, $referenceDatabaseName);

                    // Crear usuario de base de datos
                    $this->createTenantDatabaseUser($website->uuid, $databaseName, $website);

                    $this->safeLog("RESTORE: Base de datos '{$databaseName}' creada exitosamente con estructura copiada");

                } catch (\Exception $e) {
                    // Asegurarse de que las foreign keys estén habilitadas nuevamente
                    try {
                        DB::statement("SET FOREIGN_KEY_CHECKS=1");
                    } catch (\Exception $ex) {}

                    $this->safeLog("RESTORE WARNING: Error creando base de datos faltante {$databaseName}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $this->safeLog("RESTORE WARNING: Error en verificación de bases de datos faltantes: " . $e->getMessage());
        }
    }

    /**
     * Copy essential data from reference database to new tenant database
     */
    private function copyEssentialTenantData($targetDatabase, $referenceDatabase)
    {
        $criticalTables = [
            'configurations',
            'cat_payment_method_types',
            'currencies',
            'currency_types',
            'attributes',
            'charge_discount_types',
            'system_activity_types',
            'operation_types',
            'document_types',
        ];

        // Agregar catálogos (catalog_01 hasta catalog_59)
        for ($i = 1; $i <= 59; $i++) {
            $criticalTables[] = 'catalog_' . str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        foreach ($criticalTables as $tableName) {
            try {
                // Verificar si la tabla existe en ambas bases de datos
                $tableExistsInReference = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                    [$referenceDatabase, $tableName]
                );

                $tableExistsInTarget = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                    [$targetDatabase, $tableName]
                );

                if ($tableExistsInReference[0]->count > 0 && $tableExistsInTarget[0]->count > 0) {
                    // Verificar si hay datos en la tabla de referencia
                    $count = DB::select("SELECT COUNT(*) as count FROM `{$referenceDatabase}`.`{$tableName}`")[0]->count;

                    if ($count > 0) {
                        // Copiar datos
                        DB::statement("INSERT IGNORE INTO `{$targetDatabase}`.`{$tableName}` SELECT * FROM `{$referenceDatabase}`.`{$tableName}`");
                    }
                }
            } catch (\Exception $tableError) {
                // Continuar con la siguiente tabla si hay error
                continue;
            }
        }
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
     * Extract ZIP file with validation for Windows-incompatible filenames
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

        // Extraer archivo por archivo para manejar nombres inválidos en Windows
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Saltar entradas que son solo directorios
            if (substr($filename, -1) === '/') {
                continue;
            }

            // Limpiar nombre de archivo para Windows
            $cleanFilename = $this->sanitizeFilenameForWindows($filename);

            // Si el nombre cambió, extraer manualmente
            if ($cleanFilename !== $filename) {
                $this->safeLog("RESTORE: Renombrando archivo problemático: '$filename' -> '$cleanFilename'");

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    $this->safeLog("RESTORE WARNING: No se pudo leer el contenido de '$filename', saltando...");
                    continue;
                }

                $targetPath = $extractDir . '/' . $cleanFilename;
                $targetDir = dirname($targetPath);

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                file_put_contents($targetPath, $content);
            } else {
                // Extraer normalmente
                $zip->extractTo($extractDir, $filename);
            }
        }

        $zip->close();
    }

    /**
     * Sanitize filename for Windows compatibility
     * Removes/replaces invalid characters and patterns
     */
    private function sanitizeFilenameForWindows($filename)
    {
        // Dividir en directorio y nombre de archivo
        $parts = explode('/', $filename);

        foreach ($parts as $index => &$part) {
            if (empty($part)) continue;

            // Remover caracteres inválidos para Windows: < > : " | ? *
            $part = preg_replace('/[<>:"|?*]/', '_', $part);

            // Remover slash inicial si existe
            $part = ltrim($part, '/\\');

            // Remover punto final (Windows no permite archivos que terminen en punto)
            $part = rtrim($part, '.');

            // Remover espacios al inicio/final
            $part = trim($part);

            // Si quedó vacío después de limpieza, usar nombre genérico
            if (empty($part)) {
                $part = 'file_' . $index;
            }
        }

        return implode('/', $parts);
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
     * Delete directory recursively with error handling
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        try {
            $files = @scandir($dir);
            if ($files === false) {
                return; // No se puede leer el directorio, saltar
            }

            $files = array_diff($files, array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    @unlink($path); // @ para suprimir warnings si el archivo ya no existe
                }
            }
            @rmdir($dir);
        } catch (\Exception $e) {
            // Ignorar errores al eliminar directorios temporales
            $this->safeLog("RESTORE WARNING: Error eliminando directorio temporal: " . $e->getMessage());
        }
    }

    /**
     * Safe unlink with retry for Windows file locking issues
     * En Windows, algunos procesos (como ZipArchive) pueden mantener handles abiertos brevemente
     */
    private function safeUnlinkWithRetry($filePath, $maxRetries = 5, $delayMs = 500)
    {
        if (!file_exists($filePath)) {
            return true;
        }

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                if (@unlink($filePath)) {
                    $this->safeLog("RESTORE: Archivo temporal eliminado: " . basename($filePath));
                    return true;
                }
            } catch (\Exception $e) {
                // Log pero continuar intentando
            }

            if ($attempt < $maxRetries) {
                $this->safeLog("RESTORE: Reintento {$attempt}/{$maxRetries} para eliminar " . basename($filePath));
                usleep($delayMs * 1000); // Convertir ms a microsegundos
            }
        }

        // Si después de todos los intentos aún existe, loguear advertencia pero no fallar
        $this->safeLog("RESTORE WARNING: No se pudo eliminar " . basename($filePath) . " después de {$maxRetries} intentos. El archivo se puede eliminar manualmente.");
        return false;
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
                $createQuery = "CREATE USER `{$tenantUuid}`@`localhost` IDENTIFIED BY '{$password}'";
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
                $createQuery = "CREATE USER `{$tenantUuid}`@`%` IDENTIFIED BY '{$password}'";
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
     * Sync tenant database passwords using the existing tenant_passwords command
     * Critical to ensure tenants can connect to their databases after restore
     */
    private function syncTenantPasswordsUsingCommand()
    {
        try {
            $this->safeLog("RESTORE: Ejecutando comando tenant_passwords para sincronizar contraseñas");

            // Ejecutar el comando existente con la opción --create-missing para crear usuarios si no existen
            \Artisan::call('tenant_passwords', ['--create-missing' => true]);

            $output = \Artisan::output();

            // Log the output
            $this->safeLog("RESTORE: Resultado de sincronización de contraseñas:");
            foreach (explode("\n", trim($output)) as $line) {
                if (!empty($line)) {
                    $this->safeLog("  " . $line);
                }
            }

            $this->safeLog("RESTORE: Sincronización de contraseñas completada exitosamente");
            return true;

        } catch (\Exception $e) {
            $this->safeLog("RESTORE WARNING: Error ejecutando tenant_passwords: " . $e->getMessage());
            // No lanzar excepción, el restore ya está completo
            return false;
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
     * Restore storage and public folders (non-critical, won't fail entire restore)
     */
    private function restoreStorageAndPublicFolders($extractedDir)
    {
        try {
            $this->safeLog("RESTORE: Iniciando restauración de carpetas storage y public");

            $foldersDir = $extractedDir . '/folders';

            if (!is_dir($foldersDir)) {
                $this->safeLog("RESTORE: No hay directorio de folders para restaurar, saltando");
                return true;
            }

            // Verificar espacio en disco antes de intentar
            $availableSpace = @disk_free_space(storage_path());
            if ($availableSpace !== false && $availableSpace < 1073741824) { // Menos de 1GB
                $this->safeLog("RESTORE WARNING: Poco espacio en disco (" . $this->formatBytes($availableSpace) . "), saltando restauración de carpetas");
                return true; // No fallar, solo omitir
            }

            // Restore storage folder (non-critical)
            $storageBackup = $foldersDir . '/storage';
            $storageTarget = storage_path();

            if (is_dir($storageBackup)) {
                try {
                    $this->restoreDirectorySelective($storageBackup, $storageTarget, [
                        'logs',
                        'framework/cache',
                        'framework/sessions',
                        'framework/views',
                        'app/system_backups'
                    ]);
                    $this->safeLog("RESTORE: Storage folder restaurado exitosamente");
                } catch (\Exception $e) {
                    $this->safeLog("RESTORE WARNING: Error restaurando storage, continuando: " . $e->getMessage());
                    // No lanzar excepción, solo loguear y continuar
                }
            }

            // Restore public folder (non-critical)
            $publicBackup = $foldersDir . '/public';
            $publicTarget = public_path();

            if (is_dir($publicBackup)) {
                try {
                    $this->restoreDirectorySelective($publicBackup, $publicTarget, [
                        'hot'
                    ]);
                    $this->safeLog("RESTORE: Public folder restaurado exitosamente");
                } catch (\Exception $e) {
                    $this->safeLog("RESTORE WARNING: Error restaurando public, continuando: " . $e->getMessage());
                    // No lanzar excepción, solo loguear y continuar
                }
            }

            return true;

        } catch (\Exception $e) {
            // Si falla la restauración de carpetas, solo loguear warning
            // Las BDs ya están restauradas, que es lo más importante
            $this->safeLog("RESTORE WARNING: Error general restaurando carpetas: " . $e->getMessage());
            return true; // Retornar true para que el restore continúe
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

        $copiedFiles = 0;
        $skippedFiles = 0;
        $errorFiles = 0;

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
                    @mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                }

                // Solo copiar si el archivo no existe o es diferente
                if (!file_exists($targetPath) || @filemtime($item->getPathname()) > @filemtime($targetPath)) {
                    try {
                        // Verificar espacio antes de copiar archivos grandes
                        $fileSize = @filesize($item->getPathname());
                        $availableSpace = @disk_free_space(dirname($targetPath));

                        if ($availableSpace !== false && $fileSize !== false && $availableSpace < $fileSize) {
                            $skippedFiles++;
                            if ($skippedFiles === 1) {
                                // Solo loguear la primera vez para no saturar el log
                                $this->safeLog("RESTORE WARNING: Espacio insuficiente, saltando archivos restantes");
                            }
                            continue;
                        }

                        if (@copy($item->getPathname(), $targetPath)) {
                            $copiedFiles++;
                        } else {
                            $errorFiles++;
                        }
                    } catch (\Exception $e) {
                        $errorFiles++;
                        // No lanzar excepción, solo contar error y continuar
                    }
                }
            }
        }

        $this->safeLog("RESTORE: Archivos copiados: {$copiedFiles}, Omitidos: {$skippedFiles}, Errores: {$errorFiles}");
        return true;
    }

    /**
     * Iniciar carga por chunks - crear sesión de carga
     */
    public function initChunkUpload(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string',
                'filesize' => 'required|integer|min:1',
                'total_chunks' => 'required|integer|min:1'
            ]);

            $uploadId = uniqid('upload_', true);
            $uploadPath = storage_path('app/chunk_uploads/' . $uploadId);

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Guardar metadata de la carga
            $metadata = [
                'upload_id' => $uploadId,
                'filename' => $request->filename,
                'filesize' => $request->filesize,
                'total_chunks' => $request->total_chunks,
                'uploaded_chunks' => [],
                'created_at' => now()->toISOString(),
                'last_activity' => now()->toISOString()
            ];

            file_put_contents(
                $uploadPath . '/metadata.json',
                json_encode($metadata, JSON_PRETTY_PRINT)
            );

            return response()->json([
                'success' => true,
                'upload_id' => $uploadId,
                'message' => 'Sesión de carga iniciada'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar carga: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir un chunk individual
     */
    public function uploadChunk(Request $request)
    {
        try {
            // Extender tiempo de ejecución para este chunk
            ini_set('max_execution_time', 600); // 10 minutos por chunk
            set_time_limit(600);

            $request->validate([
                'upload_id' => 'required|string',
                'chunk_index' => 'required|integer|min:0',
                'chunk' => 'required|file'
            ]);

            $uploadId = $request->upload_id;
            $chunkIndex = $request->chunk_index;
            $uploadPath = storage_path('app/chunk_uploads/' . $uploadId);

            if (!is_dir($uploadPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión de carga no encontrada'
                ], 404);
            }

            // Cargar metadata
            $metadataFile = $uploadPath . '/metadata.json';
            if (!file_exists($metadataFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metadata de carga no encontrada'
                ], 404);
            }

            $metadata = json_decode(file_get_contents($metadataFile), true);

            // Verificar que el chunk no se haya subido ya
            if (in_array($chunkIndex, $metadata['uploaded_chunks'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Chunk ya fue subido previamente',
                    'already_uploaded' => true,
                    'progress' => count($metadata['uploaded_chunks']) / $metadata['total_chunks'] * 100
                ]);
            }

            // Guardar chunk
            $chunk = $request->file('chunk');
            $chunkPath = $uploadPath . '/chunk_' . str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);
            $chunk->move($uploadPath, basename($chunkPath));

            // Actualizar metadata
            $metadata['uploaded_chunks'][] = $chunkIndex;
            $metadata['uploaded_chunks'] = array_unique($metadata['uploaded_chunks']);
            sort($metadata['uploaded_chunks']);
            $metadata['last_activity'] = now()->toISOString();

            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));

            $progress = count($metadata['uploaded_chunks']) / $metadata['total_chunks'] * 100;

            return response()->json([
                'success' => true,
                'message' => 'Chunk subido correctamente',
                'chunk_index' => $chunkIndex,
                'uploaded_chunks' => count($metadata['uploaded_chunks']),
                'total_chunks' => $metadata['total_chunks'],
                'progress' => $progress
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir chunk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado de carga
     */
    public function checkUploadStatus(Request $request)
    {
        try {
            $request->validate([
                'upload_id' => 'required|string'
            ]);

            $uploadId = $request->upload_id;
            $uploadPath = storage_path('app/chunk_uploads/' . $uploadId);
            $metadataFile = $uploadPath . '/metadata.json';

            if (!file_exists($metadataFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión de carga no encontrada'
                ], 404);
            }

            $metadata = json_decode(file_get_contents($metadataFile), true);
            $progress = count($metadata['uploaded_chunks']) / $metadata['total_chunks'] * 100;

            return response()->json([
                'success' => true,
                'upload_id' => $uploadId,
                'uploaded_chunks' => $metadata['uploaded_chunks'],
                'total_chunks' => $metadata['total_chunks'],
                'progress' => $progress,
                'is_complete' => count($metadata['uploaded_chunks']) === $metadata['total_chunks']
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar carga y ensamblar archivo completo
     */
    public function finalizeChunkUpload(Request $request)
    {
        try {
            // Configurar tiempo de ejecución y memoria para ensamblaje
            ini_set('max_execution_time', 86400); // 24 horas
            ini_set('memory_limit', '4G');
            set_time_limit(86400);

            $request->validate([
                'upload_id' => 'required|string'
            ]);

            $uploadId = $request->upload_id;
            $uploadPath = storage_path('app/chunk_uploads/' . $uploadId);
            $metadataFile = $uploadPath . '/metadata.json';

            if (!file_exists($metadataFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión de carga no encontrada'
                ], 404);
            }

            $metadata = json_decode(file_get_contents($metadataFile), true);

            // Verificar que todos los chunks estén presentes
            if (count($metadata['uploaded_chunks']) !== $metadata['total_chunks']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan chunks por subir',
                    'uploaded' => count($metadata['uploaded_chunks']),
                    'total' => $metadata['total_chunks']
                ], 400);
            }

            // Ensamblar archivo final
            $backupsPath = storage_path('app/system_backups');
            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            $finalFile = $backupsPath . '/restore_temp_' . time() . '.zip';
            $finalHandle = fopen($finalFile, 'wb');

            if (!$finalHandle) {
                throw new \Exception('No se pudo crear archivo final');
            }

            // Ensamblar chunks en orden
            for ($i = 0; $i < $metadata['total_chunks']; $i++) {
                $chunkPath = $uploadPath . '/chunk_' . str_pad($i, 6, '0', STR_PAD_LEFT);

                if (!file_exists($chunkPath)) {
                    fclose($finalHandle);
                    unlink($finalFile);
                    throw new \Exception("Falta el chunk {$i}");
                }

                $chunkHandle = fopen($chunkPath, 'rb');
                if (!$chunkHandle) {
                    fclose($finalHandle);
                    unlink($finalFile);
                    throw new \Exception("No se pudo leer el chunk {$i}");
                }

                while (!feof($chunkHandle)) {
                    $data = fread($chunkHandle, 8192);
                    fwrite($finalHandle, $data);
                }

                fclose($chunkHandle);
            }

            fclose($finalHandle);

            // Limpiar chunks
            $this->cleanupChunkUpload($uploadId);

            // Iniciar proceso de restauración
            return $this->performRestore($finalFile, true);

        } catch (\Throwable $e) {
            // Limpiar en caso de error
            if (isset($finalFile) && file_exists($finalFile)) {
                $this->safeUnlinkWithRetry($finalFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar carga: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar archivos de carga por chunks
     */
    private function cleanupChunkUpload($uploadId)
    {
        try {
            $uploadPath = storage_path('app/chunk_uploads/' . $uploadId);

            if (is_dir($uploadPath)) {
                // Eliminar todos los archivos del directorio
                $files = glob($uploadPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                // Eliminar directorio
                rmdir($uploadPath);
            }
        } catch (\Exception $e) {
            // Error silencioso al limpiar
        }
    }

    /**
     * Cancelar carga por chunks
     */
    public function cancelChunkUpload(Request $request)
    {
        try {
            $request->validate([
                'upload_id' => 'required|string'
            ]);

            $uploadId = $request->upload_id;
            $this->cleanupChunkUpload($uploadId);

            return response()->json([
                'success' => true,
                'message' => 'Carga cancelada y limpiada'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar carga: ' . $e->getMessage()
            ], 500);
        }
    }
}
