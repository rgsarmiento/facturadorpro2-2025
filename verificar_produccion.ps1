# Script para verificar el estado en producción

$server = "root@154.38.185.33"
$password = "FuWSUN4XpB9cF0ZgM55FHwY"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VERIFICANDO SERVIDOR DE PRODUCCIÓN" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Verificando commit actual..." -ForegroundColor Yellow
ssh $server "cd /var/www/html && git log --oneline -1"
Write-Host ""

Write-Host "2. Verificando si general.blade.php tiene validaciones..." -ForegroundColor Yellow
ssh $server "cd /var/www/html && grep -n 'isset(\$tax->name)' modules/Report/Resources/views/co-sales-book/partials/general.blade.php | head -5"
Write-Host ""

Write-Host "3. Verificando si summary.blade.php tiene validaciones..." -ForegroundColor Yellow
ssh $server "cd /var/www/html && grep -n 'isset(\$tax->name)' modules/Report/Resources/views/co-sales-book/partials/summary.blade.php | head -5"
Write-Host ""

Write-Host "4. Verificando vistas compiladas..." -ForegroundColor Yellow
ssh $server "cd /var/www/html && find storage/framework/views -name '*.php' | wc -l"
Write-Host ""

Write-Host "5. Contenido de las primeras 20 líneas de general.blade.php..." -ForegroundColor Yellow
ssh $server "cd /var/www/html && head -20 modules/Report/Resources/views/co-sales-book/partials/general.blade.php"
Write-Host ""
