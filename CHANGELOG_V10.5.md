# Changelog v10.5 (Preliminar)

## Cambios Principales vs v10

### 1. Flujo de Cuentas "A Cuenta" ✨

**Antes (v10)**:
- Concepto de "Fiado" poco claro
- Nombres sensibles a puntuación: "Hugo C." ≠ "Hugo C"
- Búsqueda manual en Ventas sin sugerencias
- Edición de cuentas podía crear duplicados

**Ahora (v10.5)**:
- Renombrado a "A Cuenta" para mayor legibilidad
- Normalización inteligente de nombres (ignora puntuación y mayúsculas)
- Autocomplete en Ventas con sugerencias filtradas
- Selección automática de grupo/región al elegir cuenta
- Edición segura sin duplicados
- Estado visible: "Sin adeudo" o monto del adeudo

---

### 2. Formulario de Cuentas Rediseñado

**Antes (v10)**:
```
Nombre
Apellido
Grupo/Región (campo de texto libre)
Celular
Dinero inicial
```

**Ahora (v10.5)**:
```
Nombre (campo de texto)
Inicial de Apellido (single char)
Grupo/Región (selector de lista fija)
Número de Celular
```

**Ventajas**:
- Menos campos = formulario más rápido
- Grupo/Región estandarizado
- Sin compos financieros en creación (se manejan en ventas)

---

### 3. Cortes de Caja Funcionales

**Antes (v10)**:
- Prompts nativos del navegador poco confiables
- Botones sin estado
- Flujo manual y confuso

**Ahora (v10.5)**:
- Formularios SweetAlert2 profesionales
- Botones con estado dinámico:
  - `Abrir Corte` deshabilitado si hay uno abierto
  - `Cerrar Corte` deshabilitado si no hay abierto
  - `Ver Corte Actual` acceso rápido
- Apertura: saldo inicial + usuario
- Cierre: saldo final + diferencia + notas
- Validaciones mejoradas

---

### 4. Reportes Excel Profesionales

**Antes (v10)**:
- Hoja Deudores (redundante con datos de ventas)
- Sin resumen general
- Totales ausentes en muchas hojas
- Presentación básica

**Ahora (v10.5)**:

**Nueva estructura**:
1. **Resumen** (hoja nueva)
   - Métricas de todas las áreas
   - Totales de ventas, cuentas, cortes, arcas
   - Nota aclaratoria sobre consolidación de Deudores

2. **Inventario-Productos** → Total items + stock total
3. **Inventario-Preparados** → Total items + stock total
4. **Ventas-Productos** → Total ventas + unidades + monto + fiados pendientes
5. **Ventas-Preparados** → Total ventas + unidades + monto + fiados pendientes
6. **Registros** → Total registros + monto acumulado
7. **Séptimas** → Total + pagadas/pendientes + monto
8. **Arcas Servicios** → Resumen por servicio + neto
9. **Cortes Caja** → Accumulados de saldo, ingresos, egresos
10. **Cuentas** → Total cuentas + saldo + adeudo pendiente

**Eliminado**:
- Hoja "Deudores" (datos ahora en "Cuentas" con adeudo_pendiente calculado)

**Mejoras visuales**:
- Encabezados azul oscuro con texto blanco
- Filas alternadas de color
- Bordes y alineación uniformes
- Ancho de columnas automático

---

### 5. Backend API Mejorado

#### api_ventas.php
```php
// NUEVO: Almacenamiento de región y celular por venta
INSERT INTO ventas (..., grupo_fiado, celular_fiado) 
VALUES (..., ?, ?)

// NUEVO: Normalización al buscar/emparejar
REPLACE(REPLACE(LOWER(TRIM(nombre)), '.', ''), ',', '') = REPLACE(...)

// NUEVO: Reactivación automática
UPDATE cuentas SET estado_cuenta='activo' WHERE id=? AND estado_cuenta='inactivo'
```

#### api_caja.php
```php
// Endpoints mejorados con validaciones
POST /api_caja.php?accion=abrir_corte
POST /api_caja.php?accion=cerrar_corte
GET /api_caja.php?accion=corte_actual
```

#### api_reportes.php
```php
// Nueva sección Resumen
$summary = $spreadsheet->getActiveSheet();
$summary->setTitle('Resumen');
// ... 8 secciones de métricas

// Eliminado: Generación de hoja Deudores
// $sheet3 = ... setTitle('Deudores'); // ❌ REMOVIDO

// NUEVO: Totales en cada hoja
$sheet2->setCellValue('A'.$i, 'TOTAL VENTAS PRODUCTOS');
// ... siguientes celdas con cálculos
```

---

### 6. Frontend Mejorado

#### app.js

**Nuevas funciones**:
- `ventas.normalizarCuenta()` - Normalización de nombres
- `ventas.obtenerAdeudoCuenta()` - Consulta de adeudo pendiente
- `ventas.actualizarEstadoCuentaUI()` - Actualiza card de estado
- `cortes.actualizarBotonesCorte()` - Habilita/deshabilita botones

**Cambios en flujos existentes**:
- Búsqueda de cuentas ahora usa `ventas.sugerirCuenta()`
- Edición de cuentas verifica duplicados con normalización
- Corte usa SweetAlert en lugar de prompts

#### dashboard.php

**Nuevos elementos**:
- `#cuenta-seleccionada-info` - Card con estado de cuenta y adeudo
- Botones Corte con clases dinámicas `disabled`
- FormId para modal de Cuentas

---

### 7. Cambios de Base de Datos

**Nueva estructura (compatible con v10)**:

```sql
-- Si no existen, se crean en init
CREATE TABLE IF NOT EXISTS cortes_caja (
  id INTEGER PRIMARY KEY,
  fecha_apertura DATETIME,
  fecha_cierre DATETIME,
  usuario_apertura VARCHAR(100),
  usuario_cierre VARCHAR(100),
  saldo_inicial DECIMAL,
  ingresos_efectivo DECIMAL,
  ingresos_tarjeta DECIMAL,
  ingresos_transferencia DECIMAL,
  egresos DECIMAL,
  saldo_final DECIMAL,
  diferencia DECIMAL,
  estado ENUM('abierto','cerrado'),
  notas TEXT
);

-- Campos nuevos en ventas (si no existen)
ALTER TABLE ventas ADD COLUMN grupo_fiado VARCHAR(50);
ALTER TABLE ventas ADD COLUMN celular_fiado VARCHAR(20);

-- Cuentas (estructura expandida pero compatible)
CREATE TABLE IF NOT EXISTS cuentas (
  id INTEGER PRIMARY KEY,
  nombre_cuenta VARCHAR(255),
  celular VARCHAR(20),
  estado_cuenta ENUM('activo','inactivo','bloqueado'),
  saldo_total DECIMAL,
  fecha_primer_compra DATETIME,
  fecha_ultimo_compra DATETIME,
  notas TEXT
);
```

---

## ✅ Validaciones

- **PHP Syntax**: ✅ Sin errores
- **MySQL Compatibility**: ✅ Queries compatibles
- **SQLite**: ✅ Probado
- **Backward Compatibility**: ✅ Datos v10 se cargan sin cambios

---

## 🚀 Próximas Mejoras (post-v10.5)

- [ ] Formatos de moneda uniformes en Excel
- [ ] Más estilos avanzados en celdas
- [ ] Documentación extendida de APIs REST
- [ ] Tests unitarios
- [ ] Dashboard de analytics
- [ ] Integración de pagos en línea
- [ ] Respaldo automático a cloud

---

## 📝 Notas de Testing

**Estado Actual**: Preliminar  
**Cambios Validados**: Sintaxis PHP, compatibilidad BD, flujos principales  
**Pendiente Revisión**: Usuario debe confirmar funcionalidad visual y UX

**Para Revisor**:
1. Prueba creación de cuenta nueva
2. Prueba edición de cuenta existente
3. Realiza venta con "A Cuenta"
4. Abre y cierra un Corte
5. Genera Excel Consolidado
6. Verifica datos en pestaña Resumen y Cuentas

---

**v10.5 Preliminar**  
Abril 2026
