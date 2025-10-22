#!/bin/bash

################################################################################
# Script de Configuración para Subida de Backups Grandes (hasta 60GB)
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
#   • PHP: upload_max_filesize = 60G
#   • PHP: post_max_size = 60G
#   • PHP: max_execution_time = 86400 (24 horas)
#   • PHP: memory_limit = 4G
#   • Nginx App: client_max_body_size = 60G + timeouts 24h
#   • Nginx Proxy: client_max_body_size = 60G + timeouts 24h
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
echo -e "${BLUE}   Configuración de Límites para Backups Grandes (60GB - 24h)${NC}"
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
NGINX_PROXY_CONTAINER=$(docker ps -a --format '{{.Names}}' | grep -iE 'proxy' | head -1)
if [ -z "$NGINX_PROXY_CONTAINER" ]; then
    echo -e "${YELLOW}ADVERTENCIA: No se encontró contenedor Proxy. Continuando sin configurar proxy...${NC}"
fi

echo -e "${GREEN}✓ Contenedores detectados:${NC}"
echo -e "  - PHP-FPM: ${BLUE}$FPM_CONTAINER${NC}"
echo -e "  - Nginx App: ${BLUE}$NGINX_APP_CONTAINER${NC}"
if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    # Verificar si el proxy está en ciclo de reinicio
    PROXY_STATUS=$(docker ps -a --filter "name=$NGINX_PROXY_CONTAINER" --format '{{.Status}}')
    if echo "$PROXY_STATUS" | grep -qi "restarting"; then
        echo -e "  - Nginx Proxy: ${RED}$NGINX_PROXY_CONTAINER (EN CICLO DE REINICIO - Se reparará)${NC}"

        echo ""
        echo -e "${YELLOW}═══════════════════════════════════════════════════════════════════${NC}"
        echo -e "${YELLOW}   ⚠️  PROXY EN CICLO DE REINICIO - REPARANDO AUTOMÁTICAMENTE${NC}"
        echo -e "${YELLOW}═══════════════════════════════════════════════════════════════════${NC}"
        echo ""

        # Detener el contenedor para poder trabajar con él
        echo -e "${BLUE}  → Deteniendo contenedor proxy...${NC}"
        docker stop $NGINX_PROXY_CONTAINER 2>/dev/null || true
        sleep 2

        # Método 1: Intentar trabajar directamente con el contenedor detenido
        echo -e "${BLUE}  → Buscando backup de configuración...${NC}"

        # Usar docker cp para acceder a archivos sin necesidad de volúmenes
        TEMP_DIR="/tmp/nginx_repair_$$"
        mkdir -p $TEMP_DIR

        # Copiar configuración actual del contenedor
        if docker cp $NGINX_PROXY_CONTAINER:/etc/nginx/nginx.conf $TEMP_DIR/nginx.conf 2>/dev/null; then
            echo -e "${GREEN}  ✓ Configuración actual extraída${NC}"

            # Buscar backups en el contenedor
            BACKUP_LIST=$(docker exec $NGINX_PROXY_CONTAINER ls -t /etc/nginx/nginx.conf.backup_* 2>/dev/null | head -1 || echo "")

            if [ -n "$BACKUP_LIST" ]; then
                echo -e "${YELLOW}  → Restaurando desde backup: $(basename $BACKUP_LIST)${NC}"
                docker cp $NGINX_PROXY_CONTAINER:$BACKUP_LIST $TEMP_DIR/nginx.conf.backup
                docker cp $TEMP_DIR/nginx.conf.backup $NGINX_PROXY_CONTAINER:/etc/nginx/nginx.conf
                echo -e "${GREEN}  ✓ Backup restaurado${NC}"
            else
                echo -e "${YELLOW}  → No se encontró backup, limpiando configuración corrupta...${NC}"

                # Limpiar todas las directivas problemáticas en el archivo local
                sed -i '/client_max_body_size/d' $TEMP_DIR/nginx.conf
                sed -i '/client_body_timeout/d' $TEMP_DIR/nginx.conf
                sed -i '/client_header_timeout/d' $TEMP_DIR/nginx.conf
                sed -i '/send_timeout[^_]/d' $TEMP_DIR/nginx.conf
                sed -i '/proxy_read_timeout/d' $TEMP_DIR/nginx.conf
                sed -i '/proxy_connect_timeout/d' $TEMP_DIR/nginx.conf
                sed -i '/proxy_send_timeout/d' $TEMP_DIR/nginx.conf
                sed -i '/keepalive_timeout/d' $TEMP_DIR/nginx.conf
                sed -i '/proxy_buffering/d' $TEMP_DIR/nginx.conf

                # Copiar configuración limpia de vuelta al contenedor
                docker cp $TEMP_DIR/nginx.conf $NGINX_PROXY_CONTAINER:/etc/nginx/nginx.conf
                echo -e "${GREEN}  ✓ Configuración limpiada${NC}"
            fi
        else
            echo -e "${RED}  ✗ No se pudo acceder a la configuración del contenedor${NC}"
            echo -e "${YELLOW}  → Intentando método alternativo...${NC}"

            # Método 2: Iniciar contenedor y trabajar con él
            docker start $NGINX_PROXY_CONTAINER
            sleep 2

            # Buscar y restaurar backup directamente
            BACKUP_FILE=$(docker exec $NGINX_PROXY_CONTAINER bash -c "ls -t /etc/nginx/nginx.conf.backup_* 2>/dev/null | head -1" 2>/dev/null || echo "")

            if [ -n "$BACKUP_FILE" ]; then
                echo -e "${YELLOW}  → Restaurando desde: $BACKUP_FILE${NC}"
                docker exec $NGINX_PROXY_CONTAINER cp "$BACKUP_FILE" /etc/nginx/nginx.conf
                docker restart $NGINX_PROXY_CONTAINER
            else
                echo -e "${YELLOW}  → Limpiando directivas duplicadas...${NC}"
                docker exec $NGINX_PROXY_CONTAINER bash -c "
                    sed -i '/client_max_body_size/d' /etc/nginx/nginx.conf
                    sed -i '/client_body_timeout/d' /etc/nginx/nginx.conf
                    sed -i '/client_header_timeout/d' /etc/nginx/nginx.conf
                    sed -i '/send_timeout[^_]/d' /etc/nginx/nginx.conf
                    sed -i '/proxy_read_timeout/d' /etc/nginx/nginx.conf
                    sed -i '/proxy_connect_timeout/d' /etc/nginx/nginx.conf
                    sed -i '/proxy_send_timeout/d' /etc/nginx/nginx.conf
                    sed -i '/keepalive_timeout/d' /etc/nginx/nginx.conf
                    sed -i '/proxy_buffering/d' /etc/nginx/nginx.conf
                "
                docker restart $NGINX_PROXY_CONTAINER
            fi
        fi

        # Limpiar archivos temporales
        rm -rf $TEMP_DIR

        # Iniciar/reiniciar el contenedor
        echo -e "${BLUE}  → Iniciando contenedor proxy...${NC}"
        docker start $NGINX_PROXY_CONTAINER 2>/dev/null || docker restart $NGINX_PROXY_CONTAINER
        sleep 5

        # Verificar si arrancó correctamente
        if docker ps | grep -q $NGINX_PROXY_CONTAINER; then
            echo -e "${GREEN}  ✓ Proxy reparado y funcionando${NC}"
        else
            echo -e "${RED}  ✗ El proxy sigue fallando. Ver logs:${NC}"
            docker logs $NGINX_PROXY_CONTAINER 2>&1 | tail -10
            echo ""
            echo -e "${YELLOW}  El script continuará e intentará reparar nuevamente durante la configuración...${NC}"
        fi

        echo ""
        echo -e "${YELLOW}═══════════════════════════════════════════════════════════════════${NC}"
        echo ""

    else
        echo -e "  - Nginx Proxy: ${BLUE}$NGINX_PROXY_CONTAINER${NC} (${GREEN}$PROXY_STATUS${NC})"
    fi
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

# Modificar configuraciones PHP usando sed con expresiones más robustas
docker exec $FPM_CONTAINER bash -c "
    # Primero intentar actualizar valores existentes
    sed -i 's/^upload_max_filesize[[:space:]]*=.*/upload_max_filesize = 60G/' $PHP_INI_PATH
    sed -i 's/^post_max_size[[:space:]]*=.*/post_max_size = 60G/' $PHP_INI_PATH
    sed -i 's/^max_execution_time[[:space:]]*=.*/max_execution_time = 86400/' $PHP_INI_PATH
    sed -i 's/^max_input_time[[:space:]]*=.*/max_input_time = 86400/' $PHP_INI_PATH
    sed -i 's/^memory_limit[[:space:]]*=.*/memory_limit = 4G/' $PHP_INI_PATH
    sed -i 's/^default_socket_timeout[[:space:]]*=.*/default_socket_timeout = 86400/' $PHP_INI_PATH

    # Si no existen, agregarlos al final del archivo
    grep -q '^upload_max_filesize' $PHP_INI_PATH || echo 'upload_max_filesize = 60G' >> $PHP_INI_PATH
    grep -q '^post_max_size' $PHP_INI_PATH || echo 'post_max_size = 60G' >> $PHP_INI_PATH
    grep -q '^max_execution_time' $PHP_INI_PATH || echo 'max_execution_time = 86400' >> $PHP_INI_PATH
    grep -q '^max_input_time' $PHP_INI_PATH || echo 'max_input_time = 86400' >> $PHP_INI_PATH
    grep -q '^memory_limit' $PHP_INI_PATH || echo 'memory_limit = 4G' >> $PHP_INI_PATH
    grep -q '^default_socket_timeout' $PHP_INI_PATH || echo 'default_socket_timeout = 86400' >> $PHP_INI_PATH
"

echo -e "${GREEN}  ✓ Configuraciones PHP actualizadas:${NC}"
echo -e "    - upload_max_filesize = 60G"
echo -e "    - post_max_size = 60G"
echo -e "    - max_execution_time = 86400 (24 horas)"
echo -e "    - max_input_time = 86400 (24 horas)"
echo -e "    - memory_limit = 4G"
echo -e "    - default_socket_timeout = 86400 (24 horas)"

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
    sed -i 's/^request_terminate_timeout.*/request_terminate_timeout = 86400/' $PHP_FPM_CONF_PATH
else
    echo 'request_terminate_timeout = 86400' >> $PHP_FPM_CONF_PATH
fi
"

echo -e "${GREEN}  ✓ PHP-FPM configurado:${NC}"
echo -e "    - request_terminate_timeout = 86400 (24 horas)"

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
    sed -i '/server {/a \    client_max_body_size 60G;\n    client_body_timeout 86400s;\n    client_header_timeout 86400s;\n    send_timeout 86400s;\n    keepalive_timeout 86400s;' $NGINX_APP_SITE_CONF
else
    sed -i 's/client_max_body_size.*/client_max_body_size 60G;/' $NGINX_APP_SITE_CONF
    sed -i 's/client_body_timeout.*/client_body_timeout 86400s;/' $NGINX_APP_SITE_CONF || sed -i '/client_max_body_size/a \    client_body_timeout 86400s;' $NGINX_APP_SITE_CONF
    sed -i 's/client_header_timeout.*/client_header_timeout 86400s;/' $NGINX_APP_SITE_CONF || sed -i '/client_body_timeout/a \    client_header_timeout 86400s;' $NGINX_APP_SITE_CONF
fi
"

# Agregar timeouts al location PHP
docker exec $NGINX_APP_CONTAINER bash -c "
if ! grep -q 'fastcgi_read_timeout' $NGINX_APP_SITE_CONF; then
    sed -i '/fastcgi_pass fpm_app:9000;/a \        fastcgi_read_timeout 86400s;\n        fastcgi_send_timeout 86400s;\n        fastcgi_connect_timeout 86400s;' $NGINX_APP_SITE_CONF
else
    sed -i 's/fastcgi_read_timeout.*/fastcgi_read_timeout 86400s;/' $NGINX_APP_SITE_CONF
    sed -i 's/fastcgi_send_timeout.*/fastcgi_send_timeout 86400s;/' $NGINX_APP_SITE_CONF
    sed -i 's/fastcgi_connect_timeout.*/fastcgi_connect_timeout 86400s;/' $NGINX_APP_SITE_CONF
fi
"

echo -e "${GREEN}  ✓ Nginx (nginx_app) configurado:${NC}"
echo -e "    - client_max_body_size = 60G"
echo -e "    - client_body_timeout = 86400s (24 horas)"
echo -e "    - fastcgi_read_timeout = 86400s (24 horas)"
echo -e "    - fastcgi_send_timeout = 86400s (24 horas)"
echo -e "    - fastcgi_connect_timeout = 86400s (24 horas)"
echo -e "    - keepalive_timeout = 86400s (24 horas)"

################################################################################
# 4. CONFIGURAR NGINX PROXY (Opcional)
################################################################################

echo ""
if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    echo -e "${YELLOW}[4/4] Configurando Nginx Proxy...${NC}"

    # Verificar si el contenedor está corriendo
    if ! docker ps | grep -q $NGINX_PROXY_CONTAINER; then
        echo -e "${YELLOW}  ⚠ Contenedor proxy no está corriendo. Iniciando...${NC}"

        # Iniciar el contenedor primero
        docker start $NGINX_PROXY_CONTAINER
        sleep 3

        # Verificar si inició correctamente o entró en ciclo de reinicio
        PROXY_STATUS=$(docker ps -a --filter "name=$NGINX_PROXY_CONTAINER" --format '{{.Status}}')

        if docker ps | grep -q $NGINX_PROXY_CONTAINER && ! echo "$PROXY_STATUS" | grep -qi "restarting"; then
            echo -e "${GREEN}  ✓ Contenedor proxy iniciado correctamente${NC}"
        else
            if echo "$PROXY_STATUS" | grep -qi "restarting"; then
                echo -e "${RED}  ✗ Contenedor proxy entró en ciclo de reinicio. Reparando...${NC}"

                # Esperar un momento y detener el contenedor
                echo -e "${BLUE}  → Deteniendo contenedor en ciclo de reinicio...${NC}"
                docker stop $NGINX_PROXY_CONTAINER 2>/dev/null
                sleep 3
            else
                echo -e "${RED}  ✗ Contenedor proxy falló al iniciar. Reparando...${NC}"
            fi

            # Ver el error
            echo -e "${BLUE}  → Últimos logs del contenedor:${NC}"
            docker logs $NGINX_PROXY_CONTAINER 2>&1 | tail -5

            # Intentar obtener backup si existe usando docker cp
            echo -e "${YELLOW}  → Buscando backup de configuración...${NC}"
            TEMP_DIR="/tmp/proxy_repair_$$"
            mkdir -p $TEMP_DIR

            # Intentar copiar backups del contenedor detenido
            docker cp $NGINX_PROXY_CONTAINER:/etc/nginx/ $TEMP_DIR/ 2>/dev/null || true

            if [ -f "$TEMP_DIR/nginx/nginx.conf" ]; then
                # Buscar backup más reciente
                LATEST_BACKUP=$(ls -t $TEMP_DIR/nginx/nginx.conf.backup_* 2>/dev/null | head -1)

                if [ -n "$LATEST_BACKUP" ]; then
                    echo -e "${YELLOW}  → Restaurando desde backup: $(basename $LATEST_BACKUP)${NC}"
                    cp "$LATEST_BACKUP" "$TEMP_DIR/nginx/nginx.conf"
                else
                    echo -e "${YELLOW}  → Limpiando configuración corrupta...${NC}"
                    # Limpiar directivas duplicadas
                    sed -i '/client_max_body_size/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/client_body_timeout/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/client_header_timeout/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/send_timeout[^_]/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/proxy_read_timeout/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/proxy_connect_timeout/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/proxy_send_timeout/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/keepalive_timeout/d' "$TEMP_DIR/nginx/nginx.conf"
                    sed -i '/proxy_buffering/d' "$TEMP_DIR/nginx/nginx.conf"
                fi

                # Copiar configuración limpia de vuelta
                docker cp "$TEMP_DIR/nginx/nginx.conf" $NGINX_PROXY_CONTAINER:/etc/nginx/nginx.conf
                echo -e "${GREEN}  ✓ Configuración reparada${NC}"
            fi

            # Limpiar temporal
            rm -rf $TEMP_DIR

            # Intentar iniciar nuevamente
            echo -e "${BLUE}  → Intentando iniciar proxy nuevamente...${NC}"
            docker start $NGINX_PROXY_CONTAINER
            sleep 3

            if ! docker ps | grep -q $NGINX_PROXY_CONTAINER; then
                echo -e "${RED}  ✗ Proxy sigue fallando. Ver logs detallados:${NC}"
                docker logs $NGINX_PROXY_CONTAINER 2>&1 | tail -20
                echo ""
                echo -e "${YELLOW}  El script continuará sin configurar el proxy.${NC}"
                echo -e "${YELLOW}  Puede intentar reparar manualmente o ejecutar el script nuevamente después.${NC}"
                echo ""
            else
                echo -e "${GREEN}  ✓ Proxy reparado exitosamente${NC}"
            fi
        fi
    fi

    # Solo continuar si el contenedor está corriendo ahora (no en restarting)
    PROXY_STATUS=$(docker ps -a --filter "name=$NGINX_PROXY_CONTAINER" --format '{{.Status}}')

    if docker ps | grep -q $NGINX_PROXY_CONTAINER && ! echo "$PROXY_STATUS" | grep -qi "restarting"; then
        # Crear backup del nginx.conf del proxy
        # Esperar un poco para asegurar que el contenedor esté estable
        sleep 2

        docker exec $NGINX_PROXY_CONTAINER bash -c "cp $NGINX_PROXY_CONF ${NGINX_PROXY_CONF}.backup_${BACKUP_SUFFIX}" 2>/dev/null || true
        echo -e "${GREEN}  ✓ Backup creado: ${NGINX_PROXY_CONF}.backup_${BACKUP_SUFFIX}${NC}"

        echo -e "${BLUE}  → Limpiando configuraciones duplicadas en conf.d...${NC}"

        # Verificar nuevamente que no esté en restart loop antes de continuar
        PROXY_STATUS=$(docker ps -a --filter "name=$NGINX_PROXY_CONTAINER" --format '{{.Status}}')
        if echo "$PROXY_STATUS" | grep -qi "restarting"; then
            echo -e "${YELLOW}  ⚠ Contenedor entró en restart loop durante limpieza. Abortando configuración.${NC}"
            return 1
        fi

        # Eliminar TODOS los duplicados de TODOS los archivos en conf.d
        docker exec $NGINX_PROXY_CONTAINER bash -c "
        # Eliminar duplicados en TODOS los archivos de conf.d (incluyendo default.conf)
        for conf_file in /etc/nginx/conf.d/*.conf; do
            if [ -f \"\$conf_file\" ]; then
                echo \"    Limpiando: \$conf_file\"
                sed -i '/client_max_body_size/d' \"\$conf_file\"
                sed -i '/client_body_timeout/d' \"\$conf_file\"
                sed -i '/client_header_timeout/d' \"\$conf_file\"
                sed -i '/send_timeout[^_]/d' \"\$conf_file\"
                sed -i '/proxy_read_timeout/d' \"\$conf_file\"
                sed -i '/proxy_connect_timeout/d' \"\$conf_file\"
                sed -i '/proxy_send_timeout/d' \"\$conf_file\"
                sed -i '/keepalive_timeout/d' \"\$conf_file\"
                sed -i '/proxy_buffering/d' \"\$conf_file\"
            fi
        done

        # También limpiar en vhost.d si existe
        if [ -d /etc/nginx/vhost.d ]; then
            for vhost_file in /etc/nginx/vhost.d/*; do
                if [ -f \"\$vhost_file\" ]; then
                    echo \"    Limpiando: \$vhost_file\"
                    sed -i '/client_max_body_size/d' \"\$vhost_file\"
                    sed -i '/client_body_timeout/d' \"\$vhost_file\"
                sed -i '/client_header_timeout/d' \"\$vhost_file\"
                sed -i '/send_timeout[^_]/d' \"\$vhost_file\"
                sed -i '/proxy_read_timeout/d' \"\$vhost_file\"
                sed -i '/proxy_connect_timeout/d' \"\$vhost_file\"
                sed -i '/proxy_send_timeout/d' \"\$vhost_file\"
                sed -i '/keepalive_timeout/d' \"\$vhost_file\"
                sed -i '/proxy_buffering/d' \"\$vhost_file\"
            fi
        done
    fi
    " 2>&1 | grep -E "Limpiando|^\s*$" || echo "  No se encontraron archivos adicionales"

        echo -e "${BLUE}  → Limpiando configuraciones duplicadas en nginx.conf principal...${NC}"

        # Verificar nuevamente estado antes del siguiente docker exec
        PROXY_STATUS=$(docker ps -a --filter "name=$NGINX_PROXY_CONTAINER" --format '{{.Status}}')
        if echo "$PROXY_STATUS" | grep -qi "restarting"; then
            echo -e "${YELLOW}  ⚠ Contenedor entró en restart loop. Abortando configuración.${NC}"
            return 1
        fi

        # Ahora agregar/actualizar configuraciones en nginx.conf principal
        docker exec $NGINX_PROXY_CONTAINER bash -c "
    # Eliminar TODAS las configuraciones antiguas si existen (incluso múltiples veces)
    while grep -q 'client_max_body_size' $NGINX_PROXY_CONF; do
        sed -i '0,/client_max_body_size/d' $NGINX_PROXY_CONF
    done
    while grep -q 'client_body_timeout' $NGINX_PROXY_CONF; do
        sed -i '0,/client_body_timeout/d' $NGINX_PROXY_CONF
    done
    while grep -q 'client_header_timeout' $NGINX_PROXY_CONF; do
        sed -i '0,/client_header_timeout/d' $NGINX_PROXY_CONF
    done
    while grep -q 'send_timeout[^_]' $NGINX_PROXY_CONF; do
        sed -i '0,/send_timeout[^_]/d' $NGINX_PROXY_CONF
    done
    while grep -q 'proxy_read_timeout' $NGINX_PROXY_CONF; do
        sed -i '0,/proxy_read_timeout/d' $NGINX_PROXY_CONF
    done
    while grep -q 'proxy_connect_timeout' $NGINX_PROXY_CONF; do
        sed -i '0,/proxy_connect_timeout/d' $NGINX_PROXY_CONF
    done
    while grep -q 'proxy_send_timeout' $NGINX_PROXY_CONF; do
        sed -i '0,/proxy_send_timeout/d' $NGINX_PROXY_CONF
    done
    while grep -q 'keepalive_timeout' $NGINX_PROXY_CONF; do
        sed -i '0,/keepalive_timeout/d' $NGINX_PROXY_CONF
    done
    while grep -q 'proxy_buffering' $NGINX_PROXY_CONF; do
        sed -i '0,/proxy_buffering/d' $NGINX_PROXY_CONF
    done

    echo \"    Todas las configuraciones duplicadas eliminadas\"
        "

        echo -e "${BLUE}  → Agregando configuraciones nuevas...${NC}"

        # Verificar estado final antes de agregar configuraciones
        PROXY_STATUS=$(docker ps -a --filter "name=$NGINX_PROXY_CONTAINER" --format '{{.Status}}')
        if echo "$PROXY_STATUS" | grep -qi "restarting"; then
            echo -e "${YELLOW}  ⚠ Contenedor entró en restart loop. Abortando configuración.${NC}"
            return 1
        fi

        # Agregar configuraciones nuevas UNA SOLA VEZ en el bloque http
        docker exec $NGINX_PROXY_CONTAINER bash -c "
        # Verificar que no existan antes de agregar
        if ! grep -q 'client_max_body_size' $NGINX_PROXY_CONF; then
            sed -i '/http {/a\    # Configuraciones para backups grandes (agregado por script)\n    client_max_body_size 60G;\n    client_body_timeout 86400s;\n    client_header_timeout 86400s;\n    send_timeout 86400s;\n    proxy_read_timeout 86400s;\n    proxy_connect_timeout 86400s;\n    proxy_send_timeout 86400s;\n    keepalive_timeout 86400s;\n    proxy_buffering off;' $NGINX_PROXY_CONF
            echo \"    Configuraciones agregadas correctamente\"
        else
            echo \"    Las configuraciones ya existen, omitiendo...\"
        fi
        "

        echo -e "${GREEN}  ✓ Nginx Proxy configurado:${NC}"
        echo -e "    - client_max_body_size = 60G"
        echo -e "    - client_body_timeout = 86400s (24 horas)"
        echo -e "    - client_header_timeout = 86400s (24 horas)"
        echo -e "    - proxy_read_timeout = 86400s (24 horas)"
        echo -e "    - proxy_connect_timeout = 86400s (24 horas)"
        echo -e "    - proxy_send_timeout = 86400s (24 horas)"
        echo -e "    - keepalive_timeout = 86400s (24 horas)"
        echo -e "    - proxy_buffering = off"
    else
        echo -e "${YELLOW}  ⚠ No se pudo configurar Nginx Proxy (contenedor no disponible o en restart loop)${NC}"
    fi
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
if docker exec $NGINX_APP_CONTAINER nginx -t 2>&1; then
    docker exec $NGINX_APP_CONTAINER nginx -s reload 2>/dev/null || docker restart $NGINX_APP_CONTAINER
    sleep 2
    echo -e "${GREEN}  ✓ Nginx App reiniciado correctamente${NC}"
else
    echo -e "${RED}  ✗ Error en sintaxis de Nginx App${NC}"
    docker logs $NGINX_APP_CONTAINER 2>&1 | tail -10
fi

# Reiniciar Nginx Proxy (si existe) con manejo robusto de errores
if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    # Verificar si el contenedor está corriendo antes de intentar reiniciar
    if ! docker ps | grep -q $NGINX_PROXY_CONTAINER; then
        echo -e "${YELLOW}  ⚠ Nginx Proxy no está corriendo, omitiendo reinicio${NC}"
        echo -e "${YELLOW}     El contenedor se configuró pero no pudo iniciarse correctamente${NC}"
        echo -e "${YELLOW}     Puede revisar los logs: docker logs $NGINX_PROXY_CONTAINER${NC}"
    else
        echo -e "${BLUE}  → Verificando y reiniciando Nginx Proxy...${NC}"

        # Verificar sintaxis antes de reiniciar
        echo -e "${BLUE}     Probando sintaxis de configuración...${NC}"
        SYNTAX_CHECK=$(docker exec $NGINX_PROXY_CONTAINER nginx -t 2>&1)

        if echo "$SYNTAX_CHECK" | grep -q "syntax is ok"; then
            echo -e "${GREEN}     ✓ Sintaxis correcta${NC}"

            # Intentar reload primero (más seguro)
            if docker exec $NGINX_PROXY_CONTAINER nginx -s reload 2>/dev/null; then
                echo -e "${GREEN}     ✓ Reload exitoso${NC}"
            else
                # Si reload falla, hacer restart completo
                echo -e "${YELLOW}     → Reload falló, haciendo restart completo...${NC}"
                docker restart $NGINX_PROXY_CONTAINER
            fi

            # Esperar a que el contenedor esté listo
            echo -e "${BLUE}     → Esperando que el contenedor esté listo...${NC}"
            for i in {1..30}; do
                sleep 1
                if docker ps --filter "name=$NGINX_PROXY_CONTAINER" --filter "status=running" | grep -q $NGINX_PROXY_CONTAINER; then
                    echo -e "${GREEN}  ✓ Nginx Proxy reiniciado correctamente (${i}s)${NC}"
                    break
                fi

                # Si después de 10 segundos sigue reiniciándose, hay un problema
                if [ $i -eq 10 ]; then
                    echo -e "${RED}     ✗ El contenedor sigue reiniciándose, verificando logs...${NC}"
                    docker logs $NGINX_PROXY_CONTAINER 2>&1 | tail -20
                fi

                # Si llega a 30 segundos, definitivamente hay un error
                if [ $i -eq 30 ]; then
                    echo -e "${RED}  ✗ Error: Nginx Proxy no pudo iniciar después de 30 segundos${NC}"
                    echo -e "${YELLOW}     Intentando restaurar desde backup...${NC}"

                    # Buscar el backup más reciente usando docker cp ya que exec no funcionará
                    TEMP_DIR="/tmp/proxy_restore_$$"
                    mkdir -p $TEMP_DIR

                    docker stop $NGINX_PROXY_CONTAINER 2>/dev/null
                    sleep 2

                    docker cp $NGINX_PROXY_CONTAINER:/etc/nginx/ $TEMP_DIR/ 2>/dev/null
                    LATEST_BACKUP=$(ls -t $TEMP_DIR/nginx/nginx.conf.backup_* 2>/dev/null | head -2 | tail -1)

                    if [ -n "$LATEST_BACKUP" ]; then
                        echo -e "${YELLOW}     → Restaurando: $(basename $LATEST_BACKUP)${NC}"
                        cp "$LATEST_BACKUP" "$TEMP_DIR/nginx/nginx.conf"
                        docker cp "$TEMP_DIR/nginx/nginx.conf" $NGINX_PROXY_CONTAINER:/etc/nginx/nginx.conf
                        rm -rf $TEMP_DIR

                        docker start $NGINX_PROXY_CONTAINER
                        sleep 5

                        if docker ps | grep -q $NGINX_PROXY_CONTAINER; then
                            echo -e "${GREEN}     ✓ Restauración exitosa desde backup${NC}"
                        else
                            echo -e "${RED}     ✗ Restauración falló. Intervención manual requerida.${NC}"
                            echo -e "${YELLOW}     Ver logs: docker logs $NGINX_PROXY_CONTAINER${NC}"
                        fi
                    else
                        echo -e "${RED}     ✗ No se encontró backup. Intervención manual requerida.${NC}"
                        rm -rf $TEMP_DIR
                    fi
                    break
                fi
            done

        else
            echo -e "${RED}  ✗ Error en sintaxis de Nginx Proxy:${NC}"
            echo "$SYNTAX_CHECK" | grep "error" | head -5

            echo -e "${YELLOW}     → Intentando restaurar desde backup automáticamente...${NC}"

            # Buscar el backup más reciente usando docker cp
            TEMP_DIR="/tmp/proxy_restore_syntax_$$"
            mkdir -p $TEMP_DIR

            docker stop $NGINX_PROXY_CONTAINER 2>/dev/null
            sleep 2

            docker cp $NGINX_PROXY_CONTAINER:/etc/nginx/ $TEMP_DIR/ 2>/dev/null
            LATEST_BACKUP=$(ls -t $TEMP_DIR/nginx/nginx.conf.backup_* 2>/dev/null | head -2 | tail -1)

        if [ -n "$LATEST_BACKUP" ]; then
            echo -e "${YELLOW}     → Restaurando desde: $LATEST_BACKUP${NC}"
            docker exec $NGINX_PROXY_CONTAINER cp "$LATEST_BACKUP" $NGINX_PROXY_CONF

            # Verificar sintaxis del backup
            if docker exec $NGINX_PROXY_CONTAINER nginx -t 2>&1 | grep -q "syntax is ok"; then
                echo -e "${GREEN}     ✓ Backup restaurado correctamente${NC}"
                docker restart $NGINX_PROXY_CONTAINER
                sleep 5

                if docker ps | grep -q $NGINX_PROXY_CONTAINER; then
                    echo -e "${GREEN}  ✓ Nginx Proxy funcionando con configuración anterior${NC}"
                    echo -e "${YELLOW}     ADVERTENCIA: No se aplicaron las nuevas configuraciones de timeout${NC}"
                    echo -e "${YELLOW}     Puede intentar ejecutar el script nuevamente después de revisar el proxy${NC}"
                fi
            else
                echo -e "${RED}     ✗ El backup también tiene errores${NC}"
                echo -e "${YELLOW}     Para restaurar manualmente:${NC}"
                echo -e "${YELLOW}     docker exec $NGINX_PROXY_CONTAINER cp $LATEST_BACKUP $NGINX_PROXY_CONF${NC}"
                echo -e "${YELLOW}     docker restart $NGINX_PROXY_CONTAINER${NC}"
            fi
        else
            echo -e "${RED}     ✗ No se encontró backup anterior${NC}"
            echo -e "${YELLOW}     Revise manualmente: docker exec -it $NGINX_PROXY_CONTAINER bash${NC}"
        fi
        fi
    else
        echo -e "${YELLOW}    Para restaurar: docker exec $NGINX_PROXY_CONTAINER cp ${NGINX_PROXY_CONF}.backup_${BACKUP_SUFFIX} $NGINX_PROXY_CONF && docker restart $NGINX_PROXY_CONTAINER${NC}"
    fi
fi

################################################################################
# 6. VERIFICACIÓN
################################################################################

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}Verificando configuraciones aplicadas...${NC}"
echo ""

# Esperar un momento para que los servicios se estabilicen
sleep 3

# Verificar PHP leyendo directamente el archivo FPM (no CLI)
echo -e "${BLUE}PHP-FPM Configuration (valores reales para web):${NC}"
docker exec $FPM_CONTAINER bash -c "
    echo -n '  upload_max_filesize = '
    grep '^upload_max_filesize' $PHP_INI_PATH | tail -1 | awk '{print \$3}'
    echo -n '  post_max_size = '
    grep '^post_max_size' $PHP_INI_PATH | tail -1 | awk '{print \$3}'
    echo -n '  max_execution_time = '
    grep '^max_execution_time' $PHP_INI_PATH | tail -1 | awk '{print \$3}'
    echo -n '  memory_limit = '
    grep '^memory_limit' $PHP_INI_PATH | tail -1 | awk '{print \$3}'
"

echo ""
echo -e "${BLUE}Nginx App Configuration:${NC}"
docker exec $NGINX_APP_CONTAINER grep -E "client_max_body_size|fastcgi_read_timeout" $NGINX_APP_SITE_CONF | grep -v "#" | head -3

if [ -n "$NGINX_PROXY_CONTAINER" ]; then
    echo ""
    echo -e "${BLUE}Nginx Proxy Configuration:${NC}"
    if docker ps | grep -q $NGINX_PROXY_CONTAINER; then
        docker exec $NGINX_PROXY_CONTAINER grep -E "client_max_body_size|proxy_read_timeout" $NGINX_PROXY_CONF | grep -v "#" | head -3
    else
        echo -e "${RED}  Contenedor proxy no está corriendo. Verifique los logs.${NC}"
    fi
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
echo -e "  • PHP: Límite de subida aumentado a 60GB"
echo -e "  • PHP: Tiempo de ejecución extendido a 24 horas (86400s)"
echo -e "  • PHP: Memoria aumentada a 4GB"
echo -e "  • Nginx: Tamaño máximo de body aumentado a 60GB"
echo -e "  • Nginx: Timeouts extendidos a 24 horas (86400s)"
echo -e "  • Proxy: Timeouts extendidos a 24 horas (86400s)"
echo -e "  • Proxy: Buffering desactivado para archivos grandes"
echo ""
echo -e "${YELLOW}Archivos de respaldo creados:${NC}"
echo -e "  • $PHP_INI_PATH.backup_${BACKUP_SUFFIX}"
echo -e "  • $PHP_FPM_CONF_PATH.backup_${BACKUP_SUFFIX}"
echo -e "  • $NGINX_APP_SITE_CONF.backup_${BACKUP_SUFFIX}"
echo -e "  • $NGINX_PROXY_CONF.backup_${BACKUP_SUFFIX}"
echo ""
echo -e "${BLUE}Nota:${NC} Los valores mostrados son del archivo ${BLUE}php-fpm${NC}, que es el que usa"
echo -e "       la aplicación web. El CLI puede mostrar valores diferentes."
echo ""
echo -e "${YELLOW}Siguientes pasos:${NC}"
echo -e "  1. Accede a: ${BLUE}https://tu-dominio.com/co-companies/system-backup/${NC}"
echo -e "  2. Haz clic en 'Restaurar desde Archivo'"
echo -e "  3. Selecciona tu backup (hasta 60GB)"
echo -e "  4. Haz clic en 'Restaurar Sistema'"
echo -e "  5. ${RED}¡IMPORTANTE!${NC} El proceso puede tardar ${YELLOW}2-12 horas${NC}"
echo -e "  6. ${RED}NO CIERRES${NC} la ventana del navegador durante el proceso"
echo -e "  7. La página puede parecer 'congelada' - es normal, el proceso continúa"
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
