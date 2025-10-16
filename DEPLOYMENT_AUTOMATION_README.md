# Scripts de Automatización de Despliegue - Reportes PDF

## Descripción

Este conjunto de scripts automatiza completamente el despliegue de las mejoras de reportes PDF a múltiples servidores, incluyendo la configuración de PHP-FPM, Nginx y la copia de archivos.

## 📋 Scripts Disponibles

### 1. `configure_server_for_pdf.sh` (Linux/Servidor)
**Ubicación**: Se ejecuta en el servidor Linux  
**Propósito**: Configura automáticamente PHP-FPM, Nginx y el entorno del servidor

**Características**:
- ✅ Detecta automáticamente la versión de PHP
- ✅ Crea backups de todas las configuraciones
- ✅ Configura memory_limit, max_execution_time, timeouts
- ✅ Actualiza PHP-FPM pool
- ✅ Configura timeouts de Nginx
- ✅ Crea .user.ini en el proyecto
- ✅ Reinicia servicios automáticamente
- ✅ Limpia caché de Laravel
- ✅ Genera script de rollback

**Uso**:
```bash
# Ejecutar en el servidor
sudo bash configure_server_for_pdf.sh /var/www/html
```

---

### 2. `deploy_pdf_files.sh` (Bash - desde Linux/Mac local)
**Ubicación**: Se ejecuta desde tu máquina local (Linux/Mac)  
**Propósito**: Copia archivos y ejecuta configuración en servidor remoto vía SSH

**Características**:
- ✅ Verifica conexión SSH
- ✅ Empaqueta todos los archivos necesarios
- ✅ Crea backup en el servidor antes de copiar
- ✅ Copia archivos vía SCP
- ✅ Establece permisos correctos
- ✅ Ejecuta configuración del servidor
- ✅ Limpia caché de Laravel

**Uso**:
```bash
# Desde tu máquina local
bash deploy_pdf_files.sh 192.168.1.100 root
bash deploy_pdf_files.sh miservidor.com admin /var/www/html
```

---

### 3. `deploy_pdf_to_server.ps1` (PowerShell - desde Windows)
**Ubicación**: Se ejecuta desde tu máquina Windows local  
**Propósito**: Lo mismo que deploy_pdf_files.sh pero para Windows

**Requisitos**:
- PuTTY (pscp y plink) o WinSCP
- Windows 10+ (para tar nativo) o 7-Zip

**Características**:
- ✅ Detecta automáticamente PuTTY/7-Zip
- ✅ Interfaz colorida con progress
- ✅ Mismo flujo que la versión bash
- ✅ Compatible con PowerShell 5.1+

**Uso**:
```powershell
# Desde PowerShell en Windows
.\deploy_pdf_to_server.ps1 -Server "192.168.1.100" -User "root"
.\deploy_pdf_to_server.ps1 -Server "miservidor.com" -User "admin" -RemotePath "/var/www/html"
.\deploy_pdf_to_server.ps1 -Server "10.0.0.50" -User "deploy" -AutoConfigure
```

**Parámetros**:
- `-Server`: IP o hostname del servidor (requerido)
- `-User`: Usuario SSH (requerido)
- `-RemotePath`: Ruta del proyecto en el servidor (default: /var/www/html)
- `-AutoConfigure`: Ejecuta automáticamente la configuración sin preguntar

---

### 4. `deploy_to_multiple_servers.ps1` (PowerShell - Despliegue Masivo)
**Ubicación**: Se ejecuta desde tu máquina Windows local  
**Propósito**: Despliega a múltiples servidores automáticamente

**Características**:
- ✅ Lee lista de servidores desde CSV
- ✅ Despliega a todos los servidores secuencialmente
- ✅ Genera logs individuales por servidor
- ✅ Crea log de resumen consolidado
- ✅ Muestra tasa de éxito/fallo
- ✅ Maneja errores gracefully

**Uso**:
```powershell
# Crear archivo de servidores (primera vez)
.\deploy_to_multiple_servers.ps1

# Editar servidores.csv y agregar tus servidores
# Luego ejecutar:
.\deploy_to_multiple_servers.ps1

# Con auto-configuración:
.\deploy_to_multiple_servers.ps1 -AutoConfigure

# Usar archivo personalizado:
.\deploy_to_multiple_servers.ps1 -ServersFile "mis_servidores.csv"
```

**Formato del archivo CSV** (`servidores.csv`):
```csv
# Server,User,RemotePath,Description
192.168.1.100,root,/var/www/html,Servidor Principal
servidor2.example.com,admin,/var/www/facturador,Servidor Backup
10.0.0.50,deploy,/home/deploy/facturador,Servidor de Pruebas
```

---

### 5. `check_pdf_config.sh` (Linux/Servidor)
**Ubicación**: Se ejecuta en el servidor Linux  
**Propósito**: Verifica que la configuración sea correcta

**Características**:
- ✅ Verifica configuración de PHP
- ✅ Verifica PHP-FPM pool
- ✅ Verifica configuración de Nginx
- ✅ Verifica archivos del proyecto
- ✅ Verifica controladores actualizados
- ✅ Sugiere acciones correctivas

**Uso**:
```bash
# Ejecutar en el servidor
bash check_pdf_config.sh
```

---

## 🚀 Flujo de Trabajo Recomendado

### Opción 1: Despliegue Individual (Un servidor a la vez)

#### Desde Windows:
```powershell
# 1. Abrir PowerShell en la carpeta del proyecto
cd C:\laragon\www\facturadorpro2\scripts

# 2. Desplegar a un servidor
.\deploy_pdf_to_server.ps1 -Server "192.168.1.100" -User "root" -AutoConfigure

# 3. Verificar
# El script mostrará el resultado
```

#### Desde Linux/Mac:
```bash
# 1. Ir a la carpeta de scripts
cd /ruta/proyecto/scripts

# 2. Dar permisos de ejecución
chmod +x deploy_pdf_files.sh

# 3. Desplegar
bash deploy_pdf_files.sh 192.168.1.100 root
```

---

### Opción 2: Despliegue Masivo (Múltiples servidores)

#### Desde Windows:

**Paso 1**: Crear lista de servidores
```powershell
cd C:\laragon\www\facturadorpro2\scripts

# Crear archivo de ejemplo (primera vez)
.\deploy_to_multiple_servers.ps1
```

**Paso 2**: Editar `servidores.csv`
```csv
# Server,User,RemotePath,Description
192.168.1.100,root,/var/www/html,Cliente A - Producción
192.168.1.101,root,/var/www/html,Cliente B - Producción
192.168.1.102,admin,/var/www/facturador,Cliente C - Producción
10.0.0.50,deploy,/var/www/html,Servidor de Pruebas
```

**Paso 3**: Ejecutar despliegue masivo
```powershell
.\deploy_to_multiple_servers.ps1 -AutoConfigure
```

**Paso 4**: Revisar logs
```powershell
# Los logs se guardan en: scripts/deployment_logs/
# Ver resumen:
cat .\deployment_logs\deployment_summary_*.txt
```

---

## 📁 Archivos que se Copian Automáticamente

Los scripts copian automáticamente estos archivos al servidor:

1. **Traits**:
   - `modules/Report/Traits/PdfMemoryManagement.php`

2. **Controladores**:
   - `modules/Report/Http/Controllers/ReportSalesBookController.php`
   - `modules/Report/Http/Controllers/ReportItemSoldController.php`

3. **Helpers**:
   - `app/Helpers/functions.php`

4. **Providers**:
   - `app/Providers/AppServiceProvider.php`

5. **Configuración**:
   - `.user.ini`

6. **Scripts**:
   - `scripts/configure_server_for_pdf.sh`
   - `scripts/check_pdf_config.sh`

7. **Documentación**:
   - `PDF_LARGE_REPORTS_README.md`
   - `PDF_MEMORY_MANAGEMENT_README.md`
   - `LOGO_HELPER_README.md`

---

## 🔧 Configuraciones que se Aplican Automáticamente

### PHP-FPM (`/etc/php/X.X/fpm/pool.d/www.conf`)
```ini
request_terminate_timeout = 600
```

### PHP.ini (`/etc/php/X.X/fpm/php.ini`)
```ini
memory_limit = 2048M
max_execution_time = 600
max_input_time = 600
post_max_size = 100M
upload_max_filesize = 100M
```

### Nginx (en `location ~ \.php$`)
```nginx
fastcgi_read_timeout 600;
fastcgi_send_timeout 600;
```

### .user.ini (en el proyecto)
```ini
memory_limit = 2048M
max_execution_time = 600
max_input_time = 600
post_max_size = 100M
upload_max_filesize = 100M
```

---

## 🔐 Requisitos de Seguridad

### Para SSH desde Windows:
1. **Usar autenticación por llave SSH** (recomendado):
   ```powershell
   # Generar llave (si no existe)
   ssh-keygen -t rsa -b 4096
   
   # Copiar llave al servidor
   type $env:USERPROFILE\.ssh\id_rsa.pub | ssh user@servidor "cat >> .ssh/authorized_keys"
   ```

2. **O usar contraseña** (menos seguro):
   - Los scripts pedirán la contraseña interactivamente
   - PuTTY almacena sesiones guardadas

### Para SSH desde Linux/Mac:
```bash
# Copiar llave SSH al servidor
ssh-copy-id user@servidor
```

---

## 📊 Logs y Debugging

### Logs de Despliegue Individual:
- **Ubicación**: Se muestran en pantalla durante la ejecución
- **Guardar**: Redirigir output a archivo
  ```powershell
  .\deploy_pdf_to_server.ps1 -Server "X" -User "Y" | Tee-Object deploy.log
  ```

### Logs de Despliegue Masivo:
- **Ubicación**: `scripts/deployment_logs/`
- **Archivos**:
  - `deployment_summary_TIMESTAMP.txt` - Resumen general
  - `deploy_SERVER_TIMESTAMP.txt` - Log individual por servidor

### Logs del Servidor:
- **Laravel**: `storage/logs/laravel.log`
- **PHP-FPM**: `/var/log/php7.X-fpm.log`
- **Nginx**: `/var/log/nginx/error.log`

---

## 🔄 Rollback (Revertir Cambios)

Si algo sale mal, los scripts crean backups automáticos:

### En el Servidor:
```bash
# Los backups se guardan en:
/root/facturador_backups_TIMESTAMP/

# Para revertir manualmente:
cp /root/facturador_backups_TIMESTAMP/www.conf.bak /etc/php/7.2/fpm/pool.d/www.conf
cp /root/facturador_backups_TIMESTAMP/php.ini.bak /etc/php/7.2/fpm/php.ini
cp /root/facturador_backups_TIMESTAMP/nginx_site.conf.bak /etc/nginx/sites-available/[sitio]

sudo systemctl restart php7.2-fpm
sudo systemctl restart nginx
```

### Script de Rollback Automático:
```bash
# El script de configuración crea un script de rollback
bash /root/facturador_backups_TIMESTAMP/rollback.sh
```

---

## ⚠️ Troubleshooting

### Error: "No se pudo conectar al servidor"
**Solución**:
- Verificar que el servidor esté encendido
- Verificar firewall (puerto 22 debe estar abierto)
- Probar conexión manual: `ssh user@servidor`

### Error: "pscp/plink no encontrado" (Windows)
**Solución**:
1. Instalar PuTTY: https://www.putty.org/
2. Agregar al PATH: `C:\Program Files\PuTTY`
3. O instalar WinSCP y usar su interfaz gráfica

### Error: "Permission denied" al copiar archivos
**Solución**:
- Verificar que el usuario tenga permisos de escritura
- Usar `sudo` si es necesario
- Verificar ownership: `chown -R www-data:www-data /var/www/html`

### Error: "Nginx configuration test failed"
**Solución**:
- El script revierte automáticamente los cambios de Nginx
- Configurar manualmente: `sudo nano /etc/nginx/sites-available/[sitio]`
- Verificar: `sudo nginx -t`

### Los reportes siguen fallando después del despliegue
**Solución**:
1. Verificar configuración:
   ```bash
   bash scripts/check_pdf_config.sh
   ```

2. Verificar logs de Laravel:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verificar memoria y tiempo:
   ```bash
   php -i | grep memory_limit
   php -i | grep max_execution_time
   ```

---

## 📞 Soporte

Si tienes problemas:

1. **Ejecuta el script de verificación**:
   ```bash
   bash scripts/check_pdf_config.sh
   ```

2. **Revisa los logs**:
   - Logs de despliegue en `scripts/deployment_logs/`
   - Logs de Laravel en `storage/logs/laravel.log`

3. **Revisa la documentación**:
   - `PDF_LARGE_REPORTS_README.md` - Problemas de reportes PDF
   - `PDF_MEMORY_MANAGEMENT_README.md` - Gestión de memoria
   - `LOGO_HELPER_README.md` - Problemas con logos

---

## 🎯 Checklist Post-Despliegue

Después de desplegar a un servidor:

- [ ] Ejecutar `check_pdf_config.sh` para verificar configuración
- [ ] Probar generar un reporte PDF pequeño (< 100 registros)
- [ ] Probar generar un reporte PDF mediano (100-1000 registros)
- [ ] Verificar que reportes > 2000 registros muestren mensaje apropiado
- [ ] Probar exportación a Excel
- [ ] Revisar logs de Laravel por errores
- [ ] Verificar que los servicios están activos:
  ```bash
  systemctl status php7.2-fpm
  systemctl status nginx
  ```

---

## 🔧 Personalización

### Cambiar el límite de registros para PDF:

Editar los controladores y cambiar:
```php
$maxRecordsForPdf = 2000; // Cambiar a tu valor preferido
```

### Cambiar límites de memoria/tiempo:

Editar en los scripts:
```bash
# configure_server_for_pdf.sh
configure_php_ini "memory_limit" "2048M" "$PHP_INI"  # Cambiar 2048M
configure_php_ini "max_execution_time" "600" "$PHP_INI"  # Cambiar 600
```

### Agregar más archivos al despliegue:

Editar el array `$files` en los scripts de despliegue:
```powershell
$files = @(
    # ... archivos existentes ...
    "tu/nuevo/archivo.php",
    "otro/archivo.php"
)
```

---

## 📅 Mantenimiento

### Actualización de Versiones:

Cuando actualices los archivos localmente:
```powershell
# Desplegar cambios a todos los servidores
.\deploy_to_multiple_servers.ps1 -AutoConfigure
```

### Agregar Nuevos Servidores:

1. Editar `servidores.csv`
2. Agregar línea con formato: `Server,User,RemotePath,Description`
3. Ejecutar despliegue masivo

---

**Fecha de Creación**: 16 de Octubre, 2025  
**Versión**: 1.0  
**Autor**: Facturador PRO2 Team
