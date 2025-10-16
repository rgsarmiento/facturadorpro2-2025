# Solución de Error: Logo No Encontrado en PDFs

## Problema
Al generar PDFs, algunos tenants obtienen el error:
```
mime_content_type(...): failed to open stream: No such file or directory
```

Esto ocurre cuando el archivo de logo referenciado en la base de datos no existe físicamente en el servidor.

## Solución Implementada

Se han creado **funciones helper** en `app/Helpers/functions.php` que manejan de manera segura la carga de logotipos:

### 1. `get_logo_base64($logoFilename)`
Convierte un logo a formato base64 data URI de manera segura.
- Verifica que el archivo exista antes de intentar cargarlo
- Maneja errores mediante try-catch
- Retorna `null` si el archivo no existe o hay algún error

### 2. `render_company_logo($company, $class, $style)`
Genera el tag HTML `<img>` completo para el logo de la empresa.
- Verifica que el logo exista
- Si no existe, retorna string vacío (no rompe el PDF)
- Escapa correctamente el nombre de la empresa para evitar problemas XSS

## Cómo Usar

### Forma Antigua (PROBLEMÁTICA):
```blade
@if($company->logo)
    <img src="data:{{mime_content_type(public_path("storage/uploads/logos/{$company->logo}"))}};base64, {{base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}")))}}" alt="{{$company->name}}" class="company_logo" style="max-width: 150px;">
@endif
```

### Forma Nueva (SEGURA):
```blade
@if($company->logo)
    {!! render_company_logo($company, 'company_logo', 'max-width: 150px;') !!}
@endif
```

### O aún más simple:
```blade
{!! render_company_logo($company, 'company_logo', 'max-width: 150px;') !!}
```
_(No necesitas el `@if` porque la función ya lo valida internamente)_

## Archivos que Necesitan Actualización

Se encontraron **100+** archivos blade con el problema. Los principales directorios son:

- `app/CoreFacturalo/Templates/pdf/**/` (múltiples templates)
- `app/CoreFacturalo/Templates/pdf_backup/**/`
- `modules/Report/Resources/views/**/`
- `resources/views/tenant/**/`

### Archivos Ya Actualizados:
✅ `modules/Report/Resources/views/commons/header.blade.php`

### Cómo Actualizar Otros Archivos:

1. **Buscar el patrón problemático:**
   ```
   mime_content_type(public_path("storage/uploads/logos/
   ```

2. **Reemplazar con la función helper:**
   ```blade
   {!! render_company_logo($company, 'clase-css-aqui', 'estilos-inline-aqui') !!}
   ```

3. **Ejemplos de reemplazo:**

   **Antes:**
   ```blade
   <img src="data:{{mime_content_type(public_path("storage/uploads/logos/{$company->logo}"))}};base64, {{base64_encode(file_get_contents(public_path("storage/uploads/logos/{$company->logo}")))}}" alt="{{$company->name}}" class="company_logo_ticket contain">
   ```

   **Después:**
   ```blade
   {!! render_company_logo($company, 'company_logo_ticket contain', '') !!}
   ```

## Ventajas de la Nueva Implementación

1. ✅ **No rompe PDFs** cuando falta el logo
2. ✅ **Manejo centralizado** de errores
3. ✅ **Código más limpio** y legible
4. ✅ **Logging automático** de errores en `storage/logs/laravel.log`
5. ✅ **Fácil mantenimiento** - un solo lugar para modificar la lógica
6. ✅ **Reutilizable** en toda la aplicación

## Cómo Actualizar Masivamente (Opcional)

Si deseas actualizar todos los archivos de una vez, puedes usar un script de búsqueda y reemplazo, pero ten cuidado con las variaciones de clase y estilo en cada template.

**Comando PowerShell para encontrar archivos:**
```powershell
Get-ChildItem -Path "app\CoreFacturalo\Templates\pdf" -Filter "*.blade.php" -Recurse | Select-String -Pattern "mime_content_type.*logo" | Select-Object -Property Path -Unique
```

## Notas Importantes

- El helper está registrado en `composer.json` bajo `autoload.files`
- Si modificas `app/Helpers/functions.php`, ejecuta `composer dump-autoload`
- Los logos faltantes se logearán automáticamente en `storage/logs/laravel.log`
- El PDF se generará sin logo en lugar de fallar completamente

## Testing

Para verificar que funciona correctamente:

1. Ir a `/reports/co-items-sold`
2. Hacer clic en "Exportar PDF"
3. El PDF debe generarse correctamente incluso si el logo no existe
4. Revisar `storage/logs/laravel.log` para ver si se logeó algún error de logo faltante

## Autor

- Fecha: 2025-10-15
- Contexto: Solución para error en generación de PDFs tras restauración de backup de 40GB
