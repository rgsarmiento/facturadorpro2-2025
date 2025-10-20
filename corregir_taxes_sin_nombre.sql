-- ================================================
-- Script para corregir impuestos sin nombre
-- ================================================

-- 1. IDENTIFICAR impuestos sin nombre o con nombre vacío
SELECT
    id,
    name,
    code,
    rate,
    CASE
        WHEN name IS NULL THEN '❌ NULL'
        WHEN name = '' THEN '❌ VACÍO'
        ELSE '✓ OK'
    END as estado
FROM taxes
WHERE name IS NULL OR name = ''
ORDER BY id;

-- 2. CONTAR cuántos impuestos tienen problemas
SELECT
    COUNT(*) as total_con_problemas,
    SUM(CASE WHEN name IS NULL THEN 1 ELSE 0 END) as nombre_null,
    SUM(CASE WHEN name = '' THEN 1 ELSE 0 END) as nombre_vacio
FROM taxes
WHERE name IS NULL OR name = '';

-- 3. VER si hay documentos usando estos impuestos problemáticos
SELECT
    t.id as tax_id,
    t.name as tax_name,
    t.code,
    t.rate,
    COUNT(DISTINCT di.id) as items_usando_este_impuesto
FROM taxes t
LEFT JOIN document_items di ON di.tax_id = t.id
WHERE t.name IS NULL OR t.name = ''
GROUP BY t.id, t.name, t.code, t.rate
ORDER BY items_usando_este_impuesto DESC;

-- ================================================
-- CORRECCIONES SUGERIDAS (ejecutar manualmente)
-- ================================================

-- Opción A: Asignar nombre genérico basado en el rate
-- UPDATE taxes
-- SET name = CONCAT('Impuesto ', rate, '%')
-- WHERE (name IS NULL OR name = '') AND rate IS NOT NULL;

-- Opción B: Asignar nombre por código si existe
-- UPDATE taxes
-- SET name = CONCAT('Impuesto ', code)
-- WHERE (name IS NULL OR name = '') AND code IS NOT NULL;

-- Opción C: Asignar nombre específico manualmente
-- UPDATE taxes SET name = 'IVA 19%' WHERE id = 1;
-- UPDATE taxes SET name = 'IVA 5%' WHERE id = 2;
-- UPDATE taxes SET name = 'INC' WHERE id = 3;

-- ================================================
-- VERIFICACIÓN FINAL
-- ================================================

-- Verificar que no queden impuestos sin nombre
-- SELECT COUNT(*) as impuestos_sin_nombre
-- FROM taxes
-- WHERE name IS NULL OR name = '';
-- (Debe retornar 0)
