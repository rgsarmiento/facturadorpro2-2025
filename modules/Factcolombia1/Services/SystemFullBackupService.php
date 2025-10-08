<?php

namespace Modules\Factcolombia1\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Hyn\Tenancy\Models\Website;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SystemFullBackupService
{
    // Tipos removidos para compatibilidad con versiones PHP < 7.4
    private $id;
    private $progressFile;
    private $basePath;
    private $data;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->basePath = storage_path('app/system_backups');
        if (!is_dir($this->basePath)) mkdir($this->basePath, 0755, true);
        $this->progressFile = $this->basePath.'/progress_'.$this->id.'.json';
        $this->initProgress();
    }

    private function initProgress(): void
    {
        $this->data = [
            'id' => $this->id,
            'status' => 'running',
            'step' => 'init',
            'message' => 'Iniciando backup',
            'started_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'current' => 0,
            'successful' => 0,
            'total_tenants' => Website::count(),
            'log' => [],
            'filename' => null,
            'errors' => [],
        ];
        $this->persist();
    }

    private function persist(): void
    {
        $this->data['updated_at'] = now()->toDateTimeString();
        file_put_contents($this->progressFile, json_encode($this->data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    private function log(string $msg): void
    {
        $this->data['log'][] = [ 'ts' => now()->format('H:i:s'), 'msg' => $msg ];
        if (count($this->data['log']) > 500) {
            $this->data['log'] = array_slice($this->data['log'], -500);
        }
        \Log::info('ASYNC BACKUP: '.$msg);
        $this->persist();
    }

    public function getStatus(): string
    {
        return $this->data['status'] ?? 'unknown';
    }

    public function run(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $workingDir = $this->basePath.'/temp_system_backup_'.$timestamp.'_'.$this->id;
        try {
            mkdir($workingDir, 0755, true);
            $this->log('Directorio temporal: '.$workingDir);
            $conn = config('database.default');
            $host = config("database.connections.{$conn}.host");
            $user = config("database.connections.{$conn}.username");
            $pass = config("database.connections.{$conn}.password");
            $port = config("database.connections.{$conn}.port", 3306);
            $systemDb = config("database.connections.{$conn}.database");

            // Paso 1: BD sistema
            $this->data['step'] = 'system_db';
            $this->data['message'] = 'Respaldando base de datos del sistema';
            $this->persist();
            $this->dumpDatabase($workingDir.'/system_database.sql', $host, $port, $user, $pass, $systemDb, 600);
            $this->log('BD sistema completada');

            // Paso 2: Tenants
            $this->data['step'] = 'tenants';
            $this->data['message'] = 'Respaldando bases de datos de tenants';
            $this->persist();
            $this->dumpTenants($workingDir, $host, $port, $user, $pass);

            // Paso 3: carpetas
            $this->data['step'] = 'folders';
            $this->data['message'] = 'Respaldando carpetas storage/public';
            $this->persist();
            $this->backupFolders($workingDir);
            $this->log('Carpetas respaldadas');

            // Paso 4: zip
            $this->data['step'] = 'zip';
            $this->data['message'] = 'Creando archivo ZIP final';
            $this->persist();
            $zipFilename = 'system_backup_'.$timestamp.'_'.$this->id.'.zip';
            $this->createZip($workingDir, $this->basePath.'/'.$zipFilename);
            $this->data['filename'] = $zipFilename;
            $this->log('ZIP creado: '.$zipFilename);

            // Paso 5: limpieza
            $this->data['step'] = 'cleanup';
            $this->data['message'] = 'Limpiando temporales';
            $this->persist();
            $this->deleteDirectory($workingDir);

            $this->data['status'] = 'done';
            $this->data['message'] = 'Backup completado';
            $this->persist();
        } catch (\Throwable $e) {
            $this->data['status'] = 'failed';
            $this->data['message'] = 'Fallo: '.$e->getMessage();
            $this->data['errors'][] = $e->getMessage();
            $this->persist();
            \Log::error('ASYNC BACKUP ERROR: '.$e->getMessage());
            if (isset($workingDir) && is_dir($workingDir)) {
                $this->deleteDirectory($workingDir);
            }
        }
    }

    private function dumpDatabase(string $filePath, $host,$port,$user,$pass,$db,$timeout)
    {
        $cmd = [ 'mysqldump', '--host='.$host,'--port='.$port,'--user='.$user,'--password='.$pass,'--single-transaction','--routines','--triggers','--add-drop-table','--disable-keys','--extended-insert','--no-autocommit',$db ];
        $p = new Process($cmd);
        $p->setTimeout($timeout);
        $p->run();
        if(!$p->isSuccessful()) throw new ProcessFailedException($p);
        file_put_contents($filePath, $p->getOutput());
    }

    private function dumpTenants(string $workingDir,$host,$port,$user,$pass)
    {
        $tenantsDir = $workingDir.'/tenants';
        mkdir($tenantsDir,0755,true);
        $websites = Website::all();
        $prefix = env('PREFIX_DATABASE','tenancy');
        $total = $websites->count();
        $batchSize = 10;
        $batchNumber = 1;
        foreach($websites->chunk($batchSize) as $batch){
            $this->log("Procesando lote {$batchNumber} (".$batch->count()." tenants)");
            foreach($batch as $website){
                $hostname = $website->hostnames()->first();
                if(!$hostname){
                    $this->log('Tenant sin hostname, omitido');
                    continue;
                }
                $tenantName = explode('.', $hostname->fqdn)[0];
                $tenantDb = $prefix.'_'.$tenantName;
                try {
                    $exists = DB::connection('mysql')->select("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?",[$tenantDb]);
                    if(empty($exists)) { $this->log("DB {$tenantDb} no existe, omitida"); continue; }
                } catch(\Exception $ex){ $this->log('Error verificando '.$tenantDb.': '.$ex->getMessage()); continue; }

                $this->data['current'] += 1; $this->persist();
                $this->log('Dump tenant: '.$tenantName);
                $fname = $tenantsDir.'/tenant_'.$tenantName.'.sql';
                try {
                    $this->dumpDatabase($fname,$host,$port,$user,$pass,$tenantDb,600);
                    $this->data['successful'] += 1; $this->persist();
                } catch(\Throwable $ex){
                    $this->log('Error dump '.$tenantName.': '.$ex->getMessage());
                    $this->data['errors'][] = 'tenant '.$tenantName.': '.$ex->getMessage();
                    $this->persist();
                }
            }
            $batchNumber++;
        }
    }

    private function backupFolders(string $workingDir)
    {
        // Para compatibilidad con el proceso de RESTORE existente debemos replicar la estructura
        // creada por el backup síncrono: <root>/folders/storage y <root>/folders/public
        $foldersDir = $workingDir.'/folders';
        @mkdir($foldersDir,0755,true);

        // 1. STORAGE completo (excluyendo logs, cache, sessions, views y system_backups para evitar recursión)
        $storageSource = storage_path();
        $storageDest   = $foldersDir.'/storage';
        $this->log('Copiando carpeta storage (compatibilidad restore)');
        $this->copyDirectorySelective($storageSource, $storageDest, [
            'logs',
            'framework/cache',
            'framework/sessions',
            'framework/views',
            'app/system_backups'
        ]);

        // 2. PUBLIC (excluyendo archivos temporales típicos)
        $publicSource = public_path();
        $publicDest   = $foldersDir.'/public';
        $this->log('Copiando carpeta public (compatibilidad restore)');
        $this->copyDirectorySelective($publicSource, $publicDest, [
            'hot',
            'mix-manifest.json'
        ]);
    }

    private function recursiveCopy($src,$dst,array $excludeDirs=[]){
        if(!is_dir($src)) return; $dir = opendir($src); @mkdir($dst,0755,true);
        while(false !== ($file = readdir($dir))){
            if($file == '.' || $file=='..') continue;
            $full = $src.'/'.$file;
            if(is_dir($full)){
                if(in_array($file,$excludeDirs)) continue;
                $this->recursiveCopy($full,$dst.'/'.$file,$excludeDirs);
            } else {
                @copy($full,$dst.'/'.$file);
            }
        }
        closedir($dir);
    }

    private function createZip(string $sourceDir, string $zipPath)
    {
        $zip = new \ZipArchive();
        if($zip->open($zipPath, \ZipArchive::CREATE|\ZipArchive::OVERWRITE)!==true){
            throw new \RuntimeException('No se pudo crear ZIP');
        }
        $sourceDir = realpath($sourceDir);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS));
        foreach($files as $file){
            if(!$file->isDir()){
                $filePath = $file->getRealPath();
                $relative = substr($filePath, strlen($sourceDir)+1);
                $zip->addFile($filePath,$relative);
            }
        }
        $zip->close();
    }

    private function deleteDirectory($dir){
        if(!is_dir($dir)) return; $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file){
            $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
        }
        @rmdir($dir);
    }

    // Copiado selectivo similar al usado en el controlador síncrono para mantener compatibilidad de RESTORE
    private function copyDirectorySelective($source, $destination, array $excludePatterns = [])
    {
        if (!is_dir($source)) return false;
        if (!is_dir($destination)) @mkdir($destination, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);

            $exclude = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($relativePath, $pattern) === 0) { $exclude = true; break; }
            }
            if ($exclude) continue;

            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;
            if ($item->isDir()) {
                if (!is_dir($targetPath)) @mkdir($targetPath, 0755, true);
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
                @copy($item->getPathname(), $targetPath);
            }
        }
        return true;
    }
}
