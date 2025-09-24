<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TestImportPuc extends Command
{
    protected $signature = 'test:import-puc';
    protected $description = 'Test PUC import with character normalization';

    public function handle()
    {
        $this->info('Probando importación PUC con normalización de caracteres...');

        $filePath = storage_path('app/templates/PUC_inicial.xlsx');

        if (!file_exists($filePath)) {
            $this->error('El archivo PUC_inicial.xlsx no existe en storage/app/templates/');
            return;
        }

        // Función de normalización (copiada del controller)
        $normalizarTexto = function($texto) {
            // Convertir a minúsculas
            $texto = strtolower(trim($texto));

            // Reemplazar caracteres acentuados
            $mapeo = [
                'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
                'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
                'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
                'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
                'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
                'ñ' => 'n',
                'ç' => 'c'
            ];

            return str_replace(array_keys($mapeo), array_values($mapeo), $texto);
        };

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Saltar header
            array_shift($rows);

            $tiposProblematicos = [];
            $contadorTipos = [];

            foreach ($rows as $index => $row) {
                $fila = $index + 2; // +2 porque empezamos en 1 y saltamos header

                if (empty($row[0])) continue; // Saltar filas vacías

                $tipoOriginal = trim($row[2] ?? '');
                $tipoNormalizado = $normalizarTexto($tipoOriginal); // Usar función de normalización

                // Contar tipos
                if (!isset($contadorTipos[$tipoNormalizado])) {
                    $contadorTipos[$tipoNormalizado] = 0;
                }
                $contadorTipos[$tipoNormalizado]++;

                // Mapear tipos de cuenta comunes
                $mapeoTipos = [
                    'gastos' => 'gasto',
                    'costos de venta' => 'costo',
                    'costos de ventas' => 'costo',
                    'costos de produccion' => 'costo',
                    'costos de produccion o de operacion' => 'costo',
                    'costos de operacion' => 'costo',
                    hex2bin('636f73746f732064652070726f6475636369e3b36e206f206465206f706572616369e3b36e') => 'costo', // Cadena UTF-8 corrupta
                    'cuentas de orden acreedoras' => 'patrimonio',
                    'cuentas de orden deudoras' => 'activo',
                    'ingresos' => 'ingreso',
                    'egresos' => 'gasto'
                ];

                $tipoFinal = $mapeoTipos[$tipoNormalizado] ?? $tipoNormalizado;

                $tiposValidos = ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'];

                if (!in_array($tipoFinal, $tiposValidos)) {
                    $tiposProblematicos[] = [
                        'fila' => $fila,
                        'codigo' => $row[0],
                        'original' => $tipoOriginal,
                        'normalizado' => $tipoNormalizado,
                        'final' => $tipoFinal
                    ];
                }
            }

            $this->info("\n=== RESUMEN DE TIPOS ENCONTRADOS ===");
            foreach ($contadorTipos as $tipo => $cantidad) {
                $this->line("$tipo: $cantidad cuentas");
            }

            $this->info("\n=== TIPOS PROBLEMÁTICOS DESPUÉS DE NORMALIZACIÓN ===");
            if (empty($tiposProblematicos)) {
                $this->info('¡No hay tipos problemáticos! La normalización funcionó correctamente.');
            } else {
                foreach ($tiposProblematicos as $problema) {
                    $this->error("Fila {$problema['fila']}: {$problema['codigo']} - '{$problema['original']}' -> '{$problema['normalizado']}' -> '{$problema['final']}'");
                }
            }

            $this->info("\nTotal de filas procesadas: " . count($rows));
            $this->info("Tipos problemáticos encontrados: " . count($tiposProblematicos));

        } catch (\Exception $e) {
            $this->error('Error al procesar el archivo: ' . $e->getMessage());
        }
    }
}
