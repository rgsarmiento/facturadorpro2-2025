# Gestión de Memoria en Generación de PDFs

## Problema

Al generar PDFs con mucha información (ej: reportes con muchos registros), se puede alcanzar el límite de memoria de PHP:

```
Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes)
```

## Solución Implementada

Se ha creado un **Trait** `PdfMemoryManagement` que maneja automáticamente el límite de memoria para generación de PDFs.

### Ubicación del Trait

```
modules/Report/Traits/PdfMemoryManagement.php
```

### Métodos Disponibles

1. **`increaseMemoryLimit($limit = '512M')`**
   - Aumenta el límite de memoria solo si es necesario
   - Compara el límite actual con el solicitado
   - Logea el cambio en `storage/logs/laravel.log`
   - Por defecto usa 512M (suficiente para la mayoría de reportes)

2. **`freeMemory()`**
   - Ejecuta el recolector de basura de PHP
   - Libera memoria después de generar el PDF
   - Recomendado llamarlo después de generar PDFs grandes

## Cómo Usar en Controladores de Reportes

### Paso 1: Importar el Trait

```php
use Modules\Report\Traits\PdfMemoryManagement;

class MiReporteController extends Controller
{
    use PdfMemoryManagement;
    
    // ... resto del código
}
```

### Paso 2: Aumentar Memoria Antes de Generar el PDF

```php
public function exportPdf(Request $request)
{
    // Aumentar memoria antes de procesar
    $this->increaseMemoryLimit('512M'); // O '256M', '1G', etc.
    
    // ... lógica de consultas y datos ...
    
    $pdf = PDF::loadView('mi-reporte.pdf', $data);
    
    // Liberar memoria después de generar
    $this->freeMemory();
    
    return $pdf->stream('reporte.pdf');
}
```

### Ejemplo Completo

```php
<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Report\Traits\PdfMemoryManagement;

class ReportItemSoldController extends Controller
{
    use PdfMemoryManagement;

    public function export(Request $request, $type)
    {
        // Aumentar límite de memoria
        $this->increaseMemoryLimit('512M');
        
        switch ($type) {
            case 'excel':
                return $this->excel($request);
                
            default:
                $result = $this->pdf($request);
                $this->freeMemory(); // Liberar memoria
                return $result;
        }
    }
    
    private function pdf(Request $request)
    {
        // ... consultas y datos ...
        
        $pdf = PDF::loadView('report::items-sold.pdf', compact('data'));
        return $pdf->stream('reporte.pdf');
    }
}
```

## Límites de Memoria Recomendados

| Tipo de Reporte | Memoria Recomendada | Descripción |
|-----------------|---------------------|-------------|
| Reportes simples | `256M` | Menos de 100 registros, pocas columnas |
| Reportes medianos | `512M` | 100-1000 registros, tablas estándar |
| Reportes grandes | `1G` | Más de 1000 registros, múltiples tablas |
| Reportes muy grandes | `2G` | Procesamiento masivo de datos |

## Ventajas de Esta Solución

1. ✅ **No requiere modificar php.ini** - Se ajusta dinámicamente por script
2. ✅ **Inteligente** - Solo aumenta si es necesario (compara límite actual vs solicitado)
3. ✅ **Reutilizable** - Un trait para todos los controladores
4. ✅ **Logging** - Registra cambios de memoria para debugging
5. ✅ **Limpieza automática** - Libera memoria con `freeMemory()`
6. ✅ **Fácil de aplicar** - Solo agregar `use` y llamar método

## Controladores Ya Actualizados

- ✅ `ReportItemSoldController` - Reporte de artículos vendidos

## Controladores Pendientes de Actualizar

Los siguientes controladores generan PDFs y pueden beneficiarse del trait:

```
modules/Report/Http/Controllers/
├── ReportSalesBookController.php
├── ReportSaleNoteController.php
├── ReportSaleConsolidatedController.php
├── ReportRemissionController.php
├── ReportUserCommissionController.php
├── ReportOrderNoteGeneralController.php
├── ReportOrderNoteConsolidatedController.php
├── ReportQuotationController.php
├── ReportDocumentPosController.php
├── ReportIncomeSummaryController.php
├── ReportDocumentController.php
├── ReportCommissionController.php
├── ReportCashController.php
├── ReportTaxController.php
└── ReportPurchaseController.php
```

## Monitoreo

Para verificar que la memoria se está ajustando correctamente, revisa los logs:

```bash
tail -f storage/logs/laravel.log | grep "Memoria aumentada"
```

Deberías ver mensajes como:
```
[2025-10-16 11:55:22] local.INFO: Memoria aumentada de 128M a 512M para generación de PDF
```

## Notas Importantes

- El límite de memoria solo se puede **aumentar**, no disminuir durante la ejecución del script
- Si el servidor tiene un límite muy bajo en `php.ini`, puede que no se pueda aumentar más allá de ese límite
- En servidores compartidos, puede haber restricciones del hosting
- El método `freeMemory()` ayuda pero no garantiza liberar toda la memoria inmediatamente

## Solución de Problemas

### Error: "Allowed memory size exhausted" persiste

1. Aumenta el límite: `$this->increaseMemoryLimit('1G');`
2. Verifica que no haya loops infinitos o memory leaks
3. Considera paginar los datos en lugar de cargar todo a la vez
4. Optimiza las consultas SQL para reducir datos innecesarios

### El límite no aumenta

- Verifica los logs para ver si hay restricciones del servidor
- Algunos hostings no permiten cambiar `memory_limit` vía `ini_set()`
- En ese caso, contacta al administrador del servidor

## Autor

- Fecha: 2025-10-16
- Contexto: Solución para problemas de memoria en generación de reportes PDF
