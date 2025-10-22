<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Models\Hostname;
use Exception;

class TenantPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant_passwords
                            {--tenant= : Specific tenant UUID to update}
                            {--list : List all tenants and their calculated passwords}
                            {--verify : Verify all tenant database connections}
                            {--create-missing : Create missing database users}
                            {--recreate : Drop and recreate all tenant users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage tenant database user passwords. Synchronizes, verifies and creates tenant database users.';

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
        $specificTenant = $this->option('tenant');
        $listOnly = $this->option('list');
        $verify = $this->option('verify');
        $createMissing = $this->option('create-missing');
        $recreate = $this->option('recreate');

        if ($listOnly) {
            return $this->listTenants();
        }

        if ($verify) {
            return $this->verifyConnections();
        }

        if ($specificTenant) {
            return $this->updateSpecificTenant($specificTenant, $recreate);
        }

        return $this->updateAllTenants($createMissing, $recreate);
    }

    /**
     * List all tenants with their information
     */
    private function listTenants()
    {
        $this->info('🏢 Listing all tenants and their database information:');
        $this->line('');

        $websites = Website::with('hostnames')->get();

        if ($websites->isEmpty()) {
            $this->warn('No tenants found in the system.');
            return;
        }

        $headers = ['ID', 'UUID', 'Full Hostname(s)', 'Base Hostname', 'Database User', 'Calculated Password', 'Created'];
        $rows = [];

        foreach ($websites as $website) {
            $password = $this->calculatePassword($website);
            $hostnames = $website->hostnames->pluck('fqdn')->implode(', ') ?: 'No hostname assigned';
            $baseHostname = $this->extractBaseHostname($website);

            $rows[] = [
                $website->id,
                $website->uuid,
                $hostnames,
                $baseHostname,
                $website->uuid,
                $password,
                $website->created_at->format('Y-m-d H:i:s')
            ];
        }

        $this->table($headers, $rows);
        $this->info("Total tenants: " . count($rows));
    }

    /**
     * Verify database connections for all tenants
     */
    private function verifyConnections()
    {
        $this->info('🔍 Verifying tenant database connections:');
        $this->line('');

        $websites = Website::all();
        $successCount = 0;
        $errorCount = 0;

        foreach ($websites as $website) {
            $password = $this->calculatePassword($website);
            $baseHostname = $this->extractBaseHostname($website);
            $fullHostname = $website->hostnames->first() ? $website->hostnames->first()->fqdn : 'No hostname';

            try {
                // Try to connect to the tenant database
                $connection = config('database.connections.system');
                $connection['database'] = $website->uuid;
                $connection['username'] = $website->uuid;
                $connection['password'] = $password;

                config(['database.connections.temp_tenant' => $connection]);

                $pdo = DB::connection('temp_tenant')->getPdo();

                $this->line("✅ {$website->uuid}: Connection successful");
                $this->line("   Full hostname: {$fullHostname}");
                $this->line("   Base hostname: {$baseHostname}");
                $successCount++;

            } catch (Exception $e) {
                $this->error("❌ {$website->uuid}: Connection failed - " . $e->getMessage());
                $this->line("   Full hostname: {$fullHostname}");
                $this->line("   Base hostname: {$baseHostname}");
                $errorCount++;
            }
        }

        $this->line('');
        $this->info("✅ Successful connections: {$successCount}");
        if ($errorCount > 0) {
            $this->error("❌ Failed connections: {$errorCount}");
        }
    }

    /**
     * Update a specific tenant's password
     */
    private function updateSpecificTenant($tenantUuid, $recreate = false)
    {
        $website = Website::where('uuid', $tenantUuid)->first();

        if (!$website) {
            $this->error("Tenant '{$tenantUuid}' not found.");
            return 1;
        }

        $hostname = $website->hostnames->first();
        $hostnameText = $hostname ? $hostname->fqdn : 'No hostname assigned';
        $baseHostname = $this->extractBaseHostname($website);

        $this->info("🎯 Updating password for tenant: {$website->uuid}");
        $this->line("   Full hostname: {$hostnameText}");
        $this->line("   Base hostname: {$baseHostname}");
        $this->line("   Created: {$website->created_at}");
        $this->line('');

        if ($this->updateTenantPassword($website, false, $recreate)) {
            $this->info("✅ Password updated successfully for tenant: {$website->uuid}");
            return 0;
        } else {
            $this->error("❌ Failed to update password for tenant: {$website->uuid}");
            return 1;
        }
    }

    /**
     * Update all tenants' passwords
     */
    private function updateAllTenants($createMissing = false, $recreate = false)
    {
        $websites = Website::all();

        if ($websites->isEmpty()) {
            $this->warn('No tenants found in the system.');
            return;
        }

        $this->info('🔄 Updating passwords for all tenants:');
        $this->line('');

        $successCount = 0;
        $errorCount = 0;
        $createdCount = 0;

        foreach ($websites as $website) {
            $this->line("Processing tenant: {$website->uuid}");

            $result = $this->updateTenantPassword($website, $createMissing, $recreate);

            if ($result === 'created') {
                $this->info("  ✅ User created and password set");
                $createdCount++;
                $successCount++;
            } elseif ($result === true) {
                $this->info("  ✅ Password updated");
                $successCount++;
            } else {
                $this->error("  ❌ Failed to update password");
                $errorCount++;
            }
        }

        $this->line('');
        $this->info("📊 Summary:");
        $this->info("   ✅ Successful updates: {$successCount}");
        if ($createdCount > 0) {
            $this->info("   🆕 Users created: {$createdCount}");
        }
        if ($errorCount > 0) {
            $this->error("   ❌ Failed updates: {$errorCount}");
        }
    }

    /**
     * Update password for a specific tenant
     */
    private function updateTenantPassword($website, $createMissing = false, $recreate = false)
    {
        $password = $this->calculatePassword($website);
        $username = $website->uuid;
        $updated = false;

        // First, check which users exist
        $existingUsers = $this->checkExistingUsers($username);

        if (empty($existingUsers)) {
            $this->error("     No users found for {$username}");
            if (!$createMissing && !$recreate) {
                return false;
            }
        }

        // If recreate option is enabled, drop existing users first
        if ($recreate && !empty($existingUsers)) {
            foreach ($existingUsers as $host) {
                try {
                    $dropQuery = "DROP USER `{$username}`@`{$host}`";
                    DB::update($dropQuery);
                    $this->line("     🗑️ Dropped user {$username}@{$host}");
                } catch (\Exception $e) {
                    $this->error("     ❌ Error dropping {$username}@{$host}: " . $e->getMessage());
                }
            }
            // After dropping, we need to create users
            $createMissing = true;
            $existingUsers = []; // Reset existing users since we dropped them
        }

        // Try to update password for localhost first
        if (in_array('localhost', $existingUsers)) {
            try {
                $query = "ALTER USER `{$username}`@`localhost` IDENTIFIED BY '{$password}'";
                DB::update($query);
                $updated = true;
                $this->line("     ✅ Updated password for {$username}@localhost");
            } catch (\Illuminate\Database\QueryException $e) {
                $this->error("     ❌ Error updating {$username}@localhost: " . $e->getMessage());
            }
        } elseif ($createMissing) {
            try {
                $createQuery = "CREATE USER `{$username}`@`localhost` IDENTIFIED BY '{$password}'";
                DB::update($createQuery);

                // Grant privileges
                $grantQuery = "GRANT ALL PRIVILEGES ON `{$username}`.* TO `{$username}`@`localhost`";
                DB::update($grantQuery);

                $updated = true;
                $this->line("     ✅ Created user {$username}@localhost");
            } catch (\Illuminate\Database\QueryException $createException) {
                $this->error("     ❌ Error creating {$username}@localhost: " . $createException->getMessage());
            }
        }

        // Try to update password for % host
        if (in_array('%', $existingUsers)) {
            try {
                $query = "ALTER USER `{$username}`@`%` IDENTIFIED BY '{$password}'";
                DB::update($query);
                $updated = true;
                $this->line("     ✅ Updated password for {$username}@%");
            } catch (\Illuminate\Database\QueryException $e) {
                $this->error("     ❌ Error updating {$username}@%: " . $e->getMessage());
            }
        } elseif ($createMissing) {
            try {
                $createQuery = "CREATE USER `{$username}`@`%` IDENTIFIED BY '{$password}'";
                DB::update($createQuery);

                // Grant privileges
                $grantQuery = "GRANT ALL PRIVILEGES ON `{$username}`.* TO `{$username}`@`%`";
                DB::update($grantQuery);

                $updated = true;
                $this->line("     ✅ Created user {$username}@%");
            } catch (\Illuminate\Database\QueryException $createException) {
                $this->error("     ❌ Error creating {$username}@%: " . $createException->getMessage());
            }
        }

        if ($updated) {
            try {
                DB::update("FLUSH PRIVILEGES");
                return $createMissing ? 'created' : true;
            } catch (\Exception $e) {
                // Ignore flush privileges error
                return $createMissing ? 'created' : true;
            }
        }

        return false;
    }

    /**
     * Check which users exist for a given username
     */
    private function checkExistingUsers($username)
    {
        try {
            $result = DB::select("SELECT User, Host FROM mysql.user WHERE User = ?", [$username]);
            return collect($result)->pluck('Host')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract the base hostname from APP_URL_BASE or from tenant hostname
     */
    private function extractBaseHostname($website)
    {
        // First, try to get the base hostname from APP_URL_BASE config
        $baseHostname = env('APP_URL_BASE');

        if ($baseHostname) {
            return $baseHostname;
        }

        // Fallback: extract from the first hostname if available
        $hostname = $website->hostnames->first();
        if ($hostname) {
            // Remove any subdomain prefix (e.g., torres.facturadorpro2.oo -> facturadorpro2.oo)
            $fqdn = $hostname->fqdn;
            $parts = explode('.', $fqdn);

            // If we have at least 3 parts (subdomain.domain.tld), remove the first part
            if (count($parts) >= 3) {
                array_shift($parts); // Remove first part (subdomain)
                return implode('.', $parts);
            }

            return $fqdn; // Return as is if no subdomain detected
        }

        return 'Unknown';
    }

    /**
     * Calculate the correct password for a tenant
     */
    private function calculatePassword($website)
    {
        return md5(sprintf(
            '%s.%d',
            config('app.key'),
            $website->id
        ));
    }
}
