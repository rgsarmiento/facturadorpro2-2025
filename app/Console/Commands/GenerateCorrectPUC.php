<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\PucTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class GenerateCorrectPUC extends Command
{
    protected $signature = 'generate:correct-puc';
    protected $description = 'Generate correct PUC template file';

    public function handle()
    {
        try {
            $this->info("Iniciando generación de plantilla PUC...");

            $export = new PucTemplateExport();
            $filename = 'Plan de Cuentas Inicial.xlsx';

            $this->info("Generando archivo Excel...");

            // Store in local disk (storage/app) with explicit writer
            Excel::store($export, $filename, 'local', \Maatwebsite\Excel\Excel::XLSX);

            $this->info("Archivo generado en storage/app/$filename");

            // Move to base path
            $from = storage_path('app/' . $filename);
            $to = base_path($filename);

            $this->info("Moviendo archivo de $from a $to");

            if (file_exists($from)) {
                if (file_exists($to)) {
                    // Backup existing
                    $backupName = 'Plan de Cuentas Inicial_backup_' . date('Y-m-d-H-i-s') . '.xlsx';
                    rename($to, base_path($backupName));
                    $this->info("Backup creado: $backupName");
                }

                if (rename($from, $to)) {
                    $this->info("Nueva plantilla PUC creada exitosamente: $to");
                } else {
                    $this->error("Error al mover el archivo de $from a $to");
                }
            } else {
                $this->error("El archivo no se generó en: $from");
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
