<?php

namespace Modules\Report\Traits;

trait PdfMemoryManagement
{
    /**
     * Configurar recursos para generación de PDFs
     *
     * @param string $memoryLimit Límite de memoria (ej: '256M', '512M', '1G')
     * @param int $executionTime Tiempo máximo de ejecución en segundos (0 = ilimitado)
     * @return void
     */
    protected function configurePdfResources($memoryLimit = '512M', $executionTime = 300)
    {
        $this->increaseMemoryLimit($memoryLimit);
        $this->increaseExecutionTime($executionTime);
    }

    /**
     * Aumentar límite de memoria para generación de PDFs
     *
     * @param string $limit Límite de memoria (ej: '256M', '512M', '1G')
     * @return void
     */
    protected function increaseMemoryLimit($limit = '512M')
    {
        $currentLimit = ini_get('memory_limit');

        // Convertir a bytes para comparar
        $currentBytes = $this->convertToBytes($currentLimit);
        $newBytes = $this->convertToBytes($limit);

        // Solo aumentar si el nuevo límite es mayor que el actual
        if ($newBytes > $currentBytes) {
            ini_set('memory_limit', $limit);
        }
    }

    /**
     * Aumentar tiempo máximo de ejecución para generación de PDFs
     *
     * @param int $seconds Tiempo en segundos (0 = ilimitado)
     * @return void
     */
    protected function increaseExecutionTime($seconds = 300)
    {
        $currentLimit = ini_get('max_execution_time');

        if ($currentLimit == 0) {
            // Ya está en ilimitado, no hacer nada
            return;
        }

        if ($seconds == 0 || $seconds > $currentLimit) {
            set_time_limit($seconds);
        }
    }

    /**
     * Convertir string de memoria a bytes
     *
     * @param string $value
     * @return int
     */
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Liberar memoria después de generar el PDF
     *
     * @return void
     */
    protected function freeMemory()
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}
