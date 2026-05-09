# REPORTE EXHAUSTIVO - TG GESTIÓN ESTABLOS V10.7

## 📋 RESUMEN EJECUTIVO

Sistema operativo para gestión de **establo con módulos de ventas, inventario, caja, fiados y contribuciones religiosas**. Base de datos SQLite con capacidad de fallback a MySQL. Completamente funcional en modo offline.

---

## 🗂️ ESTRUCTURA DE BASE DE DATOS (11 TABLAS)

### 1. **USUARIOS** 
- **Propósito:** Autenticación y control de acceso
- **Campos clave:** username, password (hasheada), role (superadmin/admin/vendedor)
- **Usuario por defecto:** AdanGL / Agl252002 / superadmin

### 2. **PRODUCTOS**
- **Propósito:** Inventario de artículos
- **Campos principales:**
  - nombre, código_barras, precio_compra, precio_venta
  - stock, stock_minimo
  - tipo_producto (producto|preparado)
  - activo (soft delete)

### 3. **VENTAS** ⭐
- **Propósito:** Todas las transacciones de venta
- **Campos principales:**
  - producto_id (foreign key a productos)
  - cantidad, total, fecha
  - vendedor, tipo_pago (efectivo|fiado|tarjeta|transferencia)
  - nombre_fiado, grupo_fiado, celular_fiado
  - fiado_pagado (0=pendiente, 1=pagado)
- **Relaciones:** Genera registros automáticos en tabla "registros"

### 4. **REGISTROS**
- **Propósito:** Movimientos de caja (ingresos, gastos, retiros, arcas)
- **Tipos soportados:**
  - ingreso, egreso, gasto, merma (circulante)
  - fiado (generado automáticamente por ventas a crédito)
  - septima, septima_especial (contribuciones religiosas)
  - arca_ingreso, arca_egreso, arca_gasto, arca_merma (servicios específicos)
- **Campos:** fecha, tipo, concepto, monto, usuario, categoria, servicio
- **Nota:** Cálculos automáticos de corte diario

### 5. **SEPTIMAS**
- **Propósito:** Gestión de contribuciones religiosas
- **Campos:** nombre_padrino, monto, tipo (normal|especial), servicio, pagado

### 6. **CORTES_CAJA**
- **Propósito:** Cierre y apertura de cajas (flexible, no diarios fijos)
- **Campos principales:**
  - fecha_apertura, fecha_cierre
  - usuario_apertura, usuario_cierre
  - saldo_inicial, saldo_final
  - ingresos_efectivo, ingresos_tarjeta, ingresos_transferencia
  - egresos
  - diferencia (saldo_final - esperado)
  - notas
- **Estado:** abierto|cerrado
- **Fórmula:** diferencia = saldo_final - (saldo_inicial + ingresos - egresos)

### 7. **CUENTAS**
- **Propósito:** Gestión de clientes con crédito
- **Campos principales:**
  - nombre_cuenta (UNIQUE), celular
  - estado_cuenta (activo|inactivo|bloqueado)
  - saldo_total (adeudo pendiente)
  - fecha_primer_compra, fecha_ultimo_compra
- **Normalización:** Evita duplicados (TRIM, LOWER, sin puntos/comas)

### 8. **CONFIRMACION_PAGOS**
- **Propósito:** Seguimiento de pagos pendientes de validar
- **Campos:** venta_id, metodo_pago, comprobante_referencia, estado, fecha_solicitud

### 9. **AUDIT_LOG** 📊
- **Propósito:** Auditoría completa de cambios
- **Registra:** usuario, acción, tabla, registro_id, datos_anteriores, datos_nuevos

### 10. **LOG_ERRORES**
- **Propósito:** Registro de errores del sistema
- **Campos:** fecha, tipo, mensaje, detalles, url

### 11. **CONFIGURACION**
- **Propósito:** Valores de configuración (clave-valor)
- **Valores iniciales:** color_tema = #0d47a1

---

## 📊 REPORTES DISPONIBLES (api/api_reportes.php v5.2)

### 1. **Inventario de Productos** `inventario_hoy`
- Listado completo: productos + preparados
- Resalta stock bajo (rojo)
- Columnas: ID, Nombre, Tipo, Código Barras, Costo, Precio, Stock, Stock Mínimo
- **Salida:** Excel .xlsx

### 2. **Análisis de Utilidad** `utilidad_productos`
- Margen de ganancia por artículo
- Columnas: Margen Unitario, Inversión Stock, Ingreso Potencial, Utilidad Potencial
- **Salida:** Excel .xlsx

### 3. **Consolidado General** `consolidado`
**8 secciones:**
1. Resumen General
2. Inventario (6 métricas)
3. Ventas-Productos (5 métricas)
4. Ventas-Preparados (5 métricas)
5. Cuentas (6 métricas)
6. Cortes de Caja (8 métricas)
7. Registros (5 métricas)
8. Séptimas (4 métricas)
9. Arcas Servicios (6 métricas)

**Salida:** Excel .xlsx multihoja

### 4. **Ventas por Período** `ventas_periodo`
- Parámetros: desde, hasta (YYYY-MM-DD)
- **Salida:** Excel .xlsx

### 5. **Exportar CSV**
- Últimas 30 días
- Incluye costo unitario, precio venta, utilidad
- **Destino:** Downloads

### 6. **Exportar JSON**
- Productos, ventas, usuarios, metadata
- **Destino:** Downloads

---

## 💰 CAPTURA DE DATOS - OPERACIONES PRINCIPALES

### **VENTAS** (api/api_ventas.php)
✅ Se captura:
- Fecha, hora, producto, cantidad, total
- Vendedor, tipo de pago
- Si es fiado: nombre, grupo, celular
- Generación automática de registros en caja

❌ NO SE CAPTURA:
- Descuentos/promociones
- Número de factura
- Margen % neto
- Cliente recurrente vs nuevo

### **FIADOS/DEUDAS** (api/api_ventas.php)
✅ Se captura:
- Nombre deudor (normalizado)
- Monto adeudado, grupo, celular
- Historial de compras
- Estado pago (pendiente/pagado)

❌ NO SE CAPTURA:
- Dirección completa
- Identificación personal
- Días en mora
- Intereses por retraso
- Acuerdos de pago formales

### **CAJA DIARIA** (api/api_caja.php)
✅ Se captura:
- Saldo inicial/final
- Desglose: efectivo, tarjeta, transferencia
- Egresos, diferencia, notas
- Apertura/cierre flexible (no diarios)

❌ NO SE CAPTURA:
- Desglose de efectivo (billetes por denominación)
- Cheques recibidos
- Depósitos bancarios confirmados
- Conciliación bancaria

### **REGISTROS/MOVIMIENTOS** (api/api_registros.php)
✅ Se captura:
- Tipo (11 tipos diferentes)
- Concepto, monto, usuario
- Categoría, servicio

❌ NO SE CAPTURA:
- Comprobantes/facturas
- Autorización de gasto
- Centro de costo
- Presupuesto vs real

### **SÉPTIMAS** (api/api_septimas.php)
✅ Se captura:
- Nombre padrino, monto, tipo, servicio
- Estado pagado

❌ NO SE CAPTURA:
- Justificación de gasto
- Presupuesto aprobado
- Documentos de sustento
- Acreedor específico

---

## 🎯 DATOS CRÍTICOS FALTANTES PARA JUNTA HACIENDA

### **PRIORIDAD ALTA** 🔴

1. **Rentabilidad % por venta**
   - Actualmente: solo valor absoluto de margen
   - Necesario: % sobre costo y sobre venta

2. **Desglose de efectivo**
   - Actualmente: total efectivo
   - Necesario: billetes de 1000, 500, 100, etc.

3. **Comprobantes de sustento**
   - Actualmente: ninguno vinculado
   - Necesario: facturas, recibos, comprobantes

4. **Análisis de rentabilidad vendedor**
   - Actualmente: no hay comisiones capturadas
   - Necesario: desempeño por vendedor

5. **Dirección/Identificación clientes**
   - Actualmente: solo nombre y teléfono
   - Necesario: para seguimiento de deudas

### **PRIORIDAD MEDIA** 🟡

6. Categorización detallada de gastos
7. Presupuestos aprobados vs real
8. Días en mora de deudas
9. Intereses por mora
10. Fecha de vencimiento de productos perecederos

### **PRIORIDAD BAJA** 🟢

11. Proveedor de cada producto
12. Análisis de estacionalidad
13. Predicción de demanda
14. Margen % por categoría
15. Rotación de inventario (salida/mes)

---

## 🖼️ MULTIMEDIA

**Logo encontrado:** ✅ `logo-agua-viva.png` en `/assets/img/`

**Otros assets:**
- `placeholder.png` (imagen genérica)
- `2-85e1f1c2.ico` (favicon)
- `assets/css/style.css` (estilos)
- `assets/js/app.js`, `login.js`, `download-handler.js` (scripts)
- `assets/vendor/` (librerías: FontAwesome, SweetAlert2)

---

## 🔐 SEGURIDAD & AUDITORÍA

✅ **Implementado:**
- Autenticación por usuario/password (hasheada)
- Roles: superadmin, admin, vendedor
- Auditoría completa en `audit_log`
- CSRF tokens
- Reconexión automática BD
- Fallback SQLite → MySQL
- Validaciones de entrada
- Normalización de nombres (evita duplicados)

---

## 📈 CAPACIDADES CRÍTICAS

1. ✅ Venta online y a crédito (fiado)
2. ✅ Gestión de deudas con historial
3. ✅ Cortes de caja flexibles (no diarios)
4. ✅ Reportes multi-formato (Excel, CSV, JSON)
5. ✅ Arcas de servicios independientes
6. ✅ Auditoría completa de cambios
7. ✅ Múltiples métodos de pago
8. ✅ Modo offline con SQLite

---

## ⚠️ LIMITACIONES ACTUALES

1. ❌ Sin gestión de proveedores
2. ❌ Sin nómina/salarios
3. ❌ Sin predicción de demanda
4. ❌ Sin análisis de margen % por transacción
5. ❌ Sin desglose de efectivo por denominación
6. ❌ Sin interfaz visual de auditoría
7. ❌ Sin comprobantes/facturas vinculadas
8. ❌ Sin análisis de rentabilidad vendedor

---

## 📁 ARCHIVOS JSON GENERADOS

✅ **REPORTE_INVENTARIO_SISTEMA.json** - Inventario completo estructurado (este documento en JSON)

---

**Generado:** 18 de enero de 2024
**Versión del Sistema:** 10.7
**Estado:** OPERACIONAL
