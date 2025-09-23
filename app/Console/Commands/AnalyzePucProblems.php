<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class AnalyzePucProblems extends Command
{
    protected $signature = 'analyze:puc-problems';
    protected $description = 'Analyze specific problems in PUC file';

    public function handle()
    {
        $filePath = public_path('formats/PUC_inicial.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        try {
            $data = Excel::toArray([], $filePath)[0];

            // Skip headers
            array_shift($data);

            $this->info("Analizando problemas específicos en el archivo PUC...");
            $this->info("");

            $cuentasRaizProblematicas = [];
            $cuentasPadreFaltantes = [];
            $tiposInvalidos = [];

            foreach ($data as $index => $row) {
                $fila = $index + 2; // +2 porque array_shift quitó headers y index empieza en 0

                if (empty(array_filter($row))) continue;

                $codigo = trim((string)$row[0]);
                $nombre = trim((string)$row[1]);
                $tipo_cuenta = strtolower(trim((string)$row[2]));
                $naturaleza = strtolower(trim((string)$row[3]));
                $nivel = intval($row[4]);
                $cuenta_padre = trim((string)($row[5] ?? ''));

                // Analizar cuentas raíz problemáticas
                if (empty($cuenta_padre) && $nivel != 1) {
                    $cuentasRaizProblematicas[] = [
                        'fila' => $fila,
                        'codigo' => $codigo,
                        'nombre' => $nombre,
                        'nivel_actual' => $nivel
                    ];
                }

                // Mapear tipos para ver cuáles siguen siendo inválidos
                $mapeoTipos = [
                    'gastos' => 'gasto',
                    'costos de venta' => 'costo',
                    'costos de ventas' => 'costo',
                    'costos de producción' => 'costo',
                    'costos de producción o de operación' => 'costo',
                    'costos de operación' => 'costo',
                    'cuentas de orden acreedoras' => 'patrimonio',
                    'cuentas de orden deudoras' => 'activo',
                    'ingresos' => 'ingreso',
                    'egresos' => 'gasto'
                ];

                $tipo_normalizado = isset($mapeoTipos[$tipo_cuenta]) ? $mapeoTipos[$tipo_cuenta] : $tipo_cuenta;

                if (!in_array($tipo_normalizado, ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'])) {
                    $tiposInvalidos[] = [
                        'fila' => $fila,
                        'codigo' => $codigo,
                        'tipo_original' => $row[2],
                        'tipo_normalizado' => $tipo_normalizado
                    ];
                }

                // Buscar cuentas padre faltantes (sample de las primeras problemáticas)
                if (!empty($cuenta_padre) && count($cuentasPadreFaltantes) < 20) {
                    // Buscar si la cuenta padre existe en el archivo
                    $padreExiste = false;
                    foreach ($data as $checkRow) {
                        if (trim((string)$checkRow[0]) === $cuenta_padre) {
                            $padreExiste = true;
                            break;
                        }
                    }

                    if (!$padreExiste) {
                        $cuentasPadreFaltantes[] = [
                            'fila' => $fila,
                            'codigo' => $codigo,
                            'padre_faltante' => $cuenta_padre
                        ];
                    }
                }
            }

            // Reportar resultados
            $this->info("=== CUENTAS RAÍZ PROBLEMÁTICAS (sin padre pero nivel != 1) ===");
            foreach (array_slice($cuentasRaizProblematicas, 0, 10) as $cuenta) {
                $this->info("Fila {$cuenta['fila']}: {$cuenta['codigo']} - {$cuenta['nombre']} (Nivel: {$cuenta['nivel_actual']})");
            }
            if (count($cuentasRaizProblematicas) > 10) {
                $this->info("... y " . (count($cuentasRaizProblematicas) - 10) . " más");
            }

            $this->info("");
            $this->info("=== TIPOS DE CUENTA INVÁLIDOS ===");
            foreach ($tiposInvalidos as $tipo) {
                $this->info("Fila {$tipo['fila']}: {$tipo['codigo']} - Tipo: '{$tipo['tipo_original']}' -> '{$tipo['tipo_normalizado']}'");
            }

            $this->info("");
            $this->info("=== CUENTAS PADRE FALTANTES (muestra) ===");
            foreach ($cuentasPadreFaltantes as $cuenta) {
                $this->info("Fila {$cuenta['fila']}: {$cuenta['codigo']} busca padre {$cuenta['padre_faltante']}");
            }

            $this->info("");
            $this->info("RESUMEN:");
            $this->info("- Cuentas raíz problemáticas: " . count($cuentasRaizProblematicas));
            $this->info("- Tipos inválidos: " . count($tiposInvalidos));
            $this->info("- Cuentas padre faltantes (muestra): " . count($cuentasPadreFaltantes));

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
