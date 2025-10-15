<?php

namespace Modules\Factcolombia1\Jobs\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Factcolombia1\Http\Controllers\System\SystemBackupController;

/**
 * Ejecuta la restauración completa de un backup grande de forma asíncrona.
 * Escribe progreso incremental en storage/app/system_restore_tasks/{taskId}.json
 */
class AsyncRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $sourceFile; // ruta absoluta al zip
    protected $expectedSha256; // opcional

    public function __construct(string $taskId, string $sourceFile, ?string $expectedSha256 = null)
    {
        $this->taskId = $taskId;
        $this->sourceFile = $sourceFile;
        $this->expectedSha256 = $expectedSha256;
        $this->onQueue('default');
    }

    public function handle()
    {
        $this->writeStatus([
            'status' => 'running',
            'message' => 'Validando archivo',
            'progress' => 2,
            'started_at' => date('c')
        ]);
        try {
            if (!file_exists($this->sourceFile)) {
                throw new \Exception('Archivo de backup no encontrado: ' . basename($this->sourceFile));
            }
            $size = filesize($this->sourceFile);
            $free = @disk_free_space(dirname($this->sourceFile));
            if ($free !== false) {
                // requerimos ~2.5x
                if ($free < ($size * 2.5)) {
                    throw new \Exception('Espacio insuficiente en disco para restaurar. Requerido >= 2.5x del tamaño del backup. Libre: ' . $this->formatBytes($free) . ' / Backup: ' . $this->formatBytes($size));
                }
            }

            if ($this->expectedSha256) {
                $this->writeStatus(['message' => 'Calculando checksum SHA256', 'progress' => 5]);
                $realHash = hash_file('sha256', $this->sourceFile);
                if (strcasecmp($realHash, $this->expectedSha256) !== 0) {
                    throw new \Exception('Checksum SHA256 no coincide. Esperado ' . $this->expectedSha256 . ' calculado ' . $realHash);
                }
                $this->writeStatus(['message' => 'Checksum verificado', 'sha256' => $realHash, 'progress' => 8]);
            }

            // Preparar controlador para reutilizar lógica existente
            /** @var SystemBackupController $controller */
            $controller = app(SystemBackupController::class);

            // Reutilizaremos métodos privados via callable si es posible; si no, replicaríamos lógica.
            // Aquí invocamos un método público adaptado que implementaremos: performRestoreFromExisting
            $this->writeStatus(['message' => 'Iniciando extracción', 'progress' => 12]);
            $result = $controller->performRestoreFromExisting($this->sourceFile, function ($phase, $percent, $extra = []) {
                $this->writeStatus(array_merge(['message' => $phase, 'progress' => $percent], $extra));
            });

            $this->writeStatus([
                'status' => 'completed',
                'message' => 'Restauración completada',
                'progress' => 100,
                'finished_at' => date('c'),
                'result' => $result
            ]);
        } catch (\Throwable $e) {
            $this->writeStatus([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'progress' => 100,
                'finished_at' => date('c'),
                'exception' => get_class($e)
            ]);
        }
    }

    protected function taskDir()
    {
        return storage_path('app/system_restore_tasks');
    }

    protected function writeStatus(array $patch)
    {
        $dir = $this->taskDir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . $this->taskId . '.json';
        $current = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) $current = $data;
        }
        $new = array_merge($current, $patch, ['id' => $this->taskId]);
        @file_put_contents($file, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = $size > 0 ? log($size, 1024) : 0;
        $suffixes = array('B','KB','MB','GB','TB');
        return round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)];
    }
}
