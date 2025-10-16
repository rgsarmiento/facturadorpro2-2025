#!/bin/bash

# Script de Verificación de Configuración para Reportes PDF
# Facturador PRO2
# Fecha: 16 de Octubre, 2025

echo "=================================================="
echo "Verificación de Configuración - Reportes PDF"
echo "=================================================="
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para verificar configuración
check_config() {
    local name=$1
    local current=$2
    local required=$3

    if [ "$current" -ge "$required" ]; then
        echo -e "${GREEN}✓${NC} $name: $current (Requerido: $required)"
    else
        echo -e "${RED}✗${NC} $name: $current (Requerido: $required) - ${YELLOW}NECESITA AJUSTE${NC}"
    fi
}

echo "1. Verificando Configuración de PHP"
echo "-----------------------------------"

# Obtener configuraciones PHP actuales
memory_limit=$(php -r "echo ini_get('memory_limit');")
max_execution_time=$(php -r "echo ini_get('max_execution_time');")
max_input_time=$(php -r "echo ini_get('max_input_time');")
post_max_size=$(php -r "echo ini_get('post_max_size');")

echo "Memory Limit: $memory_limit (Requerido: 2048M o 2G)"
echo "Max Execution Time: ${max_execution_time}s (Requerido: 600s)"
echo "Max Input Time: ${max_input_time}s (Requerido: 600s)"
echo "Post Max Size: $post_max_size (Requerido: 100M)"
echo ""

echo "2. Verificando Archivos de Configuración"
echo "----------------------------------------"

# Verificar php.ini
PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)
if [ -f "$PHP_INI" ]; then
    echo -e "${GREEN}✓${NC} php.ini encontrado: $PHP_INI"
else
    echo -e "${RED}✗${NC} php.ini no encontrado"
fi

# Verificar .user.ini en el proyecto
if [ -f "/var/www/html/.user.ini" ]; then
    echo -e "${GREEN}✓${NC} .user.ini encontrado en /var/www/html/"
    echo "   Contenido:"
    cat /var/www/html/.user.ini | grep -E "memory_limit|max_execution_time"
else
    echo -e "${RED}✗${NC} .user.ini no encontrado en /var/www/html/"
    echo -e "${YELLOW}   ACCIÓN REQUERIDA:${NC} Copiar .user.ini al directorio del proyecto"
fi
echo ""

echo "3. Verificando Configuración de PHP-FPM"
echo "---------------------------------------"

# Buscar archivo de configuración de pool
FPM_POOL="/etc/php/7.2/fpm/pool.d/www.conf"
if [ -f "$FPM_POOL" ]; then
    echo -e "${GREEN}✓${NC} Pool de PHP-FPM encontrado: $FPM_POOL"

    # Verificar request_terminate_timeout
    timeout=$(grep "^request_terminate_timeout" $FPM_POOL | cut -d= -f2 | xargs)
    if [ -z "$timeout" ]; then
        timeout=$(grep "^;request_terminate_timeout" $FPM_POOL | cut -d= -f2 | xargs)
        echo -e "${YELLOW}⚠${NC} request_terminate_timeout está comentado: $timeout"
        echo -e "${YELLOW}   ACCIÓN REQUERIDA:${NC} Descomentar y establecer a 600"
    else
        if [ "$timeout" -ge 600 ]; then
            echo -e "${GREEN}✓${NC} request_terminate_timeout: ${timeout}s"
        else
            echo -e "${RED}✗${NC} request_terminate_timeout: ${timeout}s (Requerido: 600s)"
        fi
    fi
else
    echo -e "${RED}✗${NC} Pool de PHP-FPM no encontrado en $FPM_POOL"
fi
echo ""

echo "4. Verificando Configuración de Nginx"
echo "--------------------------------------"

# Buscar configuración de Nginx
NGINX_CONF=$(find /etc/nginx/sites-enabled/ -type f 2>/dev/null | head -1)
if [ -f "$NGINX_CONF" ]; then
    echo -e "${GREEN}✓${NC} Configuración de Nginx encontrada: $NGINX_CONF"

    # Verificar timeouts
    if grep -q "fastcgi_read_timeout" "$NGINX_CONF"; then
        timeout=$(grep "fastcgi_read_timeout" "$NGINX_CONF" | head -1)
        echo -e "${GREEN}✓${NC} $timeout"
    else
        echo -e "${YELLOW}⚠${NC} fastcgi_read_timeout no configurado"
        echo -e "${YELLOW}   ACCIÓN REQUERIDA:${NC} Agregar 'fastcgi_read_timeout 600;' a la configuración"
    fi
else
    echo -e "${YELLOW}⚠${NC} No se pudo encontrar la configuración de Nginx"
fi
echo ""

echo "5. Verificando Trait de Manejo de Memoria"
echo "------------------------------------------"

if [ -f "/var/www/html/modules/Report/Traits/PdfMemoryManagement.php" ]; then
    echo -e "${GREEN}✓${NC} PdfMemoryManagement.php encontrado"
else
    echo -e "${RED}✗${NC} PdfMemoryManagement.php no encontrado"
fi
echo ""

echo "6. Verificando Controladores Actualizados"
echo "------------------------------------------"

controllers=(
    "/var/www/html/modules/Report/Http/Controllers/ReportSalesBookController.php"
    "/var/www/html/modules/Report/Http/Controllers/ReportItemSoldController.php"
)

for controller in "${controllers[@]}"; do
    if [ -f "$controller" ]; then
        filename=$(basename "$controller")
        if grep -q "configurePdfResources" "$controller"; then
            echo -e "${GREEN}✓${NC} $filename - Configuración aplicada"
        else
            echo -e "${RED}✗${NC} $filename - Configuración NO aplicada"
        fi
    else
        filename=$(basename "$controller")
        echo -e "${RED}✗${NC} $filename - Archivo no encontrado"
    fi
done
echo ""

echo "=================================================="
echo "Resumen de Acciones Requeridas"
echo "=================================================="
echo ""
echo "1. Si .user.ini no existe:"
echo "   cp /ruta/local/.user.ini /var/www/html/.user.ini"
echo ""
echo "2. Si PHP-FPM necesita ajustes:"
echo "   sudo nano $FPM_POOL"
echo "   # Descomentar y establecer: request_terminate_timeout = 600"
echo "   sudo systemctl restart php7.2-fpm"
echo ""
echo "3. Si Nginx necesita ajustes:"
echo "   sudo nano $NGINX_CONF"
echo "   # Agregar en location ~ \.php$: fastcgi_read_timeout 600;"
echo "   sudo nginx -t"
echo "   sudo systemctl restart nginx"
echo ""
echo "4. Verificar que los archivos actualizados estén en el servidor:"
echo "   - modules/Report/Traits/PdfMemoryManagement.php"
echo "   - modules/Report/Http/Controllers/ReportSalesBookController.php"
echo "   - modules/Report/Http/Controllers/ReportItemSoldController.php"
echo ""
echo "5. Limpiar caché de Laravel:"
echo "   cd /var/www/html"
echo "   php artisan config:clear"
echo "   php artisan cache:clear"
echo "   php artisan view:clear"
echo ""
echo "=================================================="
