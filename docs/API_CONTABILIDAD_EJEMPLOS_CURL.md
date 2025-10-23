# 🔧 Ejemplos de Pruebas con cURL - API Contabilidad

## 📋 Configuración Inicial

```bash
# Variables de entorno (personaliza estos valores)
API_BASE="http://torres.facturadorpro2.oo/api/contabilidad"
API_TOKEN="FRhP2rsd318LzTvLtqfGMAW6E6QvtnA0Bks2sGK58nyL2TtNew"
```

---

## 1️⃣ Plan Único de Cuentas (PUC)

### 1.1 Listar Todas las Cuentas

```bash
curl -X GET "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 1.2 Listar Cuentas con Filtros

```bash
# Filtrar por tipo de cuenta y activas
curl -X GET "${API_BASE}/cuentas-contables?tipo_cuenta=activo&activa=1&per_page=20" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Buscar cuentas por texto
curl -X GET "${API_BASE}/cuentas-contables?search=caja" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por nivel jerárquico
curl -X GET "${API_BASE}/cuentas-contables?nivel=3&permite_movimiento=1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 1.3 Obtener Árbol de Cuentas (Jerárquico)

```bash
curl -X GET "${API_BASE}/cuentas-contables/tree" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 1.4 Obtener Cuenta Específica

```bash
# Reemplaza {codigo} con el código real de la cuenta (ej: 1105, 110505)
curl -X GET "${API_BASE}/cuentas-contables/1105" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 1.5 Crear Nueva Cuenta

**Nota**: Use `cuenta_padre_codigo` (código de la cuenta padre) en lugar de `cuenta_padre_id`. El nivel se calcula automáticamente.

```bash
curl -X POST "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "codigo": "110505",
    "nombre": "Caja General",
    "tipo_cuenta": "activo",
    "naturaleza": "debito",
    "cuenta_padre_codigo": "1105",
    "activa": true,
    "permite_movimiento": true,
    "saldo_inicial": 0,
    "descripcion": "Caja general de la empresa",
    "codigo_niif": "1105"
  }'
```

### 1.6 Actualizar Cuenta Existente

**Nota**: Use `cuenta_padre_codigo` si desea cambiar la cuenta padre. El nivel se recalcula automáticamente.

```bash
# Reemplaza {codigo} con el código de la cuenta a actualizar (ej: 110505)
curl -X PUT "${API_BASE}/cuentas-contables/110505" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "codigo": "110505",
    "nombre": "Caja General Principal",
    "tipo_cuenta": "activo",
    "naturaleza": "debito",
    "cuenta_padre_codigo": "1105",
    "activa": true,
    "permite_movimiento": true,
    "saldo_inicial": 1000000
  }'
```

### 1.7 Eliminar Cuenta

```bash
# Solo se pueden eliminar cuentas sin movimientos y sin cuentas hijas
# Reemplaza {codigo} con el código de la cuenta a eliminar (ej: 110505)
curl -X DELETE "${API_BASE}/cuentas-contables/110505" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 2️⃣ Asientos Contables

### 2.1 Listar Todos los Asientos

```bash
curl -X GET "${API_BASE}/asientos" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 2.2 Listar Asientos con Filtros

```bash
# Filtrar por rango de fechas
curl -X GET "${API_BASE}/asientos?fecha_inicio=2025-10-01&fecha_fin=2025-10-31" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por estado
curl -X GET "${API_BASE}/asientos?estado=CONFIRMADO" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por tipo de comprobante
curl -X GET "${API_BASE}/asientos?tipo_comprobante_id=1" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Combinar filtros
curl -X GET "${API_BASE}/asientos?fecha_inicio=2025-10-01&estado=BORRADOR&per_page=50" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 2.3 Obtener Asiento Específico

```bash
# Incluye los detalles del asiento
curl -X GET "${API_BASE}/asientos/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 2.4 Crear Nuevo Asiento Contable

```bash
curl -X POST "${API_BASE}/asientos" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "fecha_asiento": "2025-10-23",
    "tipo_comprobante_id": 1,
    "concepto": "Apertura de caja del día",
    "detalles": [
      {
        "cuenta_contable_codigo": "110505",
        "concepto": "Efectivo recibido en caja",
        "debito": 500000,
        "credito": 0,
        "tercero_number": null
      },
      {
        "cuenta_contable_codigo": "310505",
        "concepto": "Aporte de socios",
        "debito": 0,
        "credito": 500000,
        "tercero_number": "123456789"
      }
    ]
  }'
```

### 2.5 Crear Asiento Más Complejo (Múltiples Detalles)

```bash
curl -X POST "${API_BASE}/asientos" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "fecha_asiento": "2025-10-23",
    "tipo_comprobante_id": 2,
    "concepto": "Pago de nómina del mes",
    "detalles": [
      {
        "cuenta_contable_codigo": "510506",
        "concepto": "Salarios del mes",
        "debito": 5000000,
        "credito": 0
      },
      {
        "cuenta_contable_codigo": "510510",
        "concepto": "Prestaciones sociales",
        "debito": 1000000,
        "credito": 0
      },
      {
        "cuenta_contable_codigo": "110505",
        "concepto": "Pago desde caja",
        "debito": 0,
        "credito": 4000000
      },
      {
        "cuenta_contable_codigo": "111005",
        "concepto": "Pago desde banco",
        "debito": 0,
        "credito": 2000000
      }
    ]
  }'
```

### 2.6 Actualizar Asiento (Solo BORRADOR)

```bash
curl -X PUT "${API_BASE}/asientos/1" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "concepto": "Apertura de caja del día - Corregido",
    "detalles": [
      {
        "cuenta_contable_codigo": "110505",
        "concepto": "Efectivo recibido en caja",
        "debito": 600000,
        "credito": 0
      },
      {
        "cuenta_contable_codigo": "310505",
        "concepto": "Aporte de socios",
        "debito": 0,
        "credito": 600000,
        "tercero_number": "123456789"
      }
    ]
  }'
```

### 2.7 Confirmar/Aprobar Asiento

```bash
# Una vez confirmado, NO se puede editar ni eliminar
curl -X POST "${API_BASE}/asientos/1/confirmar" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 2.8 Eliminar Asiento (Solo BORRADOR)

```bash
curl -X DELETE "${API_BASE}/asientos/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 3️⃣ Catálogos

### 3.1 Listar Tipos de Comprobantes

```bash
curl -X GET "${API_BASE}/tipos-comprobantes" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.2 Listar Terceros/Personas

```bash
# Listar todos
curl -X GET "${API_BASE}/terceros" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Buscar tercero por texto
curl -X GET "${API_BASE}/terceros?search=Juan" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.3 Obtener Próximo Consecutivo

```bash
# Reemplaza {tipo_comprobante_id} con el ID real del tipo
curl -X GET "${API_BASE}/proximo-consecutivo/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 🧪 Pruebas Paso a Paso

### Flujo Completo: Crear y Confirmar Asiento

```bash
# Paso 1: Obtener tipos de comprobantes disponibles
echo "=== PASO 1: Listar Tipos de Comprobantes ==="
curl -X GET "${API_BASE}/tipos-comprobantes" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 2: Ver árbol de cuentas contables
echo -e "\n\n=== PASO 2: Árbol de Cuentas ==="
curl -X GET "${API_BASE}/cuentas-contables/tree" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 3: Obtener próximo consecutivo
echo -e "\n\n=== PASO 3: Próximo Consecutivo ==="
curl -X GET "${API_BASE}/proximo-consecutivo/1" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 4: Crear asiento en estado BORRADOR
echo -e "\n\n=== PASO 4: Crear Asiento ==="
curl -X POST "${API_BASE}/asientos" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "fecha_asiento": "2025-10-23",
    "tipo_comprobante_id": 1,
    "concepto": "Prueba de asiento contable",
    "detalles": [
      {
        "cuenta_contable_codigo": "110505",
        "concepto": "Cargo a caja",
        "debito": 100000,
        "credito": 0
      },
      {
        "cuenta_contable_codigo": "310505",
        "concepto": "Abono a ingresos",
        "debito": 0,
        "credito": 100000
      }
    ]
  }'

# Paso 5: Consultar el asiento creado (reemplaza {id} con el ID retornado)
echo -e "\n\n=== PASO 5: Consultar Asiento Creado ==="
curl -X GET "${API_BASE}/asientos/1" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 6: Confirmar el asiento
echo -e "\n\n=== PASO 6: Confirmar Asiento ==="
curl -X POST "${API_BASE}/asientos/1/confirmar" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 7: Verificar estado final
echo -e "\n\n=== PASO 7: Verificar Estado Final ==="
curl -X GET "${API_BASE}/asientos/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 💡 Tips para Pruebas

### 1. Guardar Respuestas en Archivos

```bash
# Guardar listado de cuentas
curl -X GET "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -o cuentas.json

# Guardar árbol jerárquico
curl -X GET "${API_BASE}/cuentas-contables/tree" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -o cuentas_tree.json
```

### 2. Ver Headers de Respuesta

```bash
curl -X GET "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -i
```

### 3. Modo Verbose (Debug)

```bash
curl -X GET "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -v
```

### 4. Formatear JSON de Respuesta (con jq)

```bash
# Si tienes jq instalado
curl -X GET "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer ${API_TOKEN}" | jq
```

---

## 🐛 Validación de Errores

### Error 401: Token Inválido

```bash
# Esto debería devolver error 401
curl -X GET "${API_BASE}/cuentas-contables" \
  -H "Authorization: Bearer token_invalido"
```

### Error 404: Ruta Incorrecta

```bash
# Esto debería devolver error 404
curl -X GET "http://torres.facturadorpro2.oo/cuentas-contables/tree" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### Error 422: Asiento No Balanceado

```bash
# Esto debería devolver error 422
curl -X POST "${API_BASE}/asientos" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "fecha_asiento": "2025-10-23",
    "tipo_comprobante_id": 1,
    "concepto": "Asiento NO balanceado",
    "detalles": [
      {
        "cuenta_contable_codigo": "110505",
        "concepto": "Débito",
        "debito": 100000,
        "credito": 0
      },
      {
        "cuenta_contable_codigo": "310505",
        "concepto": "Crédito",
        "debito": 0,
        "credito": 50000
      }
    ]
  }'
```

---

## 📝 Notas Finales

1. **Reemplaza las variables** `${API_BASE}` y `${API_TOKEN}` con tus valores reales
2. **IDs dinámicos**: Los ejemplos usan IDs como `1`, `5`, `10` - reemplázalos con IDs válidos de tu BD
3. **Windows PowerShell**: Si usas PowerShell, ajusta las comillas simples por dobles
4. **Fechas**: Usa formato `YYYY-MM-DD` siempre
5. **Balance**: Los asientos DEBEN estar balanceados (Débito = Crédito) para confirmarse

