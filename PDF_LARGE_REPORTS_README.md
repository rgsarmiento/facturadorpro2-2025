# Manejo de Reportes PDF con Grandes Volúmenes de Datos

## Problema Identificado

Al generar reportes PDF con grandes cantidades de registros (4000+), se presentan los siguientes problemas:

1. **Timeout de Nginx (504 Gateway Timeout)**: Nginx tiene un timeout predeterminado de 60 segundos
2. **Agotamiento de Memoria**: Aunque se configura 2G al inicio, durante la ejecución puede caer a 128M
3. **Tiempo de Procesamiento Excesivo**: Reportes que tardan más de 3-5 minutos en generarse
4. **DomPDF consume mucha memoria**: Cada página del PDF puede consumir varios MB de RAM

## Soluciones Implementadas

### 1. Límite de Registros para PDF

Los reportes PDF ahora tienen un límite de **2000 registros máximo**. Si el reporte excede este límite:
- Se rechaza la generación del PDF
- Se muestra un mensaje sugiriendo usar Excel
- Se registra en logs para monitoreo

**Ubicación**: `modules/Report/Http/Controllers/ReportSalesBookController.php`

```php
$maxRecordsForPdf = 2000;
if (count($records) > $maxRecordsForPdf) {
    return response()->json([
        'success' => false,
        'message' => "Para reportes con más de {$maxRecordsForPdf} registros, use Excel."
    ], 400);
}
```

### 2. Configuración de Recursos Mejorada

**Memoria**: 2GB (2048M)
**Tiempo de Ejecución**: 600 segundos (10 minutos)

Se fuerza el límite de memoria **dos veces**:
1. Al inicio del método con `configurePdfResources()`
2. Antes de generar el PDF con `ini_set('memory_limit', '2G')`

### 3. Archivo .user.ini

Se creó `.user.ini` en la raíz del proyecto para que PHP-FPM respete los límites:

```ini
memory_limit = 2048M
max_execution_time = 600
max_input_time = 600
post_max_size = 100M
upload_max_filesize = 100M
```

**IMPORTANTE**: Este archivo debe copiarse al servidor de producción en `/var/www/html/.user.ini`

### 4. Logging Detallado

Cada paso del proceso registra:
- Uso de memoria actual
- Límite de memoria configurado
- Cantidad de registros procesados
- Tiempo transcurrido

## Configuración del Servidor (Requerido)

Para que todo funcione correctamente en producción, se requiere:

### 1. Configuración de Nginx

Editar `/etc/nginx/sites-available/[tu-sitio]`:

```nginx
location ~ \.php$ {
    # ... otras configuraciones ...
    
    # Aumentar timeouts para reportes PDF
    fastcgi_read_timeout 600;
    fastcgi_send_timeout 600;
}
```

O en el bloque `server`:

```nginx
server {
    # ... otras configuraciones ...
    
    # Timeouts generales
    proxy_read_timeout 600;
    proxy_connect_timeout 600;
    proxy_send_timeout 600;
    send_timeout 600;
}
```

Reiniciar Nginx:
```bash
sudo systemctl restart nginx
```

### 2. Configuración de PHP-FPM

Editar `/etc/php/7.2/fpm/pool.d/www.conf` (o el archivo correspondiente a tu versión):

```ini
; Tiempo máximo de ejecución por request
request_terminate_timeout = 600
```

Reiniciar PHP-FPM:
```bash
sudo systemctl restart php7.2-fpm
```

### 3. Verificar php.ini Global

Editar `/etc/php/7.2/fpm/php.ini`:

```ini
memory_limit = 2048M
max_execution_time = 600
max_input_time = 600
post_max_size = 100M
upload_max_filesize = 100M
```

## Recomendaciones de Uso

### Para Usuarios Finales

1. **Reportes Pequeños (< 2000 registros)**: Usar PDF
2. **Reportes Grandes (> 2000 registros)**: Usar Excel
3. **Filtrar por Fechas**: Reducir el rango de fechas para obtener menos registros
4. **Usar Paginación**: Si está disponible en la interfaz

### Para Desarrolladores

1. **Aumentar el límite si es necesario**: El límite de 2000 registros puede ajustarse en el controlador:
   ```php
   $maxRecordsForPdf = 3000; // Ajustar según necesidad
   ```

2. **Monitorear logs**: Revisar regularmente los logs para identificar reportes problemáticos:
   ```bash
   tail -f storage/logs/laravel.log | grep "Libro de Ventas"
   ```

3. **Optimizar vistas PDF**: Reducir imágenes, estilos complejos, y tablas anidadas en las vistas blade

4. **Considerar procesamiento en cola**: Para reportes muy grandes, implementar Jobs con colas:
   ```php
   dispatch(new GenerateReportJob($filters))->onQueue('reports');
   ```

## Controladores Afectados

Los siguientes controladores tienen la configuración de recursos mejorada:

1. ✅ `ReportSalesBookController` - Libro de Ventas (con límite de 2000 registros)
2. ✅ `ReportItemSoldController` - Items Vendidos

### Pendientes de Aplicar

Los siguientes controladores podrían beneficiarse de las mismas mejoras:

- `ReportPurchasesController`
- `ReportCommercialController`
- `ReportInventoryController`
- `ReportAccountingController`
- Otros 10+ controladores de reportes

## Troubleshooting

### Error: "504 Gateway Timeout"
**Causa**: Nginx timeout muy corto
**Solución**: Aumentar `fastcgi_read_timeout` en Nginx

### Error: "Allowed memory size exhausted"
**Causa**: Límite de memoria insuficiente o reseteo durante ejecución
**Solución**: 
1. Verificar `.user.ini` esté en el servidor
2. Reiniciar PHP-FPM
3. Reducir cantidad de registros

### Error: "Maximum execution time exceeded"
**Causa**: Timeout de PHP muy corto
**Solución**: Aumentar `max_execution_time` y `request_terminate_timeout`

### Mensaje: "Demasiados registros para PDF"
**Causa**: El reporte tiene más de 2000 registros
**Solución**: 
1. Usar exportación a Excel
2. Filtrar por rango de fechas más pequeño
3. Aumentar `$maxRecordsForPdf` si el servidor lo soporta

## Monitoreo

### Logs Importantes

```bash
# Ver todos los logs de reportes
tail -f storage/logs/laravel.log | grep "PDF"

# Ver uso de memoria
tail -f storage/logs/laravel.log | grep "memory_usage"

# Ver reportes rechazados por tamaño
tail -f storage/logs/laravel.log | grep "Demasiados registros"
```

### Métricas a Monitorear

- Cantidad de reportes generados por día
- Tiempo promedio de generación
- Uso promedio de memoria
- Tasa de errores (timeouts, memoria agotada)
- Cantidad de reportes rechazados por límite

## Fecha de Última Actualización

16 de Octubre, 2025
