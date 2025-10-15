# Verificación de Límites PHP para Restore de 40GB+

## Configuraciones PHP Requeridas

Para manejar archivos de 40GB+ sin timeouts, debes ajustar estas directivas en `php.ini`:

### 1. Límites de Upload
```ini
upload_max_filesize = 50G
post_max_size = 50G
max_input_time = 7200
```

### 2. Límites de Ejecución y Memoria
```ini
max_execution_time = 7200
memory_limit = 2G
```

### 3. MySQL CLI (para restore de bases de datos)
- El restore usa `mysql` CLI mediante archivos `.bat` (Windows) o shell directo (Linux)
- No está limitado por PHP, pero debe estar disponible en PATH
- En Laragon: `C:\laragon\bin\mysql\mysql-X.X.X\bin\mysql.exe`

## Ubicación de php.ini

Para encontrar qué archivo está usando tu servidor web:

```powershell
# Desde línea de comandos
php -i | findstr "php.ini"

# O crear un script info.php en public/
<?php phpinfo(); ?>
```

En Laragon, usualmente:
- CLI: `C:\laragon\bin\php\php-X.X.X\php.ini`
- Apache: Mismo archivo (Laragon unifica configuración)

## Configuración de Apache (Laragon)

Si usas Apache, también necesitas ajustar timeouts en `httpd.conf` o `.htaccess`:

```apache
# En httpd.conf o VirtualHost
Timeout 7200
ProxyTimeout 7200

# O en .htaccess (si AllowOverride permite)
php_value upload_max_filesize 50G
php_value post_max_size 50G
php_value max_execution_time 7200
php_value max_input_time 7200
php_value memory_limit 2G
```

## Verificación Rápida

### Comando PowerShell para verificar límites actuales:
```powershell
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
php -r "echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;"
php -r "echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL;"
```

### Crear script de verificación temporal:
Coloca esto en `public/check_limits.php`:

```php
<?php
echo "<h2>Límites PHP Actuales</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " segundos<br>";
echo "max_input_time: " . ini_get('max_input_time') . " segundos<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "<br><strong>PHP.ini location:</strong> " . php_ini_loaded_file();
```

Luego visita: `http://facturadorpro2-new.oo/check_limits.php`

**IMPORTANTE:** Elimina `check_limits.php` después de verificar.

## Códigos de Estado en SystemBackupController

El controlador ya tiene configuraciones defensivas:

1. **Cliente (JS):** Límite de 50GB en `handleRestore()`
2. **Laravel Validation:** `max:52428800` (50GB en KB) en `restore()`
3. **Ejecución PHP:** `ini_set('max_execution_time', 7200)` y `memory_limit=2G`
4. **Logs:** Usa `safeLog()` para escribir en `storage/app/backup_debug.log`

## Flujo Esperado para 40GB / 82 Tenants

### Tiempos estimados (dependen del hardware):
1. **Upload:** 5-15 minutos (depende de red/disco)
2. **Extracción ZIP:** 10-30 minutos (depende del disco)
3. **Restore BD Sistema:** 1-3 minutos
4. **Restore 82 BDs Tenant:** 30-90 minutos (procesado en lotes de 10)
5. **Restore storage/public:** 5-15 minutos

**Total estimado:** 50-150 minutos (~1-2.5 horas)

### Monitoreo durante restore:
- Revisa `storage/app/backup_debug.log` para seguir progreso
- El controller procesa tenants en lotes de 10 para evitar memory exhaustion
- Cada lote se registra: "RESTORE: Procesando lote X/Y"

## Recomendaciones para Producción

1. **Usar Async Restore** para archivos >10GB:
   - Primero carga el ZIP al servidor (FTP/SCP)
   - Colócalo en `storage/app/system_backups/`
   - Usa `POST /co-companies/system-backup/restore-start` con `{filename: "backup.zip"}`
   - Monitorea con `GET /co-companies/system-backup/restore-status/{id}`

2. **Verificar espacio en disco:**
   - Necesitas ~3x el tamaño del ZIP (ZIP + extracted + processing)
   - 40GB ZIP → ~120GB libres recomendados

3. **Programar fuera de horas pico:**
   - El restore bloquea/reemplaza BDs, hazlo cuando no haya usuarios activos

4. **Backup antes de restore:**
   - Siempre crea un backup del estado actual antes de restaurar

## Solución de Problemas

### "Maximum execution time exceeded"
- Aumenta `max_execution_time` en php.ini a 7200 o más
- Reinicia Apache/PHP-FPM después de cambiar php.ini

### "Allowed memory size exhausted"
- Aumenta `memory_limit` en php.ini a 2G o más
- El controller ya usa `ini_set('memory_limit', '2G')`

### "POST Content-Length exceeds limit"
- Aumenta `post_max_size` en php.ini (debe ser >= `upload_max_filesize`)

### Error 413 (Request Entity Too Large)
- Configuración del servidor web (Apache/Nginx)
- Apache: `LimitRequestBody 53687091200` (50GB en bytes)
- Nginx: `client_max_body_size 50G;`

### MySQL restore falla con "Access denied"
- Verifica credenciales en config/database.php
- El user debe tener permisos `CREATE DATABASE`, `GRANT` para crear tenants

### "mysql is not recognized as an internal or external command"
- Agrega MySQL bin a PATH del sistema
- O actualiza el controller para usar path absoluto (contacta para ayuda)
