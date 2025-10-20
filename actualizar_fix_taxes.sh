#!/bin/bash

echo "=============================================="
echo "🔧 ACTUALIZACIÓN FIX: TAXES SIN NOMBRE"
echo "=============================================="
echo ""

echo "📥 1. Actualizando código desde repositorio..."
cd /var/www/html
git pull origin master
echo ""

echo "🔍 2. Verificando commit actual..."
git log --oneline -1
echo ""

echo "🧹 3. Limpiando caché de PHP-FPM..."
sudo systemctl stop php7.4-fpm 2>/dev/null || sudo systemctl stop php8.1-fpm 2>/dev/null || sudo systemctl stop php8.2-fpm 2>/dev/null
echo "   ✓ PHP-FPM detenido"
echo ""

echo "🗑️  4. Eliminando vistas compiladas..."
rm -rf storage/framework/views/*
rm -rf storage/framework/cache/data/*
echo "   ✓ Caché eliminado"
echo ""

echo "📦 5. Ejecutando comandos artisan..."
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo "   ✓ Comandos ejecutados"
echo ""

echo "🚀 6. Iniciando PHP-FPM..."
sudo systemctl start php7.4-fpm 2>/dev/null || sudo systemctl start php8.1-fpm 2>/dev/null || sudo systemctl start php8.2-fpm 2>/dev/null
sleep 2
echo "   ✓ PHP-FPM iniciado"
echo ""

echo "✅ ACTUALIZACIÓN COMPLETADA"
echo ""
echo "🧪 PROBAR AHORA:"
echo "   1. Ir a /reports/co-sales-book"
echo "   2. Marcar o NO marcar 'Libro resumido'"
echo "   3. Exportar PDF"
echo ""
echo "💡 NOTA: Los impuestos sin nombre ahora se mostrarán como:"
echo "   'Impuesto sin nombre (ID: X)'"
echo ""
echo "🔧 Si quieres corregir los impuestos en la BD, ejecuta:"
echo "   SELECT id, name, code, rate FROM taxes WHERE name IS NULL OR name = '';"
echo "   UPDATE taxes SET name = 'IVA' WHERE id = X;"
echo ""
