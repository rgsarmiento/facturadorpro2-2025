<?php

namespace Modules\Factcolombia1\Jobs\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Factcolombia1\Http\Controllers\System\CompanyController;
use Modules\Factcolombia1\Http\Requests\System\CompanyRequest;
use Illuminate\Support\Str;

class CreateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    protected $payload;
    /**
     * @var string
     */
    protected $taskId;

    public function __construct(array $payload, string $taskId)
    {
        $this->payload = $payload;
        $this->taskId = $taskId;
        $this->onQueue('default');
    }

    public function handle()
    {
        // Construir un CompanyController para reutilizar la lógica existente
        $controller = app(CompanyController::class);

        $this->writeStatus([
            'id' => $this->taskId,
            'status' => 'running',
            'message' => 'Creando configuración en ApiDIAN',
            'progress' => 10,
            'result' => null,
            'started_at' => date('c'),
        ]);

        // Crear un request válido
        $request = CompanyRequest::create('/co-companies', 'POST', $this->payload);

        // Paso 1: Registrar en ApiDIAN (interno de store)
        // Usaremos el método store pero iremos actualizando estado entre bloques; por simplicidad, invocamos store() completa.
        try {
            $this->writeStatus([ 'status' => 'running', 'message' => 'Creando website y hostname', 'progress' => 30 ]);

            $response = $controller->store($request);

            $data = $response->getData(true);

            if (!($data['success'] ?? false)) {
                $this->writeStatus([
                    'status' => 'failed',
                    'message' => $data['message'] ?? 'Fallo creando compañía',
                    'progress' => 100,
                    'result' => $data,
                    'finished_at' => date('c'),
                ]);
                return;
            }

            $this->writeStatus([
                'status' => 'completed',
                'message' => 'Compañía creada exitosamente',
                'progress' => 100,
                'result' => $data,
                'finished_at' => date('c'),
            ]);
        } catch (\Throwable $e) {
            $this->writeStatus([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'progress' => 100,
                'result' => [ 'exception' => get_class($e) ],
                'finished_at' => date('c'),
            ]);
        }
    }

    protected function writeStatus(array $patch): void
    {
        // Leer estado actual, fusionar y escribir
        $dir = storage_path('app/tenant_creation_tasks');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . $this->taskId . '.json';
        $current = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) $current = $data;
        }
        $new = array_merge($current, $patch);
        @file_put_contents($file, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
