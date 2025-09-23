<?php

namespace App\Exports;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PucInicialExport implements FromArray, WithHeadings
{
    protected $data;
    protected $headers;

    public function __construct()
    {
        $this->loadDataFromOriginalFile();
    }

    protected function loadDataFromOriginalFile()
    {
        $filePath = public_path('formats/PUC_inicial.xlsx');

        if (!file_exists($filePath)) {
            // Fallback a datos básicos si el archivo no existe
            $this->headers = [
                'codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'nivel',
                'cuenta_padre_codigo', 'descripcion', 'activa', 'permite_movimiento', 'requiere_tercero'
            ];
            $this->data = [
                ['1', 'ACTIVO', 'activo', 'debito', 1, '', 'Representa todos los bienes y derechos de la empresa', 1, 0, 0]
            ];
            return;
        }

        try {
            $excelData = Excel::toArray([], $filePath)[0];

            if (empty($excelData)) {
                throw new \Exception('Archivo vacío');
            }

            // Obtener headers de la primera fila
            $originalHeaders = array_shift($excelData);

            // Mapear headers para que sean consistentes con la nueva estructura
            $this->headers = [];
            foreach ($originalHeaders as $header) {
                $normalizedHeader = strtolower(trim($header));
                if ($normalizedHeader === 'cuenta_padre_id') {
                    $this->headers[] = 'cuenta_padre_codigo';
                } else {
                    $this->headers[] = $normalizedHeader;
                }
            }

            // Procesar datos
            $this->data = [];
            foreach ($excelData as $row) {
                if (empty(array_filter($row))) {
                    continue; // Saltar filas vacías
                }

                $processedRow = [];
                foreach ($row as $index => $value) {
                    if ($index === 5) { // cuenta_padre_id/cuenta_padre_codigo
                        // Convertir a string y limpiar
                        $processedRow[] = !empty(trim((string)$value)) ? trim((string)$value) : '';
                    } elseif ($index === 2) { // tipo_cuenta
                        // Normalizar tipo de cuenta a minúsculas y mapear tipos comunes
                        $tipoRaw = strtolower(trim((string)$value));
                        $mapeoTipos = [
                            'gastos' => 'gasto',
                            'costos de venta' => 'costo',
                            'costos de ventas' => 'costo',
                            'costos de produccion' => 'costo',
                            'costos de producción' => 'costo',
                            'costos de produccion o de operacion' => 'costo',
                            'costos de producción o de operación' => 'costo',
                            'costos de produccin o de operacin' => 'costo', // Versión sin tildes
                            'costos de operacion' => 'costo',
                            'costos de operación' => 'costo',
                            'cuentas de orden acreedoras' => 'patrimonio',
                            'cuentas de orden deudoras' => 'activo',
                            'ingresos' => 'ingreso',
                            'egresos' => 'gasto'
                        ];
                        $processedRow[] = isset($mapeoTipos[$tipoRaw]) ? $mapeoTipos[$tipoRaw] : $tipoRaw;
                    } elseif ($index === 3) { // naturaleza
                        // Normalizar naturaleza a minúsculas
                        $processedRow[] = strtolower(trim((string)$value));
                    } else {
                        $processedRow[] = $value;
                    }
                }

                $this->data[] = $processedRow;
            }

        } catch (\Exception $e) {
            // Fallback en caso de error
            $this->headers = [
                'codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'nivel',
                'cuenta_padre_codigo', 'descripcion', 'activa', 'permite_movimiento', 'requiere_tercero'
            ];
            $this->data = [
                ['1', 'ACTIVO', 'activo', 'debito', 1, '', 'Representa todos los bienes y derechos de la empresa', 1, 0, 0]
            ];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function getDataForCSV(): array
    {
        return array_merge([$this->headers], $this->data);
    }
}
