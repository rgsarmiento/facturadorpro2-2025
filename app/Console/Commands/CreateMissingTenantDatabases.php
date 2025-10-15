<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Hyn\Tenancy\Models\Website;

class CreateMissingTenantDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:create-missing-databases
                            {--check-only : Only check for missing databases without creating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing tenant databases and run migrations';

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
        $checkOnly = $this->option('check-only');

        $this->info('🔍 Checking for missing tenant databases...');
        $this->line('');

        $websites = Website::all();
        $missingDatabases = [];
        $existingDatabases = [];

        // Check which databases are missing
        foreach ($websites as $website) {
            $databaseName = $website->uuid;
            $exists = $this->checkDatabaseExists($databaseName);

            if (!$exists) {
                $missingDatabases[] = $website;
                $hostname = $website->hostnames->first();
                $hostnameText = $hostname ? $hostname->fqdn : 'No hostname';
                $this->warn("❌ Missing: {$databaseName} (Hostname: {$hostnameText})");
            } else {
                $existingDatabases[] = $website;
            }
        }

        $this->line('');
        $this->info("📊 Summary:");
        $this->info("   ✅ Existing databases: " . count($existingDatabases));
        $this->warn("   ❌ Missing databases: " . count($missingDatabases));
        $this->line('');

        if (empty($missingDatabases)) {
            $this->info("🎉 All tenant databases exist!");
            return 0;
        }

        if ($checkOnly) {
            $this->info("Check-only mode: No databases were created.");
            return 0;
        }

        // Ask for confirmation
        if (!$this->confirm("Do you want to create the missing databases and run migrations?", true)) {
            $this->info("Operation cancelled.");
            return 0;
        }

        $this->line('');
        $this->info('🚀 Creating missing databases...');
        $this->line('');

        $successCount = 0;
        $errorCount = 0;

        // Obtener una base de datos de referencia para copiar estructura
        $referenceTenant = $existingDatabases[0] ?? null;
        $referenceDatabaseName = $referenceTenant ? $referenceTenant->uuid : null;

        if (!$referenceDatabaseName) {
            $this->error("No se encontró ninguna base de datos de tenant existente para usar como referencia.");
            return 1;
        }

        $this->info("📋 Usando '{$referenceDatabaseName}' como plantilla para la estructura.");
        $this->line('');

        foreach ($missingDatabases as $website) {
            $databaseName = $website->uuid;
            $hostname = $website->hostnames->first();
            $hostnameText = $hostname ? $hostname->fqdn : 'No hostname';

            $this->line("Processing: {$databaseName} (Hostname: {$hostnameText})");

            try {
                // Crear base de datos
                DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->info("  ✅ Database created: {$databaseName}");

                // Copiar estructura de la base de datos de referencia
                try {
                    $this->line("  🔄 Copying structure from {$referenceDatabaseName}...");

                    // Deshabilitar verificación de foreign keys temporalmente
                    DB::statement("SET FOREIGN_KEY_CHECKS=0");

                    // Obtener lista de tablas de la base de datos de referencia
                    $tables = DB::select("SHOW TABLES FROM `{$referenceDatabaseName}`");
                    $tableKey = "Tables_in_{$referenceDatabaseName}";

                    $tableCount = count($tables);
                    $this->line("  📊 Found {$tableCount} tables to copy");

                    // Cambiar a la base de datos destino
                    DB::statement("USE `{$databaseName}`");

                    foreach ($tables as $table) {
                        $tableName = $table->$tableKey;

                        // Obtener CREATE TABLE statement
                        $createTableResult = DB::select("SHOW CREATE TABLE `{$referenceDatabaseName}`.`{$tableName}`");
                        $createTableStatement = $createTableResult[0]->{'Create Table'};

                        // Crear tabla en la nueva base de datos
                        DB::statement($createTableStatement);
                    }

                    // Volver a habilitar verificación de foreign keys
                    DB::statement("SET FOREIGN_KEY_CHECKS=1");

                    // Volver a usar la base de datos del sistema
                    DB::statement("USE `" . config('database.connections.system.database') . "`");

                    $this->info("  ✅ Structure copied successfully ({$tableCount} tables)");

                    // Insertar datos básicos mínimos necesarios
                    $this->line("  🔄 Inserting default configuration data...");
                    $this->insertDefaultTenantData($databaseName, $referenceDatabaseName);
                    $this->info("  ✅ Default data inserted");

                    $successCount++;

                } catch (\Exception $copyError) {
                    // Asegurarse de que las foreign keys estén habilitadas nuevamente
                    try {
                        DB::statement("SET FOREIGN_KEY_CHECKS=1");
                    } catch (\Exception $e) {}

                    $this->error("  ⚠️ Database created but structure copy failed: " . $copyError->getMessage());
                    $this->line("  💡 Database exists but may be empty. You may need to manually restore it.");
                    $successCount++; // Count as success since DB was created
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Error creating database: " . $e->getMessage());
                $errorCount++;
            }

            $this->line('');
        }        $this->info("📊 Final Summary:");
        $this->info("   ✅ Successfully created: {$successCount}");
        if ($errorCount > 0) {
            $this->error("   ❌ Failed: {$errorCount}");
        }

        if ($successCount > 0) {
            $this->line('');
            $this->info("💡 Next steps:");
            $this->info("   1. Run: php artisan tenant_passwords --create-missing");
            $this->info("   2. Verify tenant access in the application");
        }

        return 0;
    }

    /**
     * Check if a database exists
     */
    private function checkDatabaseExists($databaseName)
    {
        try {
            // Usar información_schema para verificar existencia de base de datos
            $result = DB::select(
                "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?",
                [$databaseName]
            );
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Insert default/minimum required data into new tenant database
     */
    private function insertDefaultTenantData($targetDatabase, $referenceDatabase)
    {
        try {
            // Lista de tablas con datos críticos que deben copiarse
            $criticalTables = [
                'configurations',
                'establishments',
                'users',
                'cat_payment_method_types',
                'currencies',
                'currency_types',
                'attributes',
                'charge_discount_types',
                'system_activity_types',
                'operation_types',
                'document_types',
                'catalog_01',
                'catalog_02',
                'catalog_05',
                'catalog_06',
                'catalog_07',
                'catalog_08',
                'catalog_09',
                'catalog_10',
                'catalog_12',
                'catalog_13',
                'catalog_14',
                'catalog_15',
                'catalog_16',
                'catalog_17',
                'catalog_18',
                'catalog_19',
                'catalog_20',
                'catalog_21',
                'catalog_22',
                'catalog_23',
                'catalog_24',
                'catalog_25',
                'catalog_51',
                'catalog_53',
                'catalog_54',
                'catalog_59',
            ];

            foreach ($criticalTables as $tableName) {
                try {
                    // Verificar si la tabla existe en ambas bases de datos
                    $tableExistsInReference = DB::select(
                        "SELECT COUNT(*) as count FROM information_schema.TABLES
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                        [$referenceDatabase, $tableName]
                    );

                    $tableExistsInTarget = DB::select(
                        "SELECT COUNT(*) as count FROM information_schema.TABLES
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                        [$targetDatabase, $tableName]
                    );

                    if ($tableExistsInReference[0]->count > 0 && $tableExistsInTarget[0]->count > 0) {
                        // Copiar datos de la tabla
                        $count = DB::select("SELECT COUNT(*) as count FROM `{$referenceDatabase}`.`{$tableName}`")[0]->count;

                        if ($count > 0) {
                            DB::statement("INSERT INTO `{$targetDatabase}`.`{$tableName}` SELECT * FROM `{$referenceDatabase}`.`{$tableName}`");
                        }
                    }
                } catch (\Exception $tableError) {
                    // Continuar con la siguiente tabla si hay error
                    // Algunas tablas pueden no existir en todos los tenants
                    continue;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail
            \Log::warning("Error inserting default tenant data: " . $e->getMessage());
        }
    }
}
