<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Models\Hostname;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:cleanup-orphaned {--dry-run : Solo mostrar lo que se haría sin ejecutar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar websites huérfanos sin hostname asociado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Buscando websites huérfanos...');
        
        $orphanedWebsites = Website::whereDoesntHave('hostnames')->get();
        
        if ($orphanedWebsites->count() === 0) {
            $this->info('✅ No se encontraron websites huérfanos');
            return 0;
        }
        
        $this->warn("⚠️  Encontrados {$orphanedWebsites->count()} websites huérfanos:");
        
        $headers = ['ID', 'UUID', 'Base de Datos', 'Acción'];
        $rows = [];
        
        foreach ($orphanedWebsites as $website) {
            $tenantName = str_replace(env('PREFIX_DATABASE', 'tenancy') . '_', '', $website->uuid);
            $tenantDatabase = env('PREFIX_DATABASE', 'tenancy') . '_' . $tenantName;
            
            // Verificar si existe la base de datos
            $databases = DB::select("SHOW DATABASES LIKE '{$tenantDatabase}'");
            $dbExists = !empty($databases) ? 'Existe' : 'No existe';
            
            $action = $dryRun ? 'Marcaría para eliminar' : 'Eliminando...';
            
            $rows[] = [
                $website->id,
                $website->uuid,
                $tenantDatabase . ' (' . $dbExists . ')',
                $action
            ];
        }
        
        $this->table($headers, $rows);
        
        if ($dryRun) {
            $this->info('🔍 Modo dry-run: No se realizaron cambios');
            $this->info('💡 Ejecuta sin --dry-run para realizar la limpieza');
            return 0;
        }
        
        if (!$this->confirm('¿Proceder con la limpieza?')) {
            $this->info('❌ Operación cancelada');
            return 0;
        }
        
        $cleaned = 0;
        
        foreach ($orphanedWebsites as $website) {
            try {
                $tenantName = str_replace(env('PREFIX_DATABASE', 'tenancy') . '_', '', $website->uuid);
                $tenantDatabase = env('PREFIX_DATABASE', 'tenancy') . '_' . $tenantName;
                
                // Intentar eliminar base de datos si existe
                $databases = DB::select("SHOW DATABASES LIKE '{$tenantDatabase}'");
                if (!empty($databases)) {
                    DB::statement("DROP DATABASE IF EXISTS `{$tenantDatabase}`");
                    $this->info("  🗑️  Base de datos eliminada: {$tenantDatabase}");
                }
                
                // Eliminar website
                $website->delete();
                $this->info("  ✅ Website eliminado: {$website->uuid}");
                
                $cleaned++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error al limpiar {$website->uuid}: " . $e->getMessage());
            }
        }
        
        $this->info("🎉 Limpieza completada: {$cleaned} websites huérfanos eliminados");
        
        return 0;
    }
}