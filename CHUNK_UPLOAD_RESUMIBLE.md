# Sistema de Carga Resumible por Chunks para Backups Grandes

## 📋 Descripción

Sistema implementado para manejar la carga de archivos de backup muy grandes (hasta 60GB) que pueden tomar varias horas, con capacidad de **reanudar automáticamente** si se pierde la conexión a internet.

## 🎯 Problema Resuelto

**Problema anterior:**
- Al subir un archivo de 40GB, la carga toma varias horas
- Si se pierde la conexión HTTP durante la carga, todo el proceso falla
- El usuario debe empezar de nuevo desde cero
- Error: `ERR_HTTP2_PING_FAILED` o timeout de conexión

**Solución implementada:**
- El archivo se divide en chunks (partes) de 5MB cada una
- Cada chunk se sube independientemente
- Si falla un chunk, solo se reintenta ese chunk específico
- El progreso se guarda en el servidor
- La carga se puede pausar y reanudar en cualquier momento
- Reintentos automáticos con backoff exponencial

## 🔧 Componentes Implementados

### 1. Backend (PHP - Laravel)

**Archivo:** `modules/Factcolombia1/Http/Controllers/System/SystemBackupController.php`

**Nuevos métodos:**

1. **`initChunkUpload()`** - Iniciar sesión de carga
   - Crea un ID único para la sesión
   - Guarda metadata del archivo (nombre, tamaño, total de chunks)
   - Retorna el `upload_id` para tracking

2. **`uploadChunk()`** - Subir un chunk individual
   - Recibe: `upload_id`, `chunk_index`, `chunk` (archivo)
   - Valida que no esté duplicado
   - Guarda el chunk en disco
   - Actualiza progreso en metadata
   - Retorna progreso actual

3. **`checkUploadStatus()`** - Verificar estado de carga
   - Consulta metadata de sesión
   - Retorna chunks subidos y progreso
   - Usado para reanudar después de desconexión

4. **`finalizeChunkUpload()`** - Ensamblar archivo y restaurar
   - Verifica que todos los chunks estén presentes
   - Ensambla chunks en archivo ZIP final
   - Verifica tamaño del archivo
   - Inicia proceso de restauración
   - Limpia chunks temporales

5. **`cancelChunkUpload()`** - Cancelar carga
   - Elimina chunks y metadata
   - Libera espacio en disco

6. **`cleanupChunkUpload()`** - Limpiar archivos temporales
   - Elimina directorio de chunks
   - Elimina metadata

### 2. Rutas (API)

**Archivo:** `modules/Factcolombia1/Routes/web.php`

```php
// Chunk Upload Routes - Carga resumible por chunks
Route::post('chunk/init', 'System\SystemBackupController@initChunkUpload');
Route::post('chunk/upload', 'System\SystemBackupController@uploadChunk');
Route::post('chunk/status', 'System\SystemBackupController@checkUploadStatus');
Route::post('chunk/finalize', 'System\SystemBackupController@finalizeChunkUpload');
Route::post('chunk/cancel', 'System\SystemBackupController@cancelChunkUpload');
```

### 3. Frontend (JavaScript)

**Archivo:** `modules/Factcolombia1/Resources/views/app/system/backup/index.blade.php`

**Funciones principales:**

1. **`handleRestore()`** - Punto de entrada
   - Valida archivo (tipo, tamaño máximo 60GB)
   - Calcula total de chunks
   - Inicia sesión de carga

2. **`initChunkUpload()`** - Inicializar carga
   - Llama al endpoint `/chunk/init`
   - Obtiene `upload_id`
   - Inicia carga del primer chunk

3. **`uploadNextChunk(chunkIndex)`** - Subir chunk
   - Divide archivo en slices de 5MB
   - Sube chunk usando FormData
   - Actualiza progreso en UI
   - Maneja errores con reintentos automáticos
   - Continúa con siguiente chunk si exitoso

4. **`resumeUpload(chunkIndex)`** - Reanudar carga
   - Verifica estado en servidor
   - Sincroniza chunks ya subidos
   - Continúa desde el último chunk no subido

5. **`finalizeUpload()`** - Finalizar y restaurar
   - Llama al endpoint `/chunk/finalize`
   - Muestra progreso de ensamblaje
   - Muestra resultado final

6. **`cancelChunkUpload()`** - Cancelar
   - Llama al endpoint `/chunk/cancel`
   - Limpia estado local
   - Cierra modal

7. **`updateProgressUI(progress, message)`** - UI
   - Actualiza barra de progreso
   - Muestra mensajes de estado

## 📊 Flujo de Funcionamiento

### Escenario Normal (Sin Interrupciones)

```
1. Usuario selecciona archivo ZIP de 40GB
2. Sistema calcula: 40GB / 5MB = 8,192 chunks
3. Inicia sesión con upload_id único
4. Sube chunks 0, 1, 2, 3... 8,191 secuencialmente
5. Cada chunk actualiza progreso: 0.01%, 0.02%... 100%
6. Al completar todos los chunks, llama a finalize
7. Servidor ensambla archivo completo
8. Inicia restauración del sistema
9. Limpia chunks temporales
```

### Escenario con Desconexión

```
1. Usuario inicia carga (8,192 chunks totales)
2. Chunks 0-1,500 suben exitosamente (18%)
3. ❌ Se pierde conexión a internet
4. Sistema intenta subir chunk 1,501
5. Falla el request HTTP
6. Sistema reintenta automáticamente:
   - Intento 1: Espera 2s
   - Intento 2: Espera 4s
   - Intento 3: Espera 8s
   - Intento 4: Espera 16s
   - Intento 5: Espera 30s (máximo)
7. Si todos los reintentos fallan:
   - ⏸️ Carga se PAUSA automáticamente
   - Mensaje: "Conexión perdida. Haga clic en Reanudar"
   - Botón cambia a: [▶️ Reanudar]
8. ✅ Usuario restablece conexión
9. Usuario hace clic en "Reanudar"
10. Sistema verifica estado en servidor
11. Confirma: Chunks 0-1,500 ya están subidos
12. Continúa desde chunk 1,501
13. Completa carga normalmente
```

## 🔄 Estrategia de Reintentos

### Backoff Exponencial

```javascript
Intento 1: Espera 2 segundos    (2^1 * 1000ms)
Intento 2: Espera 4 segundos    (2^2 * 1000ms)
Intento 3: Espera 8 segundos    (2^3 * 1000ms)
Intento 4: Espera 16 segundos   (2^4 * 1000ms)
Intento 5: Espera 30 segundos   (máximo)

Si falla el intento 5:
  → Pausar carga
  → Esperar intervención del usuario (botón Reanudar)
```

## 💾 Estructura de Almacenamiento

### Directorio de Chunks

```
storage/app/chunk_uploads/
└── upload_6735a1b2c3d4e5.123456789/
    ├── metadata.json
    ├── chunk_000000  (5 MB)
    ├── chunk_000001  (5 MB)
    ├── chunk_000002  (5 MB)
    ├── ...
    └── chunk_008191  (último chunk, puede ser menor)
```

### Metadata JSON

```json
{
    "upload_id": "upload_6735a1b2c3d4e5.123456789",
    "filename": "backup_sistema_2025_10_22.zip",
    "filesize": 42949672960,
    "total_chunks": 8192,
    "uploaded_chunks": [0, 1, 2, 3, 4, ... 1500],
    "created_at": "2025-10-22T14:30:00.000000Z",
    "last_activity": "2025-10-22T15:45:00.000000Z"
}
```

## 📈 Ventajas del Sistema

✅ **Resumibilidad Total**
- Puede pausarse en cualquier momento
- Reinicia desde el último chunk exitoso
- No pierde progreso

✅ **Resistente a Fallos de Red**
- Reintentos automáticos con backoff exponencial
- Maneja desconexiones temporales
- No requiere conexión estable continua

✅ **Eficiente en Memoria**
- Solo carga 5MB en memoria a la vez
- No intenta cargar archivo completo de 40GB

✅ **Feedback al Usuario**
- Barra de progreso en tiempo real
- Mensajes de estado descriptivos
- Indica chunks subidos vs totales

✅ **Gestión de Errores Robusta**
- Logs detallados en servidor y consola
- Mensajes de error claros
- Posibilidad de cancelar en cualquier momento

## ⚙️ Configuración

### Tamaño de Chunk

```javascript
// En index.blade.php
uploadState.chunkSize = 5 * 1024 * 1024; // 5MB

// Ajustable según necesidades:
// - 1MB para conexiones lentas
// - 10MB para conexiones muy rápidas
// - 5MB es un buen balance
```

### Reintentos Máximos

```javascript
uploadState.maxRetries = 5; // 5 intentos antes de pausar

// Ajustable según tolerancia:
// - 3 para fallar más rápido
// - 10 para ser más persistente
```

### Timeout por Chunk

```php
// En SystemBackupController::uploadChunk()
ini_set('max_execution_time', 600); // 10 minutos por chunk
set_time_limit(600);

// Ajustable según velocidad de conexión esperada
```

## 🧪 Pruebas Recomendadas

### 1. Prueba de Carga Normal
- Archivo pequeño (100MB)
- Verificar que todos los chunks se suban
- Verificar ensamblaje correcto
- Verificar restauración exitosa

### 2. Prueba de Desconexión
- Iniciar carga
- Desconectar WiFi/cable de red durante la carga
- Esperar mensaje "Conexión perdida"
- Reconectar red
- Hacer clic en "Reanudar"
- Verificar que continúe desde donde quedó

### 3. Prueba de Cancelación
- Iniciar carga
- Hacer clic en "Cancelar"
- Verificar que se limpien archivos temporales

### 4. Prueba con Archivo Grande Real
- Archivo de 40GB
- Monitorear progreso durante varias horas
- Verificar uso de memoria en servidor
- Verificar uso de disco en storage/app/chunk_uploads

## 📝 Notas Importantes

1. **Espacio en Disco**: Se necesita espacio para:
   - Chunks temporales (~tamaño del archivo)
   - Archivo ensamblado (~tamaño del archivo)
   - Total: ~2x el tamaño del archivo de backup

2. **Limpieza Automática**:
   - Los chunks se eliminan después de ensamblaje exitoso
   - Los chunks se eliminan si el usuario cancela
   - Revisar manualmente `storage/app/chunk_uploads/` si quedan archivos huérfanos

3. **Logs**: Revisar logs en:
   - `storage/logs/laravel.log` (logs del servidor)
   - Consola del navegador (logs del cliente)

4. **Compatibilidad**:
   - Funciona en todos los navegadores modernos
   - Requiere JavaScript habilitado
   - Funciona con archivos de cualquier tamaño (limitado por espacio en disco)

## 🔒 Seguridad

- Validación de CSRF token en cada request
- Validación de tipo de archivo (.zip)
- Validación de tamaño máximo (60GB)
- IDs de sesión únicos e impredecibles
- Limpieza automática de archivos temporales

## 🚀 Próximas Mejoras Posibles

1. **Carga paralela**: Subir múltiples chunks simultáneamente
2. **Compresión de chunks**: Comprimir chunks antes de subir
3. **Verificación de integridad**: Checksum MD5/SHA256 por chunk
4. **Persistencia**: Guardar estado en localStorage para sobrevivir recargas de página
5. **Estimación de tiempo**: Calcular tiempo restante basado en velocidad promedio

## 📞 Soporte

Si experimenta problemas:
1. Revisar consola del navegador (F12)
2. Revisar `storage/logs/laravel.log`
3. Verificar espacio en disco disponible
4. Verificar configuración de timeouts en PHP y Nginx (24 horas)
