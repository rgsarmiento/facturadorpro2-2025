<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PucTemplateExport;

class UpdatePlanDeCuentas extends Command
{
    protected $signature = 'update:plan-cuentas';
    protected $description = 'Update Plan de Cuentas Inicial.xlsx with correct headers';

    public function handle()
    {
        $originalPath = base_path('Plan de Cuentas Inicial.xlsx');
        $backupPath = base_path('Plan de Cuentas Inicial_backup.xlsx');

        if (!file_exists($originalPath)) {
            $this->error("File not found: $originalPath");
            return;
        }

        try {
            // Backup original
            copy($originalPath, $backupPath);
            $this->info("Backup created: $backupPath");

            // Read original data
            $data = Excel::toArray([], $originalPath)[0];

            // Skip headers row
            array_shift($data);

            // Convert data to correct format
            $convertedData = [];
            foreach ($data as $row) {
                if (empty(array_filter($row))) continue;

                $convertedData[] = [
                    trim((string)($row[0] ?? '')), // codigo
                    trim((string)($row[1] ?? '')), // nombre
                    strtolower(trim((string)($row[2] ?? ''))), // tipo_cuenta
                    strtolower(trim((string)($row[3] ?? ''))), // naturaleza
                    intval($row[4] ?? 0), // nivel
                    trim((string)($row[5] ?? '')), // cuenta_padre_codigo (era cuenta_padre_id)
                    trim((string)($row[6] ?? '')), // descripcion
                    boolval($row[7] ?? 1), // activa
                    boolval($row[8] ?? 1), // permite_movimiento
                    boolval($row[9] ?? 0)  // requiere_tercero
                ];
            }

            // Create new export with correct headers
            $export = new class($convertedData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $data;

                public function __construct($data) {
                    $this->data = $data;
                }

                public function array(): array {
                    return $this->data;
                }

                public function headings(): array {
                    return [
                        'codigo',
                        'nombre',
                        'tipo_cuenta',
                        'naturaleza',
                        'nivel',
                        'cuenta_padre_codigo',
                        'descripcion',
                        'activa',
                        'permite_movimiento',
                        'requiere_tercero'
                    ];
                }
            };

            // Save updated file
            Excel::store($export, 'Plan de Cuentas Inicial.xlsx', 'local');

            // Move from storage to root
            $storagePath = storage_path('app/Plan de Cuentas Inicial.xlsx');
            if (file_exists($storagePath)) {
                rename($storagePath, $originalPath);
                $this->info("File updated successfully: $originalPath");
                $this->info("Total records processed: " . count($convertedData));
            } else {
                $this->error("Failed to create updated file");
            }

        } catch (\Exception $e) {
            $this->error("Error updating file: " . $e->getMessage());
        }
    }
}
