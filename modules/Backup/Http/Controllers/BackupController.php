<?php

namespace Modules\Backup\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    /**
     * Display a listing of backups
     */
    public function index()
    {
        return view('backup::index');
    }

    /**
     * Crear una nueva copia de seguridad de la base de datos
     */
    public function create()
    {
        try {
            $backupsPath = storage_path('app/backups');

            // Crear directorio si no existe
            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            // Configuración de la base de datos del tenant
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $port = config("database.connections.{$connection}.port", 3306);

            // Obtener el nombre de la base de datos del tenant actual
            $hostname = app(\Hyn\Tenancy\Contracts\CurrentHostname::class);
            if (!$hostname) {
                throw new \Exception('No se pudo obtener el hostname actual. Asegúrese de que esté accediendo desde un dominio de tenant válido.');
            }

            if (!$hostname->website) {
                throw new \Exception('No se pudo obtener el website del tenant. El hostname no tiene un website asociado.');
            }

            // Extraer el nombre del tenant del FQDN (ej: torres.facturadorpro2.oo -> torres)
            $tenantName = explode('.', $hostname->fqdn)[0];

            $prefixDatabase = env('PREFIX_DATABASE', 'tenancy');
            if (empty($prefixDatabase)) {
                $prefixDatabase = 'tenancy'; // Fallback por defecto
            }

            $tenantDatabase = $prefixDatabase . '_' . $tenantName;

            // Nombre del archivo de backup
            $filename = 'backup_' . $tenantName . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupsPath . '/' . $filename;

            // Comando mysqldump
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

            // Ejecutar el proceso con timeout de 5 minutos
            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Guardar el output en el archivo
            file_put_contents($filePath, $process->getOutput());

            return response()->json([
                'success' => true,
                'message' => 'Backup creado exitosamente',
                'filename' => $filename,
                'size' => $this->formatBytes(filesize($filePath))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las copias de seguridad disponibles
     */
    public function list()
    {
        try {
            $backupsPath = storage_path('app/backups');
            $backups = [];

            // Crear directorio si no existe
            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            if (is_dir($backupsPath)) {
                $files = scandir($backupsPath);

                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                        $filePath = $backupsPath . '/' . $file;
                        if (file_exists($filePath)) {
                            $backups[] = [
                                'filename' => $file,
                                'size' => $this->formatBytes(filesize($filePath)),
                                'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                                'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                            ];
                        }
                    }
                }
            }

            // Ordenar por fecha de creación (más reciente primero)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'backups' => $backups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'backups' => []
            ]);
        }
    }

    /**
     * Descargar una copia de seguridad específica
     */
    public function download($filename)
    {
        try {
            $backupsPath = storage_path('app/backups');
            $filePath = $backupsPath . '/' . $filename;

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una copia de seguridad específica
     */
    public function delete($filename)
    {
        try {
            $backupsPath = storage_path('app/backups');
            $filePath = $backupsPath . '/' . $filename;

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
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
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar base de datos desde un archivo de backup
     */
    public function restore(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|max:102400' // 100MB max, allow any file type for SQL files
            ]);

            // Additional validation for SQL files
            $file = $request->file('backup_file');
            $extension = $file->getClientOriginalExtension();
            if (!in_array(strtolower($extension), ['sql'])) {
                throw new \Exception('El archivo debe ser un archivo SQL (.sql)');
            }

            $backupsPath = storage_path('app/backups');

            // Crear directorio si no existe
            if (!is_dir($backupsPath)) {
                mkdir($backupsPath, 0755, true);
            }

            // Guardar archivo temporalmente
            $tempFile = $backupsPath . '/restore_temp_' . time() . '.sql';
            $file->move($backupsPath, basename($tempFile));

            // Configuración de la base de datos del tenant
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $port = config("database.connections.{$connection}.port", 3306);

            // Obtener el nombre de la base de datos del tenant actual
            $hostname = app(\Hyn\Tenancy\Contracts\CurrentHostname::class);
            if (!$hostname || !$hostname->website) {
                throw new \Exception('No se pudo determinar el tenant actual para la restauración.');
            }

            // Extraer el nombre del tenant del FQDN (ej: torres.facturadorpro2.oo -> torres)
            $tenantName = explode('.', $hostname->fqdn)[0];

            $prefixDatabase = env('PREFIX_DATABASE', 'tenancy');
            $tenantDatabase = $prefixDatabase . '_' . $tenantName;

            // Verificar que MySQL esté disponible
            exec('mysql --version 2>&1', $mysqlCheck, $mysqlReturnCode);
            if ($mysqlReturnCode !== 0) {
                throw new \Exception('MySQL no está disponible en el PATH del sistema. Asegúrese de que MySQL esté instalado y agregado al PATH.');
            }

            // Preparar archivo SQL con comandos de foreign key y otros settings de seguridad
            $sqlContent = file_get_contents($tempFile);
            $modifiedSqlFile = $backupsPath . '/modified_restore_' . time() . '.sql';

            $modifiedContent = "-- Disable foreign key checks and other safety features\n";
            $modifiedContent .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $modifiedContent .= "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n";
            $modifiedContent .= "SET AUTOCOMMIT = 0;\n";
            $modifiedContent .= "START TRANSACTION;\n\n";
            $modifiedContent .= $sqlContent . "\n\n";
            $modifiedContent .= "-- Re-enable foreign key checks\n";
            $modifiedContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            $modifiedContent .= "COMMIT;\n";

            file_put_contents($modifiedSqlFile, $modifiedContent);

            // Para Windows, crear un archivo batch temporal para evitar problemas con redirection
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $batchFile = $backupsPath . '/restore_' . time() . '.bat';
                $batchContent = sprintf(
                    '@echo off' . "\n" .
                    'mysql --force --host=%s --port=%s --user=%s --password=%s %s < "%s"',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($tenantDatabase),
                    str_replace('/', '\\', $modifiedSqlFile)
                );
                file_put_contents($batchFile, $batchContent);
                $command = '"' . $batchFile . '"';
            } else {
                // Unix/Linux command
                $command = sprintf(
                    'mysql --force --host=%s --port=%s --user=%s --password=%s %s < %s',
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($tenantDatabase),
                    escapeshellarg($modifiedSqlFile)
                );
            }

            // Ejecutar el proceso de restauración usando shell_exec
            $output = null;
            $returnCode = null;
            exec($command . ' 2>&1', $output, $returnCode);

            // Eliminar archivo temporal
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Eliminar archivo SQL modificado
            if (isset($modifiedSqlFile) && file_exists($modifiedSqlFile)) {
                unlink($modifiedSqlFile);
            }

            // Eliminar archivo batch si existe (Windows)
            if (isset($batchFile) && file_exists($batchFile)) {
                unlink($batchFile);
            }

            if ($returnCode !== 0) {
                // Analizar si son errores críticos o solo advertencias/foreign key issues
                $outputString = implode("\n", $output);
                $criticalErrors = [
                    'Access denied',
                    'Unknown database',
                    'Table doesn\'t exist',
                    'Can\'t connect to MySQL server',
                    'Lost connection'
                ];

                $hasCriticalError = false;
                foreach ($criticalErrors as $error) {
                    if (stripos($outputString, $error) !== false) {
                        $hasCriticalError = true;
                        break;
                    }
                }

                // Si solo tiene foreign key errors, considerarlo exitoso pero con advertencias
                if (!$hasCriticalError && stripos($outputString, 'Cannot add foreign key constraint') !== false) {
                    \Log::warning('Backup restored with foreign key warnings (non-critical)', [
                        'return_code' => $returnCode,
                        'warnings' => $outputString
                    ]);
                } else if ($hasCriticalError) {
                    throw new \Exception('Error crítico al ejecutar MySQL restore. Código de salida: ' . $returnCode . '. Output: ' . $outputString);
                } else {
                    throw new \Exception('Error al ejecutar MySQL restore. Código de salida: ' . $returnCode . '. Output: ' . $outputString);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Base de datos restaurada exitosamente'
            ]);

        } catch (\Exception $e) {
            // Limpiar archivo temporal si existe
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Limpiar archivo SQL modificado si existe
            if (isset($modifiedSqlFile) && file_exists($modifiedSqlFile)) {
                unlink($modifiedSqlFile);
            }

            // Limpiar archivo batch si existe (Windows)
            if (isset($batchFile) && file_exists($batchFile)) {
                unlink($batchFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la base de datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatear bytes en formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
