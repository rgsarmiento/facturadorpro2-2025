# Guía para Restaurar Backups Grandes (40GB+)

## 📋 Problema
Al intentar restaurar un backup grande (40GB) desde la interfaz web, aparece el error:
```
POST https://gestorstar.com/co-companies/system-backup/restore 413 (Content Too Large)
ERROR: Error al restaurar el sistema. Verifique el archivo y la conexión.
```

## 🔍 Causa
El error 413 "Content Too Large" ocurre porque Docker/Nginx/PHP tienen límites de tamaño de archivo muy bajos por defecto (2MB en PHP, sin límite explícito en Nginx).

---

## ✅ SOLUCIÓN (Automática con Script Universal)

### Características del Script

- ✅ **Detección Automática**: Encuentra los contenedores correctos sin importar sus nombres
- ✅ **Limpieza de Duplicados**: Elimina directivas duplicadas en Nginx antes de aplicar cambios
- ✅ **Configuración Robusta**: Actualiza o crea directivas PHP según sea necesario  
- ✅ **Backups Automáticos**: Crea respaldos con timestamp de todos los archivos modificados
- ✅ **Verificación de Sintaxis**: Valida configuraciones de Nginx antes de reiniciar
- ✅ **Manejo de Errores**: Proporciona instrucciones de rollback si algo falla
- ✅ **Compatible**: Funciona en cualquier instalación Docker del facturador

### 🚀 Inicio Rápido (5 minutos)

#### 1. Subir el script al servidor
```bash
# Desde tu máquina local, copiar el script al VPS
scp scripts/configure_large_backup_upload.sh root@TU-SERVIDOR:/root/
```

#### 2. Ejecutar el script en el servidor
```bash
# Conectar por SSH
ssh root@TU-SERVIDOR

# Dar permisos de ejecución
chmod +x /root/configure_large_backup_upload.sh

# Ejecutar el script
sudo bash /root/configure_large_backup_upload.sh
```

#### 3. El script detectará y configurará automáticamente:
- ✅ **PHP**: upload_max_filesize = 50G
- ✅ **PHP**: post_max_size = 50G
- ✅ **PHP**: max_execution_time = 36000 (10 horas)
- ✅ **PHP**: memory_limit = 4G
- ✅ **Nginx App**: client_max_body_size = 50G + timeouts
- ✅ **Nginx Proxy**: client_max_body_size = 50G + timeouts
- ✅ **Reinicio automático** de todos los servicios

#### 4. Usar la interfaz web normalmente
Después de ejecutar el script, puedes subir backups de hasta 50GB desde:
```
https://gestorstar.com/co-companies/system-backup/
```

**Proceso**:
1. Clic en "Restaurar desde Archivo"
2. Seleccionar tu backup de 40GB
3. Clic en "Restaurar Sistema"
4. Esperar (2-10 horas, **NO cerrar el navegador**)

---

## 🔧 Qué hace el script internamente

### 🔍 Detección Automática de Contenedores

El script es **universal** y funciona en cualquier instalación Docker porque:

1. **Busca automáticamente** los contenedores por patrón:
   - PHP-FPM: Busca nombres que contengan `fpm` o `php` (ejemplos: `fpm_app`, `php`, `php-fpm`, etc.)
   - Nginx App: Busca `nginx` excluyendo `proxy` y `api` (ejemplos: `nginx_app`, `web`, `app_nginx`, etc.)
   - Nginx Proxy: Busca `proxy` (ejemplos: `proxy`, `nginx-proxy`, `reverse-proxy`, etc.)

2. **No importan los CONTAINER ID**: Usa los nombres de contenedores, que son estables

3. **Funciona sin proxy**: Si no detecta un proxy, continúa sin error

### 📝 Archivos Modificados

El script modifica automáticamente estos archivos en los contenedores detectados:

### Contenedor `fpm_app` (PHP 7.2):
- **Archivo**: `/etc/php/7.2/fpm/php.ini`
  - `upload_max_filesize = 50G`
  - `post_max_size = 50G`
  - `max_execution_time = 36000`
  - `max_input_time = 36000`
  - `memory_limit = 4G`

- **Archivo**: `/etc/php/7.2/fpm/pool.d/www.conf`
  - `request_terminate_timeout = 36000`

### Contenedor `nginx_app`:
- **Archivo**: `/etc/nginx/sites-available/default`
  - `client_max_body_size 50G;`
  - `fastcgi_read_timeout 36000s;`
  - `fastcgi_send_timeout 36000s;`

### Contenedor `proxy`:
- **Archivo**: `/etc/nginx/nginx.conf`
  - `client_max_body_size 50G;`
  - `client_body_timeout 3600s;`
  - `proxy_read_timeout 3600s;`
  - `proxy_connect_timeout 3600s;`
  - `proxy_send_timeout 3600s;`

### Seguridad:
- ✅ Crea backups de todos los archivos modificados
- ✅ Verifica que los contenedores estén corriendo
- ✅ Prueba la configuración de Nginx antes de aplicar
- ✅ Reinicia servicios automáticamente

---

## ⚠️ Notas Importantes

### ⏱️ Tiempos Estimados
- **Configuración del script**: 2-3 minutos
- **Subida de 40GB**: 30-90 minutos (depende de tu conexión a internet)
- **Restauración del backup**: 2-10 horas (depende del hardware del servidor)

### 💾 Espacio en Disco
Asegúrate de tener al menos **100GB libres** en el servidor:
- 40GB para el archivo ZIP
- 40GB para la extracción temporal
- 20GB adicionales para la base de datos

```bash
# Verificar espacio disponible
df -h /root/pro2
docker system df -v
```

### 🚨 Durante la Restauración
- ⛔ La aplicación estará **FUERA DE LÍNEA**
- ⛔ Se sobrescribirán **TODOS los datos actuales**
- ⛔ **NO CIERRES** la ventana del navegador
- ⛔ No interrumpas el proceso
- ⛔ No reinicies el servidor

### 📊 Monitorear el Progreso

```bash
# Logs de Laravel (lo más útil)
tail -f /root/pro2/storage/logs/laravel.log

# Logs específicos del backup
tail -f /root/pro2/storage/app/backup_debug.log

# Logs del contenedor PHP
docker logs -f fpm_app

# Ver procesos activos
docker exec fpm_app ps aux | grep php
```

### 🏗️ Arquitectura de la Instalación

```
┌─────────────────────────────────────────┐
│     Internet (gestorstar.com)           │
└────────────────┬────────────────────────┘
                 │ Puerto 80/443
┌────────────────▼────────────────────────┐
│  proxy (nginx-proxy:2.0)                │
│  - SSL/HTTPS                            │
│  - client_max_body_size: 50G ✓          │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│  nginx_app (nginx)                      │
│  - Servidor web                         │
│  - client_max_body_size: 50G ✓          │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│  fpm_app (php-fpm:2.0 - PHP 7.2)        │
│  - Procesa PHP                          │
│  - upload_max_filesize: 50G ✓           │
│  - max_execution_time: 10h ✓            │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│  mariadb (MariaDB 10.5.6)               │
└─────────────────────────────────────────┘

Volúmenes:
• Host: /root/pro2
• Container: /var/www/html
```

---

## 🔄 Rollback (Deshacer cambios)

Si necesitas revertir la configuración:

```bash
# 1. Encontrar el timestamp del backup
docker exec fpm_app ls -la /etc/php/7.2/fpm/*.backup_*

# 2. Restaurar archivos originales (reemplaza TIMESTAMP)
docker exec fpm_app cp /etc/php/7.2/fpm/php.ini.backup_TIMESTAMP /etc/php/7.2/fpm/php.ini
docker exec fpm_app cp /etc/php/7.2/fpm/pool.d/www.conf.backup_TIMESTAMP /etc/php/7.2/fpm/pool.d/www.conf
docker exec nginx_app cp /etc/nginx/sites-available/default.backup_TIMESTAMP /etc/nginx/sites-available/default
docker exec proxy cp /etc/nginx/nginx.conf.backup_TIMESTAMP /etc/nginx/nginx.conf

# 3. Reiniciar servicios
docker restart fpm_app nginx_app proxy
```

---

## ✅ Verificar Configuración

Después de ejecutar el script, verifica que todo esté correcto:

```bash
# 1. Verificar PHP
docker exec fpm_app php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time"

# Debe mostrar:
# upload_max_filesize => 50G => 50G
# post_max_size => 50G => 50G
# max_execution_time => 36000 => 36000

# 2. Verificar Nginx App
docker exec nginx_app cat /etc/nginx/sites-available/default | grep client_max_body_size

# Debe mostrar:
#     client_max_body_size 50G;

# 3. Verificar Nginx Proxy
docker exec proxy cat /etc/nginx/nginx.conf | grep client_max_body_size

# Debe mostrar:
#     client_max_body_size 50G;

# 4. Ver todos los contenedores corriendo
docker ps
```

---

## 🐛 Troubleshooting (Solución de Problemas)

### ❌ Error: "directive is duplicate" en Nginx Proxy

**Síntoma**: Al ejecutar el script, Nginx Proxy muestra error `nginx: [emerg] "client_max_body_size" directive is duplicate` y entra en loop de reinicio.

**Causa**: Ya existían directivas `client_max_body_size` en archivos de configuración adicionales (como `/etc/nginx/conf.d/default.conf`).

**Solución**: El script **versión mejorada** ya elimina automáticamente directivas duplicadas antes de aplicar los cambios. Si usaste una versión anterior:

```bash
# 1. Detener el loop de reinicio
docker stop proxy

# 2. Restaurar backup
docker start proxy
docker exec proxy cp /etc/nginx/nginx.conf.backup_TIMESTAMP /etc/nginx/nginx.conf

# 3. Descargar y ejecutar la versión actualizada del script
cd /root
rm configure_large_backup_upload.sh
wget https://TU-REPOSITORIO/configure_large_backup_upload.sh
chmod 700 configure_large_backup_upload.sh
./configure_large_backup_upload.sh
```

### ❌ PHP no muestra los valores de 50G después del script

**Síntoma**: Al verificar, PHP sigue mostrando `upload_max_filesize = 2M` en lugar de `50G`.

**Causa**: El archivo `php.ini` tiene las directivas comentadas o PHP no las está reconociendo.

**Solución**: El script **versión mejorada** ya agrega las directivas al final del archivo si no existen. Si usaste una versión anterior:

```bash
# 1. Verificar el contenido del php.ini
docker exec fpm_app cat /etc/php/7.2/fpm/php.ini | grep -E "upload_max_filesize|post_max_size"

# 2. Si están comentadas (;), ejecutar:
docker exec fpm_app bash -c "
    echo 'upload_max_filesize = 50G' >> /etc/php/7.2/fpm/php.ini
    echo 'post_max_size = 50G' >> /etc/php/7.2/fpm/php.ini
    echo 'max_execution_time = 36000' >> /etc/php/7.2/fpm/php.ini
    echo 'memory_limit = 4G' >> /etc/php/7.2/fpm/php.ini
"

# 3. Reiniciar PHP-FPM
docker restart fpm_app

# 4. Verificar
docker exec fpm_app php -r "echo ini_get('upload_max_filesize');"
```

### ❌ Error: "Contenedor no está corriendo"
```bash
# Ver contenedores activos
docker ps

# Iniciar contenedor si está detenido
docker start fpm_app nginx_app proxy
```

### ❌ Error: "Permission denied"
```bash
# Ejecutar como root
sudo bash configure_large_backup_upload.sh
```

### ❌ Nginx no acepta la configuración
```bash
# Probar configuración de Nginx
docker exec nginx_app nginx -t
docker exec proxy nginx -t

# Ver errores detallados
docker logs nginx_app
docker logs proxy
```

### ❌ PHP no reconoce cambios
```bash
# Forzar reinicio del contenedor
docker restart fpm_app

# Verificar php.ini activo
docker exec fpm_app php --ini
```

### ❌ Error 413 persiste después del script
```bash
# 1. Verificar que el script terminó exitosamente
# 2. Ver logs de Nginx
docker logs proxy
docker logs nginx_app

# 3. Verificar configuraciones manualmente
docker exec proxy cat /etc/nginx/nginx.conf | grep client_max_body_size
docker exec nginx_app cat /etc/nginx/sites-available/default | grep client_max_body_size

# 4. Si no aparece, ejecutar el script nuevamente
sudo bash /root/configure_large_backup_upload.sh
```

### ❌ Restauración falla a mitad del proceso
```bash
# Ver logs para identificar el error
tail -100 /root/pro2/storage/logs/laravel.log
tail -100 /root/pro2/storage/app/backup_debug.log

# Verificar espacio en disco
df -h

# Verificar procesos MySQL
docker exec mariadb ps aux | grep mysql
```

---

## 📞 Soporte

Si tienes problemas después de seguir el troubleshooting:

1. **Captura de pantalla** del error en el navegador
2. **Últimas 100 líneas** del log:
   ```bash
   tail -100 /root/pro2/storage/logs/laravel.log
   ```
3. **Estado de contenedores**:
   ```bash
   docker ps -a
   ```
4. **Espacio en disco**:
   ```bash
   df -h
   ```

---

## 📄 Resumen

✅ **Solución**: Script automático que configura límites de 50GB  
✅ **Tiempo**: 5 minutos de configuración  
✅ **Seguro**: Crea backups de todas las configuraciones  
✅ **Reversible**: Puedes hacer rollback fácilmente  
✅ **Probado**: Para instalación Docker en gestorstar.com (vmi2404978)  

**Siguiente paso**: Ejecutar el script y usar la interfaz web normalmente 🚀
