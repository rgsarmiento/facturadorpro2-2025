<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class AnalyzePucInicial extends Command
{
    protected $signature = 'analyze:puc-inicial';
    protected $description = 'Analyze PUC_inicial.xlsx file';

    public function handle()
    {
        $filePath = public_path('formats/PUC_inicial.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        try {
            $data = Excel::toArray([], $filePath)[0];

            $this->info("Contenido del archivo PUC_inicial.xlsx:");
            $this->info("");

            // Mostrar los primeros 15 registros
            for ($i = 0; $i < min(15, count($data)); $i++) {
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
