<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Environment;
use Illuminate\Support\Facades\Artisan;

class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate {uuid? : UUID del tenant (sin el prefijo)} {--seed : Run seeders after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta migraciones pendientes en un tenant específico o todos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $uuid = $this->argument('uuid');
        $shouldSeed = $this->option('seed');

        if ($uuid) {
            // Ejecutar para un tenant específico
            $fullUuid = config('tenant.prefix_database') . '_' . $uuid;
            $website = Website::where('uuid', $fullUuid)->first();

            if (!$website) {
                $this->error("No se encontró tenant con UUID: {$fullUuid}");
                $this->info("Tenants disponibles:");
                Website::all()->each(function ($w) {
                    $this->line("  - {$w->uuid}");
                });
                return 1;
            }

            $this->info("Ejecutando migraciones para tenant: {$fullUuid}");
            $this->migrateTenant($website, $shouldSeed);
        } else {
            // Ejecutar para todos los tenants
            $websites = Website::all();

            if ($websites->isEmpty()) {
                $this->error("No hay tenants registrados.");
                return 1;
            }

            $this->info("Ejecutando migraciones para {$websites->count()} tenant(s)...");

            foreach ($websites as $website) {
                $this->info("\nProcesando: {$website->uuid}");
                $this->migrateTenant($website, $shouldSeed);
            }
        }

        $this->info("\n✓ Migraciones completadas");
        return 0;
    }

    /**
     * Ejecuta las migraciones para un tenant específico
     *
     * @param Website $website
     * @param bool $shouldSeed
     * @return void
     */
    protected function migrateTenant(Website $website, $shouldSeed = false)
    {
        $tenancy = app(Environment::class);

        try {
            // Cambiar al tenant
            $tenancy->tenant($website);

            $this->line("  → Verificando conexión...");

            // Verificar que la base de datos existe
            $connection = app('db')->connection('tenant');
            $dbName = $connection->getDatabaseName();

            $this->line("  → Base de datos: {$dbName}");

            // Ejecutar migraciones usando el comando de Hyn Tenancy
            $this->line("  → Ejecutando migraciones...");

            $exitCode = Artisan::call('tenancy:migrate', [
                '--website_id' => $website->id
            ]);

            // Obtener output del comando
            $output = Artisan::output();

            if (trim($output)) {
                foreach (explode("\n", trim($output)) as $line) {
                    if ($line) {
                        $this->line("    {$line}");
                    }
                }
            }

            if ($exitCode === 0) {
                $this->info("  ✓ Migraciones ejecutadas correctamente");
            } else {
                $this->error("  ✗ Error al ejecutar migraciones (código: {$exitCode})");
            }

            // Ejecutar seeders si se solicitó
            if ($shouldSeed) {
                $this->line("  → Ejecutando seeders...");

                Artisan::call('db:seed', [
                    '--class' => 'TenancyDatabaseSeeder',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                $seedOutput = Artisan::output();
                if (trim($seedOutput)) {
                    foreach (explode("\n", trim($seedOutput)) as $line) {
                        if ($line) {
                            $this->line("    {$line}");
                        }
                    }
                }

                $this->info("  ✓ Seeders ejecutados");
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Error: " . $e->getMessage());
            $this->line("  Trace: " . $e->getTraceAsString());
            \Log::error("Error migrando tenant {$website->uuid}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            // Volver a la configuración del sistema
            config(['database.default' => 'system']);
        }
    }
}
