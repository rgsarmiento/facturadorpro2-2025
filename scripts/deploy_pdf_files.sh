#!/bin/bash

################################################################################
# Script de Despliegue de Archivos para Reportes PDF
# Facturador PRO2
# Fecha: 16 de Octubre, 2025
#
# Este script copia todos los archivos necesarios desde el entorno local
# al servidor de producción vía SSH/SCP
#
# Uso:
#   bash deploy_pdf_files.sh servidor usuario [ruta_destino]
#
# Ejemplos:
#   bash deploy_pdf_files.sh 192.168.1.100 root
#   bash deploy_pdf_files.sh miservidor.com admin /var/www/html
################################################################################

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Verificar argumentos
if [ $# -lt 2 ]; then
    echo "Uso: $0 <servidor> <usuario> [ruta_destino]"
    echo ""
    echo "Ejemplos:"
    echo "  $0 192.168.1.100 root"
    echo "  $0 miservidor.com admin /var/www/html"
    exit 1
fi

SERVER="$1"
USER="$2"
REMOTE_PATH="${3:-/var/www/html}"

print_header "Despliegue de Archivos - Reportes PDF"
print_info "Servidor: $SERVER"
print_info "Usuario: $USER"
print_info "Ruta destino: $REMOTE_PATH"

# Obtener ruta del script (debe estar en /scripts/)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$( dirname "$SCRIPT_DIR" )"

print_info "Ruta del proyecto local: $PROJECT_ROOT"

# Verificar que estamos en el directorio correcto
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    print_error "No se encuentra el archivo artisan. ¿Estás en el directorio correcto del proyecto?"
    exit 1
fi

# Verificar conexión SSH
print_header "1. Verificando Conexión SSH"
if ssh -o ConnectTimeout=5 "$USER@$SERVER" "echo 'Conexión exitosa'" 2>/dev/null; then
    print_success "Conexión SSH exitosa"
else
    print_error "No se pudo conectar al servidor $SERVER"
    echo "Verifica:"
    echo "  - La dirección IP/hostname es correcta"
    echo "  - El usuario tiene acceso SSH"
    echo "  - Las credenciales SSH están configuradas"
    exit 1
fi

# Lista de archivos a copiar
print_header "2. Preparando Archivos para Copiar"

declare -a FILES=(
    # Trait de manejo de memoria
    "modules/Report/Traits/PdfMemoryManagement.php"

    # Controladores actualizados
    "modules/Report/Http/Controllers/ReportSalesBookController.php"
    "modules/Report/Http/Controllers/ReportItemSoldController.php"

    # Helper de logos
    "app/Helpers/functions.php"

    # Service Provider
    "app/Providers/AppServiceProvider.php"

    # Archivo de configuración PHP
    ".user.ini"

    # Scripts de configuración
    "scripts/configure_server_for_pdf.sh"
    "scripts/check_pdf_config.sh"

    # Documentación
    "PDF_LARGE_REPORTS_README.md"
    "PDF_MEMORY_MANAGEMENT_README.md"
    "LOGO_HELPER_README.md"
)

# Verificar que todos los archivos existan localmente
print_info "Verificando archivos locales..."
MISSING_FILES=0
for file in "${FILES[@]}"; do
    if [ -f "$PROJECT_ROOT/$file" ]; then
        echo -e "  ${GREEN}✓${NC} $file"
    else
        echo -e "  ${RED}✗${NC} $file (NO ENCONTRADO)"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done

if [ $MISSING_FILES -gt 0 ]; then
    print_error "Faltan $MISSING_FILES archivos. Verifica que todos los cambios estén guardados."
    exit 1
fi

# Crear directorio temporal
TEMP_DIR=$(mktemp -d)
print_info "Directorio temporal: $TEMP_DIR"

# Copiar archivos al directorio temporal manteniendo estructura
print_header "3. Preparando Paquete de Archivos"
for file in "${FILES[@]}"; do
    FILE_DIR=$(dirname "$file")
    mkdir -p "$TEMP_DIR/$FILE_DIR"
    cp "$PROJECT_ROOT/$file" "$TEMP_DIR/$file"
    print_success "Preparado: $file"
done

# Crear un tarball
TARBALL="$TEMP_DIR/facturador_pdf_update_$(date +%Y%m%d_%H%M%S).tar.gz"
print_info "Creando paquete comprimido..."
cd "$TEMP_DIR"
tar -czf "$TARBALL" .
print_success "Paquete creado: $(basename $TARBALL)"

# Copiar tarball al servidor
print_header "4. Copiando Archivos al Servidor"
print_info "Subiendo paquete al servidor..."
if scp "$TARBALL" "$USER@$SERVER:/tmp/"; then
    print_success "Paquete subido exitosamente"
else
    print_error "Error al subir el paquete"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Descomprimir y copiar archivos en el servidor
print_header "5. Extrayendo Archivos en el Servidor"

ssh "$USER@$SERVER" bash << EOF
    set -e

    echo "Creando backup en el servidor..."
    BACKUP_DIR="/root/facturador_backups_before_pdf_update_\$(date +%Y%m%d_%H%M%S)"
    mkdir -p "\$BACKUP_DIR"

    # Backup de archivos que serán reemplazados
    cd "$REMOTE_PATH"
    for file in ${FILES[@]}; do
        if [ -f "\$file" ]; then
            FILE_DIR=\$(dirname "\$file")
            mkdir -p "\$BACKUP_DIR/\$FILE_DIR"
            cp "\$file" "\$BACKUP_DIR/\$file"
        fi
    done

    echo "✓ Backup creado en: \$BACKUP_DIR"

    # Extraer archivos nuevos
    cd "$REMOTE_PATH"
    tar -xzf /tmp/$(basename $TARBALL)

    echo "✓ Archivos extraídos"

    # Establecer permisos correctos
    chown -R www-data:www-data "$REMOTE_PATH/modules/Report"
    chown -R www-data:www-data "$REMOTE_PATH/app"
    chown www-data:www-data "$REMOTE_PATH/.user.ini"
    chmod 644 "$REMOTE_PATH/.user.ini"
    chmod +x "$REMOTE_PATH/scripts/"*.sh

    echo "✓ Permisos establecidos"

    # Limpiar
    rm /tmp/$(basename $TARBALL)

    echo "✓ Archivos temporales limpiados"
    echo ""
    echo "Backup guardado en: \$BACKUP_DIR"
EOF

print_success "Archivos extraídos y permisos configurados"

# Limpiar directorio temporal local
rm -rf "$TEMP_DIR"

print_header "6. Ejecutando Script de Configuración en el Servidor"
echo ""
echo -e "${YELLOW}¿Deseas ejecutar automáticamente el script de configuración del servidor?${NC}"
echo "Esto configurará PHP-FPM, Nginx y reiniciará los servicios."
echo ""
read -p "Ejecutar ahora? (s/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Ss]$ ]]; then
    print_info "Ejecutando configuración en el servidor..."
    ssh "$USER@$SERVER" "cd $REMOTE_PATH && sudo bash scripts/configure_server_for_pdf.sh $REMOTE_PATH"
    print_success "Configuración del servidor completada"
else
    print_info "Configuración omitida. Puedes ejecutarla manualmente con:"
    echo "  ssh $USER@$SERVER"
    echo "  cd $REMOTE_PATH"
    echo "  sudo bash scripts/configure_server_for_pdf.sh $REMOTE_PATH"
fi

# Limpiar caché de Laravel
print_header "7. Limpiando Caché de Laravel"
ssh "$USER@$SERVER" bash << EOF
    cd "$REMOTE_PATH"
    sudo -u www-data php artisan config:clear
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan view:clear
    echo "✓ Caché limpiada"
EOF

print_success "Caché de Laravel limpiada"

# Verificar instalación
print_header "8. Verificando Instalación"
echo ""
echo "Ejecutando script de verificación en el servidor..."
echo ""
ssh "$USER@$SERVER" "cd $REMOTE_PATH && bash scripts/check_pdf_config.sh"

# Resumen final
print_header "DESPLIEGUE COMPLETADO EXITOSAMENTE"
echo ""
echo -e "${GREEN}✓ Archivos copiados al servidor${NC}"
echo -e "${GREEN}✓ Permisos configurados${NC}"
echo -e "${GREEN}✓ Caché limpiada${NC}"
echo ""
print_info "Servidor: $USER@$SERVER"
print_info "Ruta: $REMOTE_PATH"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "1. Probar la generación de reportes PDF"
echo "2. Verificar los logs en: $REMOTE_PATH/storage/logs/laravel.log"
echo "3. Si hay problemas, revisar la documentación en PDF_LARGE_REPORTS_README.md"
echo ""
echo -e "${BLUE}Para verificar la configuración en cualquier momento:${NC}"
echo "  ssh $USER@$SERVER"
echo "  cd $REMOTE_PATH"
echo "  bash scripts/check_pdf_config.sh"
echo ""

exit 0
