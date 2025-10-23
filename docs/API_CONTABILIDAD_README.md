# 📊 API de Contabilidad - Facturador PRO2

## 📋 Índice
- [Descripción General](#descripción-general)
- [Autenticación](#autenticación)
- [URL Base](#url-base)
- [Endpoints Disponibles](#endpoints-disponibles)
- [Colección Postman](#colección-postman)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Códigos de Respuesta](#códigos-de-respuesta)
- [Errores Comunes](#errores-comunes)

---

## 📝 Descripción General

API RESTful para la gestión del módulo de contabilidad de Facturador PRO2. Permite realizar operaciones CRUD sobre:

- **Plan Único de Cuentas (PUC)**: Gestión completa de cuentas contables
- **Asientos Contables**: Creación, consulta, aprobación y gestión de comprobantes
- **Catálogos**: Tipos de comprobantes, terceros y consecutivos

---

## 🔐 Autenticación

La API utiliza autenticación mediante **Bearer Token** usando el campo `api_token` de la tabla `users` del tenant.

### ¿Cómo obtener el API Token?

1. **Consultar token existente** (desde la BD del tenant):
```sql
SELECT id, name, email, api_token FROM users WHERE id = {user_id};
```

2. **Generar nuevo token** (si no existe):
```sql
UPDATE users 
SET api_token = MD5(CONCAT(email, NOW(), RAND())) 
WHERE id = {user_id};
```

### ¿Cómo usar el token?

Incluye el token en el header `Authorization` de cada petición:

```http
Authorization: Bearer {tu_api_token_aqui}
```

**Ejemplo con cURL**:
```bash
curl -X GET "http://torres.facturadorpro2.oo/api/contabilidad/cuentas-contables" \
  -H "Authorization: Bearer abc123def456ghi789"
```

---

## 🌐 URL Base

### ⚠️ IMPORTANTE: La URL DEBE incluir el prefijo `/api/contabilidad`

Laravel automáticamente agrega el prefijo `/api/` a todas las rutas definidas en `routes/api.php`.

### Producción
```
https://{tenant}.tudominio.com/api/contabilidad
```

### Desarrollo Local
```
http://{tenant}.localhost/api/contabilidad
```

### Ejemplo Real (Laragon)
```
http://torres.facturadorpro2.oo/api/contabilidad
```

**Donde `{tenant}` es el subdominio del tenant** en tu sistema multi-tenant.

### ❌ URLs INCORRECTAS (No funcionarán)
```
http://torres.facturadorpro2.oo/cuentas/tree           ❌ Falta /api/contabilidad
http://torres.facturadorpro2.oo/contabilidad/cuentas   ❌ Falta /api/
```

### ✅ URLs CORRECTAS
```
http://torres.facturadorpro2.oo/api/contabilidad/cuentas/tree  ✅
http://torres.facturadorpro2.oo/api/contabilidad/cuentas       ✅
```

---

## 📋 Endpoints Disponibles

### 1️⃣ Plan Único de Cuentas (PUC)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/cuentas-contables` | Listar cuentas (con paginación y filtros) |
| `GET` | `/cuentas-contables/{codigo}` | Obtener cuenta específica por código |
| `GET` | `/cuentas-contables/tree` | Árbol jerárquico de cuentas |
| `POST` | `/cuentas-contables` | Crear nueva cuenta |
| `PUT` | `/cuentas-contables/{codigo}` | Actualizar cuenta existente por código |
| `DELETE` | `/cuentas-contables/{codigo}` | Eliminar cuenta por código |

### 2️⃣ Asientos Contables

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/asientos-contables` | Listar asientos (con paginación y filtros) |
| `GET` | `/asientos-contables/{id}` | Obtener asiento específico |
| `POST` | `/asientos-contables` | Crear nuevo asiento |
| `PUT` | `/asientos-contables/{id}` | Actualizar asiento (solo BORRADOR) |
| `DELETE` | `/asientos-contables/{id}` | Eliminar asiento (solo BORRADOR) |
| `POST` | `/asientos-contables/{id}/confirmar` | Aprobar/Confirmar asiento |

### 3️⃣ Catálogos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/tipos-comprobantes` | Listar tipos de comprobantes |
| `GET` | `/terceros` | Listar terceros/personas |
| `GET` | `/proximo-consecutivo/{tipo_id}` | Obtener próximo consecutivo |

---

## 📦 Colección Postman

Hemos creado una colección completa de Postman con:
- ✅ Todos los endpoints documentados
- ✅ Ejemplos de peticiones y respuestas
- ✅ Variables de entorno pre-configuradas
- ✅ Documentación inline de cada endpoint

### Descargar Colección

📥 **Archivo**: `public/postman/API_Contabilidad_Collection.json`

### Importar en Postman

1. Abre Postman
2. Click en **Import**
3. Selecciona el archivo `API_Contabilidad_Collection.json`
4. Configura las variables:
   - `base_url`: Tu URL base (ej: `https://demo.tudominio.com/api/contabilidad`)
   - `api_token`: Tu token de autenticación

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Listar Cuentas Contables

**Request**:
```http
GET /api/contabilidad/cuentas-contables?per_page=20&activa=1&tipo_cuenta=activo
Authorization: Bearer abc123def456
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "codigo": "1105",
      "nombre": "Caja",
      "tipo_cuenta": "activo",
      "naturaleza": "debito",
      "nivel": 2,
      "activa": true,
      "permite_movimiento": false,
      "saldo_inicial": 0,
      "saldo_actual": 0
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95,
    "from": 1,
    "to": 20
  }
}
```

### Ejemplo 2: Crear Cuenta Contable

**Request**:
```http
POST /api/contabilidad/cuentas-contables
Authorization: Bearer abc123def456
Content-Type: application/json

{
  "codigo": "110505",
  "nombre": "Caja General",
  "tipo_cuenta": "activo",
  "naturaleza": "debito",
  "cuenta_padre_codigo": "1105",
  "descripcion": "Caja general de la empresa",
  "activa": true,
  "permite_movimiento": true,
  "requiere_tercero": false,
  "saldo_inicial": 0,
  "codigo_niif": "1105"
}
```

**Nota Importante**: 
- Use `cuenta_padre_codigo` (código de la cuenta padre) en lugar de `cuenta_padre_id`
- El `nivel` se calcula automáticamente según el código y la cuenta padre
- Si no especifica `cuenta_padre_codigo`, el nivel se calcula por la longitud del código

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Cuenta creada exitosamente",
  "data": {
    "id": 15,
    "codigo": "110505",
    "nombre": "Caja General",
    "tipo_cuenta": "activo",
    "naturaleza": "debito",
    "nivel": 3,
    "cuenta_padre_id": 5,
    "descripcion": "Caja general de la empresa",
    "activa": true,
    "permite_movimiento": true,
    "requiere_tercero": false,
    "saldo_inicial": 0,
    "saldo_actual": 0,
    "codigo_niif": "1105"
  }
}
```

### Ejemplo 3: Crear Asiento Contable

**Request**:
```http
POST /api/contabilidad/asientos-contables
Authorization: Bearer abc123def456
Content-Type: application/json

{
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
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Asiento creado exitosamente",
  "data": {
    "id": 1,
    "fecha_asiento": "2025-10-23",
    "tipo_comprobante_id": 1,
    "numero_comprobante": "CV1",
    "consecutivo": 1,
    "concepto": "Apertura de caja del día",
    "total_debito": 500000,
    "total_credito": 500000,
    "estado": "BORRADOR",
    "usuario_creacion": 1,
    "fecha_creacion": "2025-10-23T10:30:00",
    "detalles": [...]
  }
}
```

### Ejemplo 4: Confirmar Asiento

**Request**:
```http
POST /api/contabilidad/asientos-contables/1/confirmar
Authorization: Bearer abc123def456
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Asiento confirmado exitosamente",
  "data": {
    "id": 1,
    "estado": "CONFIRMADO",
    "fecha_confirmacion": "2025-10-23T10:35:00",
    "usuario_confirmacion": 1
  }
}
```

### Ejemplo 5: Filtrar Asientos por Rango de Fechas

**Request**:
```http
GET /api/contabilidad/asientos-contables?fecha_inicio=2025-10-01&fecha_fin=2025-10-31&estado=CONFIRMADO
Authorization: Bearer abc123def456
```

### Ejemplo 6: Buscar Cuentas

**Request**:
```http
GET /api/contabilidad/cuentas-contables?search=caja&permite_movimiento=1
Authorization: Bearer abc123def456
```

---

## 📊 Códigos de Respuesta HTTP

| Código | Descripción |
|--------|-------------|
| `200` | OK - Petición exitosa |
| `201` | Created - Recurso creado exitosamente |
| `400` | Bad Request - Petición mal formada |
| `401` | Unauthorized - Token inválido o no proporcionado |
| `404` | Not Found - Recurso no encontrado |
| `422` | Unprocessable Entity - Errores de validación |
| `500` | Internal Server Error - Error del servidor |

---

## ⚠️ Errores Comunes

### 1. Error 401: Token no proporcionado

**Respuesta**:
```json
{
  "success": false,
  "message": "Token de autenticación no proporcionado. Use el header: Authorization: Bearer {api_token}"
}
```

**Solución**: Incluir el header `Authorization: Bearer {token}` en la petición.

### 2. Error 422: Asiento no balanceado

**Respuesta**:
```json
{
  "success": false,
  "message": "El asiento no está balanceado. Débito y Crédito deben ser iguales"
}
```

**Solución**: Verificar que la suma de débitos sea igual a la suma de créditos.

### 3. Error 422: Solo BORRADOR puede editarse

**Respuesta**:
```json
{
  "success": false,
  "message": "Solo se pueden editar asientos en estado BORRADOR"
}
```

**Solución**: No intentes editar asientos confirmados. Crea uno nuevo si necesitas corregir.

### 4. Error 422: Validación de campos

**Respuesta**:
```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "codigo": ["El campo codigo ya ha sido tomado."],
    "detalles": ["El campo detalles debe contener al menos 2 elementos."]
  }
}
```

**Solución**: Corregir los campos según los mensajes de error.

---

## 📌 Notas Importantes

### Estados de Asientos Contables

1. **BORRADOR**: 
   - ✅ Puede editarse
   - ✅ Puede eliminarse
   - ✅ Puede confirmarse (si está balanceado)

2. **CONFIRMADO**: 
   - ❌ NO puede editarse
   - ❌ NO puede eliminarse
   - ✅ Solo lectura

3. **ANULADO**: 
   - Asientos eliminados (soft delete)
   - Aparecen en listados si se filtra por estado "ANULADO"

### Balance de Asientos

Para confirmar un asiento, **DEBE** estar balanceado:
```
∑ Débitos = ∑ Créditos
```

Si no está balanceado, no se puede confirmar.

### Jerarquía de Cuentas

- Las cuentas tienen estructura jerárquica (cuenta padre/hijas)
- Solo las cuentas de **último nivel** permiten movimientos
- No se puede eliminar una cuenta con cuentas hijas

### Paginación

Todos los listados están paginados:
- **Default**: 15 registros por página
- **Personalizar**: `?per_page=N`
- **Máximo recomendado**: 100 registros por página

---

## 🔧 Configuración de Variables de Entorno (Postman)

En Postman, configura estas variables para facilitar las pruebas:

| Variable | Valor de Ejemplo | Descripción |
|----------|------------------|-------------|
| `base_url` | `http://torres.facturadorpro2.oo/api/contabilidad` | URL base de la API (⚠️ DEBE incluir `/api/contabilidad`) |
| `api_token` | `wWnKQz78glmPelOYNzq9B11b71PgQNKOvlYP8wsdTGqurCaqZovPq31KvFqf` | Tu token de autenticación |
| `cuenta_id` | `10` | ID de cuenta para pruebas |
| `asiento_id` | `1` | ID de asiento para pruebas |

---

## 🚨 Resolución de Problemas Comunes

### Error: "No se encontró la URL especificada" (404)

**Problema**:
```json
{
  "success": false,
  "message": "No se encontró la URL especificada",
  "file": "...\\Illuminate\\Routing\\RouteCollection.php",
  "line": 179
}
```

**Causas comunes**:

1. ❌ **Falta el prefijo `/api/` en la URL**:
   ```
   INCORRECTO: http://torres.facturadorpro2.oo/contabilidad/cuentas
   CORRECTO:   http://torres.facturadorpro2.oo/api/contabilidad/cuentas
   ```

2. ❌ **Falta el prefijo `/contabilidad/`**:
   ```
   INCORRECTO: http://torres.facturadorpro2.oo/api/cuentas
   CORRECTO:   http://torres.facturadorpro2.oo/api/contabilidad/cuentas
   ```

3. ❌ **URL mal formada**:
   ```
   INCORRECTO: http://torres.facturadorpro2.oo/cuentas/tree
   CORRECTO:   http://torres.facturadorpro2.oo/api/contabilidad/cuentas/tree
   ```

**Solución**: Todas las URLs DEBEN seguir este patrón:
```
http://{tenant}.{dominio}/api/contabilidad/{endpoint}
```

### Ejemplos de URLs Correctas

```bash
# Listar cuentas
GET http://torres.facturadorpro2.oo/api/contabilidad/cuentas-contables

# Árbol de cuentas
GET http://torres.facturadorpro2.oo/api/contabilidad/cuentas-contables/tree

# Obtener cuenta específica
GET http://torres.facturadorpro2.oo/api/contabilidad/cuentas-contables/5

# Crear asiento
POST http://torres.facturadorpro2.oo/api/contabilidad/asientos-contables

# Confirmar asiento
POST http://torres.facturadorpro2.oo/api/contabilidad/asientos-contables/10/confirmar

# Tipos de comprobantes
GET http://torres.facturadorpro2.oo/api/contabilidad/tipos-comprobantes
```

---

## 📞 Soporte

Para reportar problemas o sugerencias sobre la API:
- **Email**: soporte@tudominio.com
- **Documentación**: Ver colección de Postman incluida

---

## 📅 Versión

- **Versión de API**: 1.0
- **Última actualización**: Octubre 2025
- **Compatibilidad**: Facturador PRO2

---

## 🎯 Roadmap Futuro

Funcionalidades planificadas para próximas versiones:

- [ ] Reportes contables (balance, estado de resultados)
- [ ] Exportación de asientos a Excel/PDF
- [ ] Cierre de periodos contables
- [ ] Asientos recurrentes/plantillas
- [ ] Consulta de mayor y balance de cuentas
- [ ] Webhooks para eventos contables
