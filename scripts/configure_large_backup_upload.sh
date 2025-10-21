#!/bin/bash

################################################################################
# Script de Configuración para Subida de Backups Grandes (hasta 50GB)
#
# DESCRIPCIÓN:
#   Configura automáticamente los límites en Docker (PHP, Nginx, Proxy) para
#   permitir la subida y restauración de backups grandes desde la interfaz web.
#
# USO:
#   1. Subir al servidor: scp configure_large_backup_upload.sh root@servidor:/root/
#   2. Dar permisos: chmod +x /root/configure_large_backup_upload.sh
#   3. Ejecutar: sudo bash /root/configure_large_backup_upload.sh
#
# QUÉ CONFIGURA:
#   • PHP: upload_max_filesize = 50G
#   • PHP: post_max_size = 50G
#   • PHP: max_execution_time = 36000 (10 horas)
#   • PHP: memory_limit = 4G
#   • Nginx App: client_max_body_size = 50G + timeouts
#   • Nginx Proxy: client_max_body_size = 50G + timeouts
#
# SEGURIDAD:
#   • Crea backups de todos los archivos modificados
#   • Verifica contenedores antes de modificar
#   • Reinicia servicios automáticamente
#
# DOCUMENTACIÓN COMPLETA:
#   Ver: RESTORE_LARGE_BACKUP_GUIDE.md
#
# IMPORTANTE: Ejecutar como root en el servidor VPS
################################################################################

set -e  # Salir si hay errores

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # Sin color

echo -e "${BLUE}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Configuración de Límites para Backups Grandes (50GB)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════${NC}"
echo ""

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}ERROR: Este script debe ejecutarse como root${NC}"
    echo "Usa: sudo bash $0"
    exit 1
fi

# Variables de configuración
PHP_INI_PATH="/etc/php/7.2/fpm/php.ini"
PHP_FPM_CONF_PATH="/etc/php/7.2/fpm/pool.d/www.conf"
NGINX_APP_SITE_CONF="/etc/nginx/sites-available/default"
NGINX_PROXY_CONF="/etc/nginx/nginx.conf"
BACKUP_SUFFIX=$(date +%Y%m%d_%H%M%S)

# Detección automática de nombres de contenedores
echo -e "${YELLOW}Detectando contenedores Docker...${NC}"

# Buscar contenedor PHP-FPM (puede ser fpm_app, php_app, php-fpm, etc.)
FPM_CONTAINER=$(docker ps --format '{{.Names}}' | grep -iE 'fpm|php' | grep -v 'api' | head -1)
if [ -z "$FPM_CONTAINER" ]; then
    echo -e "${RED}ERROR: No se encontró contenedor PHP-FPM${NC}"
    echo "Contenedores disponibles:"
    docker ps --format '{{.Names}}'
    exit 1
fi

# Buscar contenedor Nginx App (puede ser nginx_app, web, app, etc.)
NGINX_APP_CONTAINER=$(docker ps --format '{{.Names}}' | grep -iE 'nginx' | grep -v 'proxy' | grep -v 'api' | head -1)
if [ -z "$NGINX_APP_CONTAINER" ]; then
    echo -e "${RED}ERROR: No se encontró contenedor Nginx App${NC}"
    echo "Contenedores disponibles:"
    docker ps --format '{{.Names}}'
    exit 1
fi

# Buscar contenedor Proxy (puede ser proxy, nginx-proxy, reverse-proxy, etc.)
NGINX_PROXY_CONTAINER=$(docker ps --format '{{.Names}}' | grep -iE 'proxy' | head -1)
if [ -z "$NGINX_PROXY_CONTAINER" ]; then
    echo -e "${YELLOW}ADVERTENCIA: No se encontró contenedor Proxy. Continuando sin configurar proxy...${NC}"
fi

echo -e "${GREEN}✓ Contenedores detectados:${NC}"
echo -e "  - PHP-FPM: ${BLUE}$FPM_CONTAINER${NC}"
echo -e "  - Nginx App: ${BLUE}$NGINX_APP_CONTAINER${NC}"
if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    echo -e "  - Nginx Proxy: ${BLUE}$NGINX_PROXY_CONTAINER${NC}"
else
    echo -e "  - Nginx Proxy: ${YELLOW}No detectado (omitiendo)${NC}"
fi
echo ""

################################################################################
# 1. CONFIGURAR PHP.INI EN CONTENEDOR FPM_APP
################################################################################

echo -e "${YELLOW}[1/4] Configurando PHP.ini en contenedor fpm_app...${NC}"

# Crear backup del php.ini original
docker exec $FPM_CONTAINER bash -c "cp $PHP_INI_PATH ${PHP_INI_PATH}.backup_${BACKUP_SUFFIX}"
echo -e "${GREEN}  ✓ Backup creado: ${PHP_INI_PATH}.backup_${BACKUP_SUFFIX}${NC}"

# Modificar configuraciones PHP
docker exec $FPM_CONTAINER bash -c "sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50G/' $PHP_INI_PATH"
docker exec $FPM_CONTAINER bash -c "sed -i 's/^post_max_size = .*/post_max_size = 50G/' $PHP_INI_PATH"
docker exec $FPM_CONTAINER bash -c "sed -i 's/^max_execution_time = .*/max_execution_time = 36000/' $PHP_INI_PATH"
docker exec $FPM_CONTAINER bash -c "sed -i 's/^max_input_time = .*/max_input_time = 36000/' $PHP_INI_PATH"
docker exec $FPM_CONTAINER bash -c "sed -i 's/^memory_limit = .*/memory_limit = 4G/' $PHP_INI_PATH"

echo -e "${GREEN}  ✓ Configuraciones PHP actualizadas:${NC}"
echo -e "    - upload_max_filesize = 50G"
echo -e "    - post_max_size = 50G"
echo -e "    - max_execution_time = 36000 (10 horas)"
echo -e "    - max_input_time = 36000 (10 horas)"
echo -e "    - memory_limit = 4G"

################################################################################
# 2. CONFIGURAR PHP-FPM (www.conf)
################################################################################

echo ""
echo -e "${YELLOW}[2/4] Configurando PHP-FPM (www.conf)...${NC}"

# Crear backup del www.conf original
docker exec $FPM_CONTAINER bash -c "cp $PHP_FPM_CONF_PATH ${PHP_FPM_CONF_PATH}.backup_${BACKUP_SUFFIX}"
echo -e "${GREEN}  ✓ Backup creado: ${PHP_FPM_CONF_PATH}.backup_${BACKUP_SUFFIX}${NC}"

# Agregar/modificar configuraciones de timeouts en PHP-FPM
docker exec $FPM_CONTAINER bash -c "
if grep -q '^request_terminate_timeout' $PHP_FPM_CONF_PATH; then
    sed -i 's/^request_terminate_timeout.*/request_terminate_timeout = 36000/' $PHP_FPM_CONF_PATH
else
    echo 'request_terminate_timeout = 36000' >> $PHP_FPM_CONF_PATH
fi
"

echo -e "${GREEN}  ✓ PHP-FPM configurado:${NC}"
echo -e "    - request_terminate_timeout = 36000 (10 horas)"

################################################################################
# 3. CONFIGURAR NGINX EN CONTENEDOR nginx_app
################################################################################

echo ""
echo -e "${YELLOW}[3/4] Configurando Nginx en contenedor nginx_app...${NC}"

# Crear backup del site config
docker exec $NGINX_APP_CONTAINER bash -c "cp $NGINX_APP_SITE_CONF ${NGINX_APP_SITE_CONF}.backup_${BACKUP_SUFFIX}"
echo -e "${GREEN}  ✓ Backup creado: ${NGINX_APP_SITE_CONF}.backup_${BACKUP_SUFFIX}${NC}"

# Agregar client_max_body_size al server block
docker exec $NGINX_APP_CONTAINER bash -c "
if ! grep -q 'client_max_body_size' $NGINX_APP_SITE_CONF; then
    sed -i '/server {/a \    client_max_body_size 50G;' $NGINX_APP_SITE_CONF
else
    sed -i 's/client_max_body_size.*/client_max_body_size 50G;/' $NGINX_APP_SITE_CONF
fi
"

# Agregar timeouts al location PHP
docker exec $NGINX_APP_CONTAINER bash -c "
if ! grep -q 'fastcgi_read_timeout' $NGINX_APP_SITE_CONF; then
    sed -i '/fastcgi_pass fpm_app:9000;/a \        fastcgi_read_timeout 36000s;\n        fastcgi_send_timeout 36000s;' $NGINX_APP_SITE_CONF
fi
"

echo -e "${GREEN}  ✓ Nginx (nginx_app) configurado:${NC}"
echo -e "    - client_max_body_size = 50G"
echo -e "    - fastcgi_read_timeout = 36000s"
echo -e "    - fastcgi_send_timeout = 36000s"

################################################################################
# 4. CONFIGURAR NGINX PROXY (Opcional)
################################################################################

echo ""
if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    echo -e "${YELLOW}[4/4] Configurando Nginx Proxy...${NC}"

    # Crear backup del nginx.conf del proxy
    docker exec $NGINX_PROXY_CONTAINER bash -c "cp $NGINX_PROXY_CONF ${NGINX_PROXY_CONF}.backup_${BACKUP_SUFFIX}"
    echo -e "${GREEN}  ✓ Backup creado: ${NGINX_PROXY_CONF}.backup_${BACKUP_SUFFIX}${NC}"

    # Agregar configuraciones en el bloque http del proxy
    docker exec $NGINX_PROXY_CONTAINER bash -c "
    if ! grep -q 'client_max_body_size' $NGINX_PROXY_CONF; then
        sed -i '/http {/a \    client_max_body_size 50G;\n    client_body_timeout 3600s;\n    proxy_read_timeout 3600s;\n    proxy_connect_timeout 3600s;\n    proxy_send_timeout 3600s;' $NGINX_PROXY_CONF
    else
        sed -i 's/client_max_body_size.*/client_max_body_size 50G;/' $NGINX_PROXY_CONF
        sed -i 's/client_body_timeout.*/client_body_timeout 3600s;/' $NGINX_PROXY_CONF
        sed -i 's/proxy_read_timeout.*/proxy_read_timeout 3600s;/' $NGINX_PROXY_CONF
        sed -i 's/proxy_connect_timeout.*/proxy_connect_timeout 3600s;/' $NGINX_PROXY_CONF
        sed -i 's/proxy_send_timeout.*/proxy_send_timeout 3600s;/' $NGINX_PROXY_CONF
    fi
    "

    echo -e "${GREEN}  ✓ Nginx Proxy configurado:${NC}"
    echo -e "    - client_max_body_size = 50G"
    echo -e "    - client_body_timeout = 3600s"
    echo -e "    - proxy_read_timeout = 3600s"
    echo -e "    - proxy_connect_timeout = 3600s"
    echo -e "    - proxy_send_timeout = 3600s"
else
    echo -e "${YELLOW}[4/4] Omitiendo configuración de Nginx Proxy (no detectado)${NC}"
fi

################################################################################
# 5. REINICIAR SERVICIOS
################################################################################

echo ""
echo -e "${YELLOW}Reiniciando servicios...${NC}"

# Reiniciar PHP-FPM en el contenedor
echo -e "${BLUE}  → Reiniciando PHP-FPM...${NC}"
docker exec $FPM_CONTAINER bash -c "kill -USR2 1" 2>/dev/null || docker restart $FPM_CONTAINER
sleep 3
echo -e "${GREEN}  ✓ PHP-FPM reiniciado${NC}"

# Reiniciar Nginx App
echo -e "${BLUE}  → Reiniciando Nginx App...${NC}"
docker exec $NGINX_APP_CONTAINER nginx -t && docker exec $NGINX_APP_CONTAINER nginx -s reload || docker restart $NGINX_APP_CONTAINER
sleep 2
echo -e "${GREEN}  ✓ Nginx App reiniciado${NC}"

# Reiniciar Nginx Proxy (si existe)
if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    echo -e "${BLUE}  → Reiniciando Nginx Proxy...${NC}"
    docker exec $NGINX_PROXY_CONTAINER nginx -t && docker exec $NGINX_PROXY_CONTAINER nginx -s reload || docker restart $NGINX_PROXY_CONTAINER
    sleep 2
    echo -e "${GREEN}  ✓ Nginx Proxy reiniciado${NC}"
fi

################################################################################
# 6. VERIFICACIÓN
################################################################################

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}Verificando configuraciones aplicadas...${NC}"
echo ""

# Verificar PHP
echo -e "${BLUE}PHP Configuration:${NC}"
docker exec $FPM_CONTAINER php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time|memory_limit" | head -4

echo ""
echo -e "${BLUE}Nginx App Configuration:${NC}"
docker exec $NGINX_APP_CONTAINER grep -E "client_max_body_size|fastcgi_read_timeout" $NGINX_APP_SITE_CONF | grep -v "#"

if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    echo ""
    echo -e "${BLUE}Nginx Proxy Configuration:${NC}"
    docker exec $NGINX_PROXY_CONTAINER grep -E "client_max_body_size|proxy_read_timeout" $NGINX_PROXY_CONF | grep -v "#"
fi

################################################################################
# 7. RESUMEN Y RECOMENDACIONES
################################################################################

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ CONFIGURACIÓN COMPLETADA EXITOSAMENTE${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Cambios aplicados:${NC}"
echo -e "  • PHP: Límite de subida aumentado a 50GB"
echo -e "  • PHP: Tiempo de ejecución extendido a 10 horas"
echo -e "  • PHP: Memoria aumentada a 4GB"
echo -e "  • Nginx: Tamaño máximo de body aumentado a 50GB"
echo -e "  • Nginx: Timeouts extendidos para operaciones largas"
echo ""
echo -e "${YELLOW}Archivos de respaldo creados:${NC}"
echo -e "  • $PHP_INI_PATH.backup_${BACKUP_SUFFIX}"
echo -e "  • $PHP_FPM_CONF_PATH.backup_${BACKUP_SUFFIX}"
echo -e "  • $NGINX_APP_SITE_CONF.backup_${BACKUP_SUFFIX}"
echo -e "  • $NGINX_PROXY_CONF.backup_${BACKUP_SUFFIX}"
echo ""
echo -e "${YELLOW}Siguientes pasos:${NC}"
echo -e "  1. Accede a: ${BLUE}https://gestorstar.com/co-companies/system-backup/${NC}"
echo -e "  2. Haz clic en 'Restaurar desde Archivo'"
echo -e "  3. Selecciona tu backup de 40GB"
echo -e "  4. Haz clic en 'Restaurar Sistema'"
echo -e "  5. ${RED}¡IMPORTANTE!${NC} El proceso puede tardar ${YELLOW}2-10 horas${NC}"
echo -e "  6. ${RED}NO CIERRES${NC} la ventana del navegador durante el proceso"
echo ""
echo -e "${YELLOW}Monitorear progreso:${NC}"
echo -e "  ${BLUE}tail -f /root/pro2/storage/logs/laravel.log${NC}"
echo ""
echo -e "${YELLOW}En caso de problemas:${NC}"
echo -e "  • Revisa logs: ${BLUE}docker logs -f $FPM_CONTAINER${NC}"
echo -e "  • Restaurar backup: ${BLUE}docker exec $FPM_CONTAINER cp $PHP_INI_PATH.backup_${BACKUP_SUFFIX} $PHP_INI_PATH${NC}"
echo ""
echo -e "${GREEN}¡Configuración lista para backups grandes!${NC} 🚀"
echo ""
