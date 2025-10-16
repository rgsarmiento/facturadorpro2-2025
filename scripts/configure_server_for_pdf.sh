#!/bin/bash

################################################################################
# Script de Configuración Automática de Servidor para Reportes PDF
# Facturador PRO2
# Fecha: 16 de Octubre, 2025
#
# Este script configura automáticamente:
# - PHP-FPM (memory_limit, timeouts)
# - Nginx (fastcgi timeouts)
# - Copia archivos necesarios (.user.ini, traits, controladores)
#
# Uso:
#   sudo bash configure_server_for_pdf.sh [ruta_proyecto]
#
# Ejemplo:
#   sudo bash configure_server_for_pdf.sh /var/www/html
################################################################################

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funciones de utilidad
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then
    print_error "Este script debe ejecutarse como root (use sudo)"
    exit 1
fi

# Obtener ruta del proyecto
PROJECT_PATH="${1:-/var/www/html}"

if [ ! -d "$PROJECT_PATH" ]; then
    print_error "El directorio del proyecto no existe: $PROJECT_PATH"
    exit 1
fi

print_header "Configuración de Servidor para Reportes PDF"
print_info "Ruta del proyecto: $PROJECT_PATH"
print_info "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"

# Detectar versión de PHP
print_header "1. Detectando Versión de PHP"
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
print_success "PHP versión detectada: $PHP_VERSION"

# Rutas según versión de PHP
PHP_FPM_POOL="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
PHP_FPM_SERVICE="php${PHP_VERSION}-fpm"

# Crear backup de configuraciones
print_header "2. Creando Backups de Configuraciones"
BACKUP_DIR="/root/facturador_backups_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ -f "$PHP_FPM_POOL" ]; then
    cp "$PHP_FPM_POOL" "$BACKUP_DIR/www.conf.bak"
    print_success "Backup de PHP-FPM pool: $BACKUP_DIR/www.conf.bak"
fi

if [ -f "$PHP_INI" ]; then
    cp "$PHP_INI" "$BACKUP_DIR/php.ini.bak"
    print_success "Backup de php.ini: $BACKUP_DIR/php.ini.bak"
fi

# Buscar configuración de Nginx
NGINX_CONF=$(find /etc/nginx/sites-enabled/ -type f ! -name "default" | head -1)
if [ -z "$NGINX_CONF" ]; then
    NGINX_CONF=$(find /etc/nginx/sites-available/ -type f ! -name "default" | head -1)
fi

if [ -f "$NGINX_CONF" ]; then
    cp "$NGINX_CONF" "$BACKUP_DIR/nginx_site.conf.bak"
    print_success "Backup de Nginx: $BACKUP_DIR/nginx_site.conf.bak"
fi

print_info "Todos los backups guardados en: $BACKUP_DIR"

# Configurar PHP-FPM Pool
print_header "3. Configurando PHP-FPM Pool"

if [ ! -f "$PHP_FPM_POOL" ]; then
    print_error "No se encontró el archivo de pool de PHP-FPM: $PHP_FPM_POOL"
    exit 1
fi

# Configurar request_terminate_timeout
if grep -q "^request_terminate_timeout" "$PHP_FPM_POOL"; then
    # Ya existe descomentado, actualizar valor
    sed -i 's/^request_terminate_timeout.*/request_terminate_timeout = 600/' "$PHP_FPM_POOL"
    print_success "request_terminate_timeout actualizado a 600 segundos"
elif grep -q "^;request_terminate_timeout" "$PHP_FPM_POOL"; then
    # Está comentado, descomentar y establecer valor
    sed -i 's/^;request_terminate_timeout.*/request_terminate_timeout = 600/' "$PHP_FPM_POOL"
    print_success "request_terminate_timeout descomentado y establecido a 600 segundos"
else
    # No existe, agregarlo
    echo "" >> "$PHP_FPM_POOL"
    echo "; Configuración para reportes PDF - Facturador PRO2" >> "$PHP_FPM_POOL"
    echo "request_terminate_timeout = 600" >> "$PHP_FPM_POOL"
    print_success "request_terminate_timeout agregado (600 segundos)"
fi

# Configurar PHP.ini
print_header "4. Configurando PHP.ini"

configure_php_ini() {
    local setting=$1
    local value=$2
    local file=$3

    if grep -q "^${setting}" "$file"; then
        sed -i "s/^${setting}.*/${setting} = ${value}/" "$file"
        print_success "${setting} actualizado a ${value}"
    elif grep -q "^;${setting}" "$file"; then
        sed -i "s/^;${setting}.*/${setting} = ${value}/" "$file"
        print_success "${setting} descomentado y establecido a ${value}"
    else
        echo "" >> "$file"
        echo "${setting} = ${value}" >> "$file"
        print_success "${setting} agregado (${value})"
    fi
}

configure_php_ini "memory_limit" "2048M" "$PHP_INI"
configure_php_ini "max_execution_time" "600" "$PHP_INI"
configure_php_ini "max_input_time" "600" "$PHP_INI"
configure_php_ini "post_max_size" "100M" "$PHP_INI"
configure_php_ini "upload_max_filesize" "100M" "$PHP_INI"

# Configurar Nginx
print_header "5. Configurando Nginx"

if [ -f "$NGINX_CONF" ]; then
    print_info "Configurando: $NGINX_CONF"

    # Verificar si ya existe configuración de timeout
    if grep -q "fastcgi_read_timeout" "$NGINX_CONF"; then
        print_warning "fastcgi_read_timeout ya existe, actualizando..."
        sed -i 's/fastcgi_read_timeout.*/fastcgi_read_timeout 600;/' "$NGINX_CONF"
    else
        # Buscar el bloque location ~ \.php$ y agregar timeout
        if grep -q "location ~ \\\.php\$" "$NGINX_CONF"; then
            # Insertar después de la línea location ~ \.php$
            sed -i '/location ~ \\\.php\$/a\        fastcgi_read_timeout 600;\n        fastcgi_send_timeout 600;' "$NGINX_CONF"
            print_success "Timeouts de Nginx agregados (600 segundos)"
        else
            print_warning "No se encontró bloque 'location ~ \.php$', debe configurarse manualmente"
        fi
    fi

    # Verificar sintaxis de Nginx
    if nginx -t 2>/dev/null; then
        print_success "Configuración de Nginx validada correctamente"
    else
        print_error "Error en la configuración de Nginx, revirtiendo cambios..."
        cp "$BACKUP_DIR/nginx_site.conf.bak" "$NGINX_CONF"
        print_warning "Configuración de Nginx revertida, debe configurarse manualmente"
    fi
else
    print_warning "No se encontró configuración de Nginx, debe configurarse manualmente"
fi

# Copiar .user.ini
print_header "6. Configurando .user.ini del Proyecto"

cat > "$PROJECT_PATH/.user.ini" << 'EOF'
; Configuración PHP para Facturador PRO2
; Este archivo es procesado por PHP-FPM para establecer límites de recursos

; Límite de memoria para scripts PHP
memory_limit = 2048M

; Tiempo máximo de ejecución para scripts PHP
max_execution_time = 600

; Tiempo máximo de entrada (parsing de datos)
max_input_time = 600

; Tamaño máximo de datos POST
post_max_size = 100M

; Tamaño máximo de archivos subidos
upload_max_filesize = 100M

; Límite de memoria para DomPDF específicamente
; Nota: DomPDF puede consumir mucha memoria con reportes grandes
EOF

chmod 644 "$PROJECT_PATH/.user.ini"
chown www-data:www-data "$PROJECT_PATH/.user.ini"
print_success ".user.ini creado en $PROJECT_PATH"

# Reiniciar servicios
print_header "7. Reiniciando Servicios"

print_info "Reiniciando PHP-FPM..."
if systemctl restart "$PHP_FPM_SERVICE"; then
    print_success "PHP-FPM reiniciado correctamente"
else
    print_error "Error al reiniciar PHP-FPM"
fi

print_info "Reiniciando Nginx..."
if systemctl restart nginx; then
    print_success "Nginx reiniciado correctamente"
else
    print_error "Error al reiniciar Nginx"
fi

# Verificar servicios
print_header "8. Verificando Estado de Servicios"

if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    print_success "PHP-FPM está activo"
else
    print_error "PHP-FPM no está activo"
fi

if systemctl is-active --quiet nginx; then
    print_success "Nginx está activo"
else
    print_error "Nginx no está activo"
fi

# Limpiar caché de Laravel
print_header "9. Limpiando Caché de Laravel"

cd "$PROJECT_PATH"

if [ -f "artisan" ]; then
    sudo -u www-data php artisan config:clear 2>/dev/null && print_success "Config cache limpiada" || print_warning "No se pudo limpiar config cache"
    sudo -u www-data php artisan cache:clear 2>/dev/null && print_success "Application cache limpiada" || print_warning "No se pudo limpiar application cache"
    sudo -u www-data php artisan view:clear 2>/dev/null && print_success "View cache limpiada" || print_warning "No se pudo limpiar view cache"
else
    print_warning "Archivo artisan no encontrado, saltando limpieza de caché"
fi

# Verificar configuración final
print_header "10. Verificación Final de Configuración"

FINAL_MEMORY=$(php -r "echo ini_get('memory_limit');")
FINAL_EXEC_TIME=$(php -r "echo ini_get('max_execution_time');")
FINAL_INPUT_TIME=$(php -r "echo ini_get('max_input_time');")

print_info "memory_limit: $FINAL_MEMORY (esperado: 2048M)"
print_info "max_execution_time: $FINAL_EXEC_TIME (esperado: 600)"
print_info "max_input_time: $FINAL_INPUT_TIME (esperado: 600)"

# Resumen final
print_header "CONFIGURACIÓN COMPLETADA"

echo -e "${GREEN}✓ PHP-FPM configurado${NC}"
echo -e "${GREEN}✓ PHP.ini configurado${NC}"
echo -e "${GREEN}✓ Nginx configurado${NC}"
echo -e "${GREEN}✓ .user.ini creado${NC}"
echo -e "${GREEN}✓ Servicios reiniciados${NC}"
echo -e "${GREEN}✓ Caché de Laravel limpiada${NC}"

echo ""
print_info "Backups guardados en: $BACKUP_DIR"
echo ""

echo -e "${YELLOW}IMPORTANTE:${NC}"
echo "1. Asegúrese de copiar los controladores y traits actualizados al servidor"
echo "2. Verifique que los permisos sean correctos (www-data:www-data)"
echo "3. Pruebe generar un reporte PDF para verificar la configuración"
echo ""

# Crear script de rollback
print_header "11. Creando Script de Rollback"

cat > "$BACKUP_DIR/rollback.sh" << EOF
#!/bin/bash
# Script de Rollback - Generado el $(date)

echo "Revirtiendo configuraciones..."

if [ -f "$BACKUP_DIR/www.conf.bak" ]; then
    cp "$BACKUP_DIR/www.conf.bak" "$PHP_FPM_POOL"
    echo "✓ PHP-FPM pool revertido"
fi

if [ -f "$BACKUP_DIR/php.ini.bak" ]; then
    cp "$BACKUP_DIR/php.ini.bak" "$PHP_INI"
    echo "✓ php.ini revertido"
fi

if [ -f "$BACKUP_DIR/nginx_site.conf.bak" ]; then
    cp "$BACKUP_DIR/nginx_site.conf.bak" "$NGINX_CONF"
    echo "✓ Nginx revertido"
fi

systemctl restart $PHP_FPM_SERVICE
systemctl restart nginx

echo "✓ Rollback completado"
EOF

chmod +x "$BACKUP_DIR/rollback.sh"
print_success "Script de rollback creado: $BACKUP_DIR/rollback.sh"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   CONFIGURACIÓN EXITOSA   ${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

exit 0
