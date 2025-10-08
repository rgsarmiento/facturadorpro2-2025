<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Factcolombia1\Services\SystemFullBackupService;

class SystemFullBackupCommand extends Command
{
    protected $signature = 'system:full-backup {id}';
    protected $description = 'Ejecuta un backup completo del sistema de forma asincrónica usando un ID de seguimiento';

    public function handle()
    {
        $id = $this->argument('id');
        $service = new SystemFullBackupService($id);
        $service->run();
        $this->info('Backup finalizado con estado: '.$service->getStatus());
        return 0;
    }
}
