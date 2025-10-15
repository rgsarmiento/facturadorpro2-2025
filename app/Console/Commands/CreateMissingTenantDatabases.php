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

        foreach ($missingDatabases as $website) {
            $databaseName = $website->uuid;
            $hostname = $website->hostnames->first();
            $hostnameText = $hostname ? $hostname->fqdn : 'No hostname';

            $this->line("Processing: {$databaseName} (Hostname: {$hostnameText})");

            try {
                // Create database
                DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->info("  ✅ Database created: {$databaseName}");

                // Run migrations for this tenant
                try {
                    $this->line("  🔄 Running migrations for {$databaseName}...");

                    // Switch to tenant context and run migrations
                    $tenancy = app(\Hyn\Tenancy\Environment::class);
                    $tenancy->tenant($website);

                    Artisan::call('tenancy:migrate', [
                        '--website_id' => $website->id
                    ]);

                    $this->info("  ✅ Migrations completed for {$databaseName}");
                    $successCount++;

                } catch (\Exception $migrationError) {
                    $this->error("  ⚠️ Database created but migrations failed: " . $migrationError->getMessage());
                    $this->line("  💡 You can manually run: php artisan tenancy:migrate --website_id={$website->id}");
                    $successCount++; // Count as success since DB was created
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Error creating database: " . $e->getMessage());
                $errorCount++;
            }

            $this->line('');
        }

        $this->info("📊 Final Summary:");
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
}
