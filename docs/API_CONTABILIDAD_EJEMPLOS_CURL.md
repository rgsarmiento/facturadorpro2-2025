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
# Incluye los detalles del asiento - usar numero_comprobante
curl -X GET "${API_BASE}/asientos/CV1" \
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
# Usar numero_comprobante en la URL
curl -X PUT "${API_BASE}/asientos/CV1" \
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
# Una vez confirmado, NO se puede editar ni eliminar - usar numero_comprobante
curl -X POST "${API_BASE}/asientos/CV1/confirmar" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 2.8 Eliminar Asiento (Solo BORRADOR)

```bash
# Usar numero_comprobante en la URL
curl -X DELETE "${API_BASE}/asientos/CV1" \
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

### 3.3 Crear Tercero

```bash
curl -X POST "${API_BASE}/terceros" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "customers",
    "identity_document_type_id": 1,
    "number": "987654321",
    "name": "Juan Pérez López",
    "trade_name": "Comercial Pérez",
    "country_id": 1,
    "department_id": 5,
    "city_id": 50,
    "address": "Calle 123 # 45-67",
    "email": "juan.perez@example.com",
    "telephone": "3001234567",
    "type_person_id": 1,
    "type_regime_id": 1,
    "code": "CLI001",
    "dv": "5",
    "contact_name": "María Pérez",
    "contact_phone": "3009876543",
    "postal_code": "110111"
  }'
```

### 3.4 Actualizar Tercero

```bash
# Usar number del tercero en la URL
curl -X PUT "${API_BASE}/terceros/987654321" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "both",
    "identity_document_type_id": 1,
    "number": "987654321",
    "name": "Juan Pérez López - Actualizado",
    "trade_name": "Comercial Pérez & Asociados",
    "email": "juan.nuevo@example.com",
    "telephone": "3009876543",
    "address": "Carrera 10 # 20-30",
    "type_person_id": 1,
    "type_regime_id": 1
  }'
```

### 3.5 Eliminar Tercero

```bash
# Solo si NO tiene movimientos contables
curl -X DELETE "${API_BASE}/terceros/987654321" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.6 Tipos de Documentos de Identidad

```bash
curl -X GET "${API_BASE}/tipos-documentos-identidad" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.7 Listar Países

```bash
curl -X GET "${API_BASE}/paises" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.8 Listar Departamentos

```bash
# Todos los departamentos
curl -X GET "${API_BASE}/departamentos" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por país
curl -X GET "${API_BASE}/departamentos?country_id=1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.9 Listar Ciudades

```bash
# Todas las ciudades
curl -X GET "${API_BASE}/ciudades" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por departamento
curl -X GET "${API_BASE}/ciudades?department_id=5" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.10 Tipos de Persona

```bash
curl -X GET "${API_BASE}/tipos-persona" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.11 Tipos de Régimen

```bash
curl -X GET "${API_BASE}/tipos-regimen" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 3.12 Obtener Próximo Consecutivo

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

# Paso 5: Consultar el asiento creado (usar numero_comprobante retornado, ej: CV1)
echo -e "\n\n=== PASO 5: Consultar Asiento Creado ==="
curl -X GET "${API_BASE}/asientos/CV1" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 6: Confirmar el asiento
echo -e "\n\n=== PASO 6: Confirmar Asiento ==="
curl -X POST "${API_BASE}/asientos/CV1/confirmar" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Paso 7: Verificar estado final
echo -e "\n\n=== PASO 7: Verificar Estado Final ==="
curl -X GET "${API_BASE}/asientos/CV1" \
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

## 4️⃣ Períodos Contables

### 4.1 Listar Períodos Contables

```bash
# Listar todos los períodos
curl -X GET "${API_BASE}/periodos-contables" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por año
curl -X GET "${API_BASE}/periodos-contables?year=2025" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por estado
curl -X GET "${API_BASE}/periodos-contables?status=open" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 4.2 Obtener Período Actual

```bash
# Obtiene el período actual (año/mes actual) o lo crea automáticamente
curl -X GET "${API_BASE}/periodos-contables/current" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 4.3 Obtener Período Específico

```bash
# Reemplaza {id} con el ID del período
curl -X GET "${API_BASE}/periodos-contables/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 4.4 Crear Nuevo Período

```bash
curl -X POST "${API_BASE}/periodos-contables" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "year": 2025,
    "month": 11
  }'
```

### 4.5 Cerrar Período Contable

**Nota**: No se puede cerrar si hay asientos en BORRADOR

```bash
# Reemplaza {id} con el ID del período
curl -X POST "${API_BASE}/periodos-contables/1/close" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "closing_notes": "Cierre mensual de octubre 2025"
  }'
```

### 4.6 Reabrir Período Cerrado

**Nota**: No se puede reabrir si el período está BLOQUEADO

```bash
curl -X POST "${API_BASE}/periodos-contables/1/reopen" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 4.7 Bloquear Período (Irreversible)

**Nota**: Una vez bloqueado, no se puede reabrir ni modificar

```bash
curl -X POST "${API_BASE}/periodos-contables/1/lock" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 5️⃣ Saldos Iniciales

### 5.1 Listar Saldos Iniciales

```bash
# Listar todos los saldos
curl -X GET "${API_BASE}/saldos-iniciales" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por período
curl -X GET "${API_BASE}/saldos-iniciales?period_id=1" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por estado
curl -X GET "${API_BASE}/saldos-iniciales?status=draft" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 5.2 Obtener Saldo Inicial Específico

```bash
curl -X GET "${API_BASE}/saldos-iniciales/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

### 5.3 Crear Saldos Iniciales en Lote

**Nota**: La suma de débitos DEBE ser igual a la suma de créditos

```bash
curl -X POST "${API_BASE}/saldos-iniciales" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "balance_date": "2025-01-01",
    "period_id": 1,
    "balances": [
      {
        "cuenta_contable_codigo": "110505",
        "person_number": null,
        "debito": 5000000,
        "credito": 0,
        "notes": "Saldo inicial caja"
      },
      {
        "cuenta_contable_codigo": "130505",
        "person_number": "123456789",
        "debito": 3000000,
        "credito": 0,
        "notes": "Saldo cliente Juan Pérez"
      },
      {
        "cuenta_contable_codigo": "220505",
        "person_number": "987654321",
        "debito": 0,
        "credito": 2000000,
        "notes": "Saldo proveedor ABC Ltda"
      },
      {
        "cuenta_contable_codigo": "310505",
        "person_number": null,
        "debito": 0,
        "credito": 6000000,
        "notes": "Capital inicial"
      }
    ]
  }'
```

### 5.4 Validar Balanceo de Saldos (Tiempo Real)

```bash
# Valida si débitos = créditos sin guardar
curl -X POST "${API_BASE}/saldos-iniciales/validate" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "balances": [
      {
        "cuenta_contable_codigo": "110505",
        "debito": 5000000,
        "credito": 0
      },
      {
        "cuenta_contable_codigo": "310505",
        "debito": 0,
        "credito": 5000000
      }
    ]
  }'
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "total_debito": 5000000,
    "total_credito": 5000000,
    "diferencia": 0,
    "is_balanced": true
  }
}
```

### 5.5 Contabilizar Saldos Iniciales

**Nota**: Genera automáticamente el asiento de apertura y actualiza los saldos de las cuentas

```bash
curl -X POST "${API_BASE}/saldos-iniciales/post" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "period_id": 1,
    "fecha_asiento": "2025-01-01",
    "concepto": "Asiento de apertura - Saldos iniciales 2025"
  }'
```

### 5.6 Eliminar Saldo Inicial

**Nota**: Solo se pueden eliminar saldos en estado DRAFT (no contabilizados)

```bash
curl -X DELETE "${API_BASE}/saldos-iniciales/1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 6️⃣ Reportes Contables

### 6.1 Balance de Prueba

```bash
# Balance de prueba básico
curl -X GET "${API_BASE}/reportes/balance-prueba" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Con filtros de fecha
curl -X GET "${API_BASE}/reportes/balance-prueba?fecha_inicio=2025-01-01&fecha_fin=2025-10-27" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por nivel de cuenta
curl -X GET "${API_BASE}/reportes/balance-prueba?nivel=3&fecha_inicio=2025-01-01&fecha_fin=2025-10-27" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

**Respuesta esperada**:
```json
{
  "success": true,
  "data": {
    "fecha_inicio": "2025-01-01",
    "fecha_fin": "2025-10-27",
    "cuentas": [
      {
        "codigo": "110505",
        "nombre": "Caja General",
        "naturaleza": "debito",
        "debito": 10000000,
        "credito": 3000000,
        "saldo": 7000000
      }
    ],
    "totales": {
      "total_debito": 50000000,
      "total_credito": 50000000,
      "diferencia": 0
    }
  }
}
```

### 6.2 Balance General

```bash
# Balance general (Activos vs Pasivos + Patrimonio)
curl -X GET "${API_BASE}/reportes/balance-general" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Con fecha de corte
curl -X GET "${API_BASE}/reportes/balance-general?fecha_corte=2025-10-27" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

**Respuesta esperada**:
```json
{
  "success": true,
  "data": {
    "fecha_corte": "2025-10-27",
    "activos": {
      "cuentas": [
        {
          "codigo": "1105",
          "nombre": "Caja",
          "saldo": 7000000
        }
      ],
      "total": 25000000
    },
    "pasivos": {
      "cuentas": [
        {
          "codigo": "2205",
          "nombre": "Proveedores",
          "saldo": 10000000
        }
      ],
      "total": 10000000
    },
    "patrimonio": {
      "cuentas": [
        {
          "codigo": "3105",
          "nombre": "Capital Social",
          "saldo": 15000000
        }
      ],
      "total": 15000000
    },
    "validacion": {
      "activos": 25000000,
      "pasivos_patrimonio": 25000000,
      "diferencia": 0,
      "balanced": true
    }
  }
}
```

### 6.3 Mayor Auxiliar por Cuenta

```bash
# Mayor auxiliar de una cuenta específica
curl -X GET "${API_BASE}/reportes/mayor-auxiliar?cuenta_codigo=110505" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Con rango de fechas
curl -X GET "${API_BASE}/reportes/mayor-auxiliar?cuenta_codigo=110505&fecha_inicio=2025-01-01&fecha_fin=2025-10-27" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Incluir terceros
curl -X GET "${API_BASE}/reportes/mayor-auxiliar?cuenta_codigo=130505&incluir_tercero=1" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

**Respuesta esperada**:
```json
{
  "success": true,
  "data": {
    "cuenta": {
      "codigo": "110505",
      "nombre": "Caja General",
      "naturaleza": "debito"
    },
    "fecha_inicio": "2025-01-01",
    "fecha_fin": "2025-10-27",
    "saldo_inicial": 5000000,
    "movimientos": [
      {
        "fecha": "2025-01-15",
        "comprobante": "CV1",
        "concepto": "Venta de contado",
        "tercero": "Juan Pérez",
        "debito": 1000000,
        "credito": 0,
        "saldo": 6000000
      }
    ],
    "totales": {
      "total_debito": 8000000,
      "total_credito": 3000000,
      "saldo_final": 10000000
    }
  }
}
```

### 6.4 Libro Diario

```bash
# Libro diario completo
curl -X GET "${API_BASE}/reportes/libro-diario" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Con rango de fechas
curl -X GET "${API_BASE}/reportes/libro-diario?fecha_inicio=2025-10-01&fecha_fin=2025-10-27" \
  -H "Authorization: Bearer ${API_TOKEN}"

# Filtrar por tipo de comprobante
curl -X GET "${API_BASE}/reportes/libro-diario?tipo_comprobante_id=1&fecha_inicio=2025-10-01" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

**Respuesta esperada**:
```json
{
  "success": true,
  "data": {
    "fecha_inicio": "2025-10-01",
    "fecha_fin": "2025-10-27",
    "asientos": [
      {
        "numero_comprobante": "CV1",
        "fecha": "2025-10-15",
        "tipo_comprobante": "Comprobante de Venta",
        "concepto": "Venta de contado",
        "estado": "CONFIRMADO",
        "detalles": [
          {
            "cuenta_codigo": "110505",
            "cuenta_nombre": "Caja General",
            "concepto": "Ingreso por venta",
            "debito": 1000000,
            "credito": 0
          },
          {
            "cuenta_codigo": "413505",
            "cuenta_nombre": "Ventas Producto A",
            "concepto": "Venta producto",
            "debito": 0,
            "credito": 1000000
          }
        ],
        "total_debito": 1000000,
        "total_credito": 1000000
      }
    ],
    "totales": {
      "total_asientos": 25,
      "total_debito": 50000000,
      "total_credito": 50000000
    }
  }
}
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

### Error 422: Eliminar Tercero con Movimientos

```bash
# Esto debería devolver error 422 si el tercero tiene movimientos contables
curl -X DELETE "${API_BASE}/terceros/123456789" \
  -H "Authorization: Bearer ${API_TOKEN}"
```

---

## 📝 Notas Finales

1. **Reemplaza las variables** `${API_BASE}` y `${API_TOKEN}` con tus valores reales
2. **IDs dinámicos**: Los ejemplos usan IDs como `1`, `5`, `10` - reemplázalos con IDs válidos de tu BD
3. **Windows PowerShell**: Si usas PowerShell, ajusta las comillas simples por dobles
4. **Fechas**: Usa formato `YYYY-MM-DD` siempre
5. **Balance**: Los asientos DEBEN estar balanceados (Débito = Crédito) para confirmarse
6. **Terceros**: Use el campo `number` (número de documento) como identificador en URLs

