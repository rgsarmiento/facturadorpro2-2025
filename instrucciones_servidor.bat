@echo off
REM Script para verificar el servidor de producción

echo ========================================
echo INSTRUCCIONES PARA EJECUTAR EN EL SERVIDOR
echo ========================================
echo.
echo Conectate al servidor con PuTTY o tu cliente SSH favorito:
echo    Host: 154.38.185.33
echo    User: root
echo.
echo Una vez conectado, ejecuta estos comandos:
echo.

echo ============================================
echo 1. VERIFICAR COMMIT ACTUAL
echo ============================================
echo cd /var/www/html
echo git log --oneline -1
echo.
echo    DEBE MOSTRAR: 2a81b31a (o mas reciente)
echo.

echo ============================================
echo 2. VERIFICAR QUE GENERAL.BLADE.PHP TENGA VALIDACIONES
echo ============================================
echo grep -n "isset(\$tax-^>name)" modules/Report/Resources/views/co-sales-book/partials/general.blade.php
echo.
echo    DEBE MOSTRAR varias líneas (8, 29, 61, 78)
echo.

echo ============================================
echo 3. VERIFICAR QUE SUMMARY.BLADE.PHP TENGA VALIDACIONES
echo ============================================
echo grep -n "isset(\$tax-^>name)" modules/Report/Resources/views/co-sales-book/partials/summary.blade.php
echo.
echo    DEBE MOSTRAR varias líneas (11, 29, 76, 121)
echo.

echo ============================================
echo 4. LIMPIAR CACHE COMPLETAMENTE
echo ============================================
echo rm -rf storage/framework/views/*
echo rm -rf storage/framework/cache/data/*
echo php artisan view:clear
echo php artisan cache:clear
echo php artisan config:clear
echo systemctl restart php7.4-fpm
echo.

echo ============================================
echo 5. VERIFICAR QUE NO HAYA VISTAS COMPILADAS
echo ============================================
echo find storage/framework/views -name "*.php" ^| wc -l
echo.
echo    DEBE MOSTRAR: 0
echo.

echo ============================================
echo 6. VER ERRORES EN EL LOG
echo ============================================
echo tail -30 storage/logs/laravel-$(date +%%Y-%%m-%%d).log
echo.

pause
