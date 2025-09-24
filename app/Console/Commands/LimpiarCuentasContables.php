<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\CuentaContable;

class LimpiarCuentasContables extends Command
{
    protected $signature = 'limpiar:cuentas-contables {--confirm}';
    protected $description = 'Limpiar todas las cuentas contables para pruebas';

    public function handle()
    {
        if (!$this->option('confirm')) {
            $this->warn('Este comando eliminará TODAS las cuentas contables.');
            $this->warn('Para confirmar, ejecuta: php artisan limpiar:cuentas-contables --confirm');
            return;
        }

        $this->info('Limpiando cuentas contables...');

        try {
            // Contar cuentas antes de eliminar
            $totalAntes = CuentaContable::on('tenant')->count();

            // Eliminar todas las cuentas
            CuentaContable::on('tenant')->delete();

            $this->info("✅ Eliminadas $totalAntes cuentas contables");

        } catch (\Exception $e) {
            $this->error('Error al limpiar cuentas: ' . $e->getMessage());
        }
    }
}
