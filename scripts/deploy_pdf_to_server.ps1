# ===============================================================================
# Script de Despliegue Automático - Facturador PRO2 - Reportes PDF
# Para ejecutar desde Windows (PowerShell)
# Fecha: 16 de Octubre, 2025
#
# Uso:
#   .\deploy_pdf_to_server.ps1 -Server "IP" -User "usuario" -RemotePath "/var/www/html"
#
# Ejemplos:
#   .\deploy_pdf_to_server.ps1 -Server "192.168.1.100" -User "root"
#   .\deploy_pdf_to_server.ps1 -Server "miservidor.com" -User "admin" -RemotePath "/var/www/html"
# ===============================================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$Server,

    [Parameter(Mandatory=$true)]
    [string]$User,

    [Parameter(Mandatory=$false)]
    [string]$RemotePath = "/var/www/html",

    [Parameter(Mandatory=$false)]
    [switch]$AutoConfigure
)

# Colores para output
function Write-Success { Write-Host "✓ $args" -ForegroundColor Green }
function Write-Error-Custom { Write-Host "✗ $args" -ForegroundColor Red }
function Write-Info { Write-Host "ℹ $args" -ForegroundColor Cyan }
function Write-Warning-Custom { Write-Host "⚠ $args" -ForegroundColor Yellow }
function Write-Header {
    Write-Host "`n========================================" -ForegroundColor Blue
    Write-Host "$args" -ForegroundColor Blue
    Write-Host "========================================`n" -ForegroundColor Blue
}

Write-Header "Despliegue de Archivos - Reportes PDF"
Write-Info "Servidor: $Server"
Write-Info "Usuario: $User"
Write-Info "Ruta destino: $RemotePath"

# Obtener ruta del proyecto (asumimos que el script está en /scripts/)
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

Write-Info "Ruta del proyecto local: $ProjectRoot"

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "$ProjectRoot\artisan")) {
    Write-Error-Custom "No se encuentra el archivo artisan. ¿Estás en el directorio correcto del proyecto?"
    exit 1
}

# Verificar que plink/pscp estén disponibles (PuTTY tools)
$pscpPath = "pscp"
$plinkPath = "plink"

# Intentar encontrar pscp y plink
try {
    $null = Get-Command pscp -ErrorAction Stop
    $null = Get-Command plink -ErrorAction Stop
} catch {
    Write-Warning-Custom "pscp o plink no encontrados en el PATH"
    Write-Info "Buscando en ubicaciones comunes..."

    $commonPaths = @(
        "C:\Program Files\PuTTY\pscp.exe",
        "C:\Program Files (x86)\PuTTY\pscp.exe",
        "$env:USERPROFILE\Downloads\pscp.exe"
    )

    $found = $false
    foreach ($path in $commonPaths) {
        if (Test-Path $path) {
            $puttyDir = Split-Path -Parent $path
            $env:Path += ";$puttyDir"
            $found = $true
            Write-Success "PuTTY encontrado en: $puttyDir"
            break
        }
    }

    if (-not $found) {
        Write-Error-Custom "No se encontró PuTTY (pscp/plink)"
        Write-Info "Por favor instala PuTTY desde: https://www.putty.org/"
        Write-Info "O usa WinSCP manualmente para copiar los archivos"
        exit 1
    }
}

# Lista de archivos a copiar
Write-Header "1. Preparando Lista de Archivos"

$files = @(
    # Trait de manejo de memoria
    "modules\Report\Traits\PdfMemoryManagement.php",

    # Controladores actualizados
    "modules\Report\Http\Controllers\ReportSalesBookController.php",
    "modules\Report\Http\Controllers\ReportItemSoldController.php",

    # Helper de logos
    "app\Helpers\functions.php",

    # Service Provider
    "app\Providers\AppServiceProvider.php",

    # Archivo de configuración PHP
    ".user.ini",

    # Scripts de configuración
    "scripts\configure_server_for_pdf.sh",
    "scripts\check_pdf_config.sh",

    # Documentación
    "PDF_LARGE_REPORTS_README.md",
    "PDF_MEMORY_MANAGEMENT_README.md",
    "LOGO_HELPER_README.md"
)

# Verificar que todos los archivos existan
Write-Info "Verificando archivos locales..."
$missingFiles = 0
foreach ($file in $files) {
    $fullPath = Join-Path $ProjectRoot $file
    if (Test-Path $fullPath) {
        Write-Success $file
    } else {
        Write-Error-Custom "$file (NO ENCONTRADO)"
        $missingFiles++
    }
}

if ($missingFiles -gt 0) {
    Write-Error-Custom "Faltan $missingFiles archivos. Verifica que todos los cambios estén guardados."
    exit 1
}

# Crear directorio temporal
$tempDir = Join-Path $env:TEMP "facturador_pdf_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
Write-Info "Directorio temporal: $tempDir"

# Copiar archivos al directorio temporal manteniendo estructura
Write-Header "2. Preparando Paquete de Archivos"
foreach ($file in $files) {
    $sourcePath = Join-Path $ProjectRoot $file
    $destPath = Join-Path $tempDir $file
    $destDir = Split-Path -Parent $destPath

    # Crear directorio si no existe
    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }

    # Copiar archivo
    Copy-Item $sourcePath $destPath
    Write-Success "Preparado: $file"
}

# Crear tarball usando 7-Zip si está disponible, o tar de Windows 10+
Write-Header "3. Creando Paquete Comprimido"
$tarballName = "facturador_pdf_update_$(Get-Date -Format 'yyyyMMdd_HHmmss').tar.gz"
$tarballPath = Join-Path $env:TEMP $tarballName

# Intentar usar tar de Windows 10+ (disponible desde Windows 10 build 17063)
try {
    Push-Location $tempDir
    tar -czf $tarballPath *
    Pop-Location
    Write-Success "Paquete creado: $tarballName"
} catch {
    Write-Warning-Custom "No se pudo crear el tarball con tar nativo"
    Write-Info "Buscando 7-Zip..."

    # Buscar 7-Zip
    $7zipPaths = @(
        "C:\Program Files\7-Zip\7z.exe",
        "C:\Program Files (x86)\7-Zip\7z.exe"
    )

    $7zipFound = $false
    foreach ($path in $7zipPaths) {
        if (Test-Path $path) {
            Write-Success "7-Zip encontrado: $path"
            $tarPath = Join-Path $env:TEMP "facturador_pdf_update_$(Get-Date -Format 'yyyyMMdd_HHmmss').tar"
            & $path a -ttar $tarPath "$tempDir\*" -r | Out-Null
            & $path a -tgzip $tarballPath $tarPath | Out-Null
            Remove-Item $tarPath
            $7zipFound = $true
            break
        }
    }

    if (-not $7zipFound) {
        Write-Error-Custom "No se pudo crear el paquete comprimido"
        Write-Info "Instala 7-Zip desde: https://www.7-zip.org/"
        Remove-Item -Recurse -Force $tempDir
        exit 1
    }
}

# Copiar al servidor usando pscp
Write-Header "4. Copiando Archivos al Servidor"
Write-Info "Subiendo paquete al servidor..."

# Convertir ruta Windows a formato Unix para pscp
$remoteTempPath = "/tmp/$tarballName"

# Ejecutar pscp (puede pedir contraseña interactivamente)
& pscp -batch $tarballPath "${User}@${Server}:${remoteTempPath}"

if ($LASTEXITCODE -eq 0) {
    Write-Success "Paquete subido exitosamente"
} else {
    Write-Error-Custom "Error al subir el paquete"
    Remove-Item -Recurse -Force $tempDir
    exit 1
}

# Extraer y configurar en el servidor
Write-Header "5. Extrayendo Archivos en el Servidor"

$remoteCommands = @"
set -e

echo "Creando backup en el servidor..."
BACKUP_DIR="/root/facturador_backups_before_pdf_update_`$(date +%Y%m%d_%H%M%S)"
mkdir -p "`$BACKUP_DIR"

# Backup de archivos que serán reemplazados
cd "$RemotePath"
FILES_TO_BACKUP="modules/Report/Traits/PdfMemoryManagement.php modules/Report/Http/Controllers/ReportSalesBookController.php modules/Report/Http/Controllers/ReportItemSoldController.php app/Helpers/functions.php app/Providers/AppServiceProvider.php .user.ini"

for file in `$FILES_TO_BACKUP; do
    if [ -f "`$file" ]; then
        FILE_DIR=`$(dirname "`$file")
        mkdir -p "`$BACKUP_DIR/`$FILE_DIR"
        cp "`$file" "`$BACKUP_DIR/`$file"
    fi
done

echo "✓ Backup creado en: `$BACKUP_DIR"

# Extraer archivos nuevos
cd "$RemotePath"
tar -xzf "/tmp/$tarballName"

echo "✓ Archivos extraídos"

# Establecer permisos correctos
chown -R www-data:www-data "$RemotePath/modules/Report"
chown -R www-data:www-data "$RemotePath/app"
chown www-data:www-data "$RemotePath/.user.ini"
chmod 644 "$RemotePath/.user.ini"
chmod +x "$RemotePath/scripts/"*.sh

echo "✓ Permisos establecidos"

# Limpiar
rm "/tmp/$tarballName"

echo "✓ Archivos temporales limpiados"
echo ""
echo "Backup guardado en: `$BACKUP_DIR"
"@

& plink -batch "${User}@${Server}" $remoteCommands

Write-Success "Archivos extraídos y permisos configurados"

# Preguntar si ejecutar configuración automática
if ($AutoConfigure -or (Read-Host "`n¿Ejecutar configuración automática del servidor? (s/n)" -eq "s")) {
    Write-Header "6. Ejecutando Configuración del Servidor"
    Write-Info "Configurando PHP-FPM, Nginx y reiniciando servicios..."

    & plink -batch "${User}@${Server}" "cd $RemotePath && sudo bash scripts/configure_server_for_pdf.sh $RemotePath"

    Write-Success "Configuración del servidor completada"
} else {
    Write-Info "Configuración omitida. Puedes ejecutarla manualmente:"
    Write-Host "  plink ${User}@${Server}" -ForegroundColor Yellow
    Write-Host "  cd $RemotePath" -ForegroundColor Yellow
    Write-Host "  sudo bash scripts/configure_server_for_pdf.sh $RemotePath" -ForegroundColor Yellow
}

# Limpiar caché de Laravel
Write-Header "7. Limpiando Caché de Laravel"

$cacheCommands = @"
cd "$RemotePath"
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear
echo "✓ Caché limpiada"
"@

& plink -batch "${User}@${Server}" $cacheCommands
Write-Success "Caché de Laravel limpiada"

# Limpiar archivos temporales locales
Remove-Item -Recurse -Force $tempDir
Remove-Item $tarballPath

# Resumen final
Write-Header "DESPLIEGUE COMPLETADO EXITOSAMENTE"
Write-Host ""
Write-Success "Archivos copiados al servidor"
Write-Success "Permisos configurados"
Write-Success "Caché limpiada"
Write-Host ""
Write-Info "Servidor: ${User}@${Server}"
Write-Info "Ruta: $RemotePath"
Write-Host ""
Write-Warning-Custom "Próximos pasos:"
Write-Host "1. Probar la generación de reportes PDF" -ForegroundColor Yellow
Write-Host "2. Verificar los logs en: $RemotePath/storage/logs/laravel.log" -ForegroundColor Yellow
Write-Host "3. Si hay problemas, revisar PDF_LARGE_REPORTS_README.md" -ForegroundColor Yellow
Write-Host ""
Write-Info "Para verificar la configuración:"
Write-Host "  plink ${User}@${Server}" -ForegroundColor Cyan
Write-Host "  cd $RemotePath" -ForegroundColor Cyan
Write-Host "  bash scripts/check_pdf_config.sh" -ForegroundColor Cyan
Write-Host ""
