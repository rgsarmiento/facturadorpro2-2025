<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\PucInicialExport;

class TestPucInicialExport extends Command
{
    protected $signature = 'test:puc-inicial-export';
    protected $description = 'Test PucInicialExport class';

    public function handle()
    {
        try {
            $this->info("Probando PucInicialExport...");

            $export = new PucInicialExport();

            $headers = $export->headings();
            $data = $export->array();

            $this->info("Headers: " . implode(', ', $headers));
            $this->info("Total de filas de datos: " . count($data));

            if (!empty($data)) {
                $this->info("Primera fila de datos: " . implode(' | ', $data[0]));
                $this->info("Última fila de datos: " . implode(' | ', $data[count($data) - 1]));
            }

            $this->info("¡Export funcionando correctamente!");

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
