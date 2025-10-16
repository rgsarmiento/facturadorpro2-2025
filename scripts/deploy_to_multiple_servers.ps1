# ===============================================================================
# Script de Despliegue Masivo - Múltiples Servidores
# Facturador PRO2 - Reportes PDF
# Fecha: 16 de Octubre, 2025
#
# Este script despliega automáticamente a múltiples servidores
# listados en un archivo CSV
#
# Uso:
#   .\deploy_to_multiple_servers.ps1 -ServersFile "servidores.csv"
# ===============================================================================

param(
    [Parameter(Mandatory=$false)]
    [string]$ServersFile = "servidores.csv",

    [Parameter(Mandatory=$false)]
    [switch]$AutoConfigure
)

function Write-Success { Write-Host "✓ $args" -ForegroundColor Green }
function Write-Error-Custom { Write-Host "✗ $args" -ForegroundColor Red }
function Write-Info { Write-Host "ℹ $args" -ForegroundColor Cyan }
function Write-Warning-Custom { Write-Host "⚠ $args" -ForegroundColor Yellow }
function Write-Header {
    Write-Host "`n========================================" -ForegroundColor Blue
    Write-Host "$args" -ForegroundColor Blue
    Write-Host "========================================`n" -ForegroundColor Blue
}

# Obtener ruta del script
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Header "Despliegue Masivo - Múltiples Servidores"

# Verificar que existe el archivo de servidores
$serversPath = Join-Path $ScriptDir $ServersFile

if (-not (Test-Path $serversPath)) {
    Write-Warning-Custom "No se encontró el archivo: $ServersFile"
    Write-Info "Creando archivo de ejemplo..."

    # Crear archivo de ejemplo
    $exampleContent = @"
# Archivo de configuración de servidores - Facturador PRO2
# Formato: Server,User,RemotePath,Description
# Líneas que empiecen con # son comentarios
#
# Ejemplos:
# 192.168.1.100,root,/var/www/html,Servidor Principal
# servidor2.example.com,admin,/var/www/facturador,Servidor Backup
# 10.0.0.50,deploy,/home/deploy/facturador,Servidor de Pruebas

# Agrega tus servidores aquí:
"@

    Set-Content -Path $serversPath -Value $exampleContent
    Write-Success "Archivo de ejemplo creado: $serversPath"
    Write-Info "Por favor edita el archivo y agrega tus servidores, luego ejecuta este script nuevamente"
    exit 0
}

# Leer archivo de servidores
Write-Info "Leyendo configuración de servidores desde: $ServersFile"
$servers = Get-Content $serversPath | Where-Object {
    $_.Trim() -ne "" -and -not $_.StartsWith("#")
}

if ($servers.Count -eq 0) {
    Write-Error-Custom "No se encontraron servidores en el archivo $ServersFile"
    Write-Info "Agrega servidores con el formato: Server,User,RemotePath,Description"
    exit 1
}

Write-Success "Se encontraron $($servers.Count) servidor(es) para desplegar"
Write-Host ""

# Mostrar lista de servidores
Write-Header "Servidores a Configurar"
$serverIndex = 1
$serverList = @()

foreach ($serverLine in $servers) {
    $parts = $serverLine -split ","
    if ($parts.Count -lt 2) {
        Write-Warning-Custom "Línea ignorada (formato inválido): $serverLine"
        continue
    }

    $serverInfo = @{
        Index = $serverIndex
        Server = $parts[0].Trim()
        User = $parts[1].Trim()
        RemotePath = if ($parts.Count -gt 2 -and $parts[2].Trim()) { $parts[2].Trim() } else { "/var/www/html" }
        Description = if ($parts.Count -gt 3) { $parts[3].Trim() } else { "" }
    }

    $serverList += $serverInfo

    Write-Host "  $serverIndex. " -NoNewline -ForegroundColor Yellow
    Write-Host "$($serverInfo.User)@$($serverInfo.Server) " -NoNewline -ForegroundColor Cyan
    Write-Host "[$($serverInfo.RemotePath)]" -NoNewline -ForegroundColor Gray
    if ($serverInfo.Description) {
        Write-Host " - $($serverInfo.Description)" -ForegroundColor DarkGray
    } else {
        Write-Host ""
    }

    $serverIndex++
}

Write-Host ""
$confirm = Read-Host "¿Continuar con el despliegue a estos servidores? (s/n)"
if ($confirm -ne "s") {
    Write-Info "Despliegue cancelado"
    exit 0
}

# Verificar que existe el script de despliegue individual
$deployScript = Join-Path $ScriptDir "deploy_pdf_to_server.ps1"
if (-not (Test-Path $deployScript)) {
    Write-Error-Custom "No se encontró el script: deploy_pdf_to_server.ps1"
    exit 1
}

# Crear directorio de logs
$logDir = Join-Path $ScriptDir "deployment_logs"
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$summaryLog = Join-Path $logDir "deployment_summary_$timestamp.txt"

# Iniciar log
$logHeader = @"
================================================================================
Despliegue Masivo - Facturador PRO2 - Reportes PDF
Fecha: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Servidores: $($serverList.Count)
================================================================================

"@
Add-Content -Path $summaryLog -Value $logHeader

# Desplegar a cada servidor
$successCount = 0
$failCount = 0
$results = @()

foreach ($server in $serverList) {
    Write-Header "Desplegando a Servidor $($server.Index)/$($serverList.Count)"
    Write-Info "Servidor: $($server.Server)"
    Write-Info "Usuario: $($server.User)"
    Write-Info "Ruta: $($server.RemotePath)"
    if ($server.Description) {
        Write-Info "Descripción: $($server.Description)"
    }

    $serverLog = Join-Path $logDir "deploy_$($server.Server)_$timestamp.txt"

    try {
        # Ejecutar script de despliegue individual
        $params = @{
            Server = $server.Server
            User = $server.User
            RemotePath = $server.RemotePath
        }

        if ($AutoConfigure) {
            $params.AutoConfigure = $true
        }

        $startTime = Get-Date

        & $deployScript @params *> $serverLog

        $endTime = Get-Date
        $duration = ($endTime - $startTime).TotalSeconds

        if ($LASTEXITCODE -eq 0) {
            Write-Success "Despliegue exitoso en $($server.Server) (${duration}s)"
            $successCount++

            $result = @{
                Server = $server.Server
                Status = "SUCCESS"
                Duration = $duration
                Message = "Despliegue completado exitosamente"
            }
        } else {
            Write-Error-Custom "Error en el despliegue a $($server.Server)"
            $failCount++

            $result = @{
                Server = $server.Server
                Status = "FAILED"
                Duration = $duration
                Message = "Error durante el despliegue (ver log)"
            }
        }

    } catch {
        Write-Error-Custom "Excepción al desplegar en $($server.Server): $_"
        $failCount++

        $result = @{
            Server = $server.Server
            Status = "ERROR"
            Duration = 0
            Message = $_.Exception.Message
        }
    }

    $results += $result

    # Agregar al log de resumen
    $logEntry = @"
[$($result.Status)] $($server.Server) - $($server.User)@$($server.RemotePath)
  Duración: $($result.Duration)s
  Mensaje: $($result.Message)
  Log detallado: $serverLog

"@
    Add-Content -Path $summaryLog -Value $logEntry

    Write-Host ""
}

# Resumen final
Write-Header "RESUMEN DE DESPLIEGUE"

$totalServers = $serverList.Count
Write-Host ""
Write-Host "Total de servidores: " -NoNewline
Write-Host $totalServers -ForegroundColor Cyan

Write-Host "Exitosos: " -NoNewline
Write-Host $successCount -ForegroundColor Green

Write-Host "Fallidos: " -NoNewline
Write-Host $failCount -ForegroundColor Red

$successRate = [math]::Round(($successCount / $totalServers) * 100, 2)
Write-Host "Tasa de éxito: " -NoNewline
if ($successRate -eq 100) {
    Write-Host "$successRate%" -ForegroundColor Green
} elseif ($successRate -ge 80) {
    Write-Host "$successRate%" -ForegroundColor Yellow
} else {
    Write-Host "$successRate%" -ForegroundColor Red
}

Write-Host ""
Write-Header "Detalle por Servidor"

foreach ($result in $results) {
    $statusColor = switch ($result.Status) {
        "SUCCESS" { "Green" }
        "FAILED" { "Red" }
        "ERROR" { "Magenta" }
    }

    Write-Host "  [$($result.Status)]" -NoNewline -ForegroundColor $statusColor
    Write-Host " $($result.Server) " -NoNewline
    Write-Host "($($result.Duration)s)" -ForegroundColor Gray
    if ($result.Message) {
        Write-Host "    → $($result.Message)" -ForegroundColor DarkGray
    }
}

Write-Host ""
Write-Info "Log de resumen guardado en: $summaryLog"
Write-Info "Logs individuales en: $logDir"

Write-Host ""
if ($failCount -gt 0) {
    Write-Warning-Custom "Algunos servidores fallaron. Revisa los logs para más detalles."
    Write-Info "Para reintentar solo los fallidos, edita $ServersFile y vuelve a ejecutar"
} else {
    Write-Success "¡Todos los servidores configurados exitosamente!"
}

Write-Host ""
Write-Header "Próximos Pasos"
Write-Host "1. Verificar que los reportes PDF funcionan en cada servidor" -ForegroundColor Yellow
Write-Host "2. Revisar los logs de Laravel en cada servidor" -ForegroundColor Yellow
Write-Host "3. Monitorear el rendimiento de los reportes" -ForegroundColor Yellow
Write-Host ""

# Guardar resumen final
$finalSummary = @"

================================================================================
RESUMEN FINAL
================================================================================
Total de servidores: $totalServers
Exitosos: $successCount
Fallidos: $failCount
Tasa de éxito: $successRate%

Fecha de finalización: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
================================================================================
"@
Add-Content -Path $summaryLog -Value $finalSummary

exit $(if ($failCount -eq 0) { 0 } else { 1 })
