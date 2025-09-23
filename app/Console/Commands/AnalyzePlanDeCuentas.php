<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class AnalyzePlanDeCuentas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:plan-cuentas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze Plan de Cuentas Inicial.xlsx file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $filePath = base_path('Plan de Cuentas Inicial.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        try {
            $data = Excel::toArray([], $filePath)[0];

            $this->info("Contenido del archivo Plan de Cuentas Inicial.xlsx:");
            $this->info("");

            // Mostrar los primeros 10 registros
            for ($i = 0; $i < min(10, count($data)); $i++) {
                $this->info("Fila " . ($i + 1) . ": " . implode(' | ', array_map('strval', $data[$i])));
            }

            $this->info("");
            $this->info("Total de filas: " . count($data));
            $this->info("Número de columnas: " . count($data[0] ?? []));

            if (isset($data[0])) {
                $this->info("");
                $this->info("Headers detectados:");
                foreach ($data[0] as $index => $header) {
                    $this->info("Columna $index: '$header'");
                }
            }

        } catch (\Exception $e) {
            $this->error("Error reading file: " . $e->getMessage());
        }
    }
}
