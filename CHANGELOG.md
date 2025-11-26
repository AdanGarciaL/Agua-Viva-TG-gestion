# 📋 Historial de Cambios - TG Gestión

## [5.0.0] - Noviembre 2025 - "Offline Edition"

### 🎨 Nuevas Características

#### Personalización Visual
- ✅ **Selector de color libre** - Elige cualquier color del espectro completo (millones de opciones)
- ✅ **Gradientes dinámicos adaptativos** - Los botones mantienen su identidad (verde=Cobrar, rojo=Eliminar, amarillo=Editar) pero se mezclan con tu color elegido
- ✅ **Sistema de color inteligente** - Generación automática de:
  - Color primario (el que eliges)
  - Color secundario (20% más oscuro)
  - Color de acento (20% más claro)
  - Versión translúcida para efectos focus
- ✅ **Modo oscuro mejorado** - Se adapta a cualquier color personalizado
- ✅ **Persistencia de preferencias** - Tu color y tema se guardan en localStorage

#### Tarjetas de Estadísticas
- ✅ **Gradientes inteligentes en stat-cards** - Cada tarjeta mantiene su color inicial distintivo (púrpura, rosa, azul, verde, naranja, turquesa) pero se gradúa hacia tu color elegido
- ✅ **Adaptación automática** - Las tarjetas se ajustan entre tema claro y oscuro
- ✅ **Consistencia visual** - Todo el dashboard se unifica con tu paleta personalizada

### 🔧 Mejoras Técnicas

#### Robustez del Sistema
- ✅ **Manejo global de errores** - Captura automática de errores JavaScript y promesas rechazadas
- ✅ **Logging inteligente** - Registro de errores con timestamp, stack trace y contexto
- ✅ **Recuperación automática** - El sistema continúa funcionando ante fallos parciales
- ✅ **Retry con backoff exponencial** - 3 reintentos automáticos en operaciones de red (1s, 2s, 4s)

#### Validación y Seguridad
- ✅ **Validación dual mejorada** - Frontend + Backend en todas las operaciones críticas
- ✅ **Corte de caja preciso** - Cálculo correcto de efectivo considerando:
  - Ventas en efectivo (`tipo_pago = 'efectivo'`)
  - Ingresos adicionales
  - Gastos y retiros
  - Fiados pendientes
- ✅ **Control de stock robusto** - Prevención de ventas sin inventario
- ✅ **Sanitización XSS** - Protección contra ataques de inyección

#### Código y Documentación
- ✅ **Comentarios v5.0 completos** - Todo el código JavaScript y CSS documentado
- ✅ **README actualizado** - Documentación exhaustiva con:
  - Nuevas características v5.0
  - Guía de personalización de colores
  - Ejemplos visuales actualizados
  - Solución de problemas mejorada
- ✅ **CHANGELOG.md** - Historial completo de versiones
- ✅ **Algoritmos de color documentados** - Funciones hexToRgb, rgbToHex, darken, lighten

### 🐛 Correcciones de Bugs

- ✅ **Corte de caja mostraba $0.00** - Ahora busca correctamente `tipo_pago = 'efectivo'` en lugar de `'pagado'`
- ✅ **Reportes vacíos sin validación** - Ahora valida datos antes de exportar y muestra mensajes específicos
- ✅ **Stock negativo en inventario** - Herramienta "Verificar Integridad" detecta y reporta inconsistencias
- ✅ **Errores sin capturar** - Sistema global de error handling previene crashes
- ✅ **Tema oscuro con colores hardcodeados** - Navbar ahora usa `var(--color-primario)` con filtro brightness

### 🗑️ Eliminaciones

- ❌ **Calculadora rápida** - Removida (funcionalidad innecesaria para uso real)
- ❌ **Historial de últimas 5 ventas** - Removido del punto de venta
- ❌ **Scanner de código de barras** - Pospuesto hasta adquisición de hardware
- ❌ **Botones +/- de cantidad** - Restaurado input simple
- ❌ **Configuración de nombre de tienda/logo** - Removido (app de uso simple, 3 días/mes)
- ❌ **Limpiar registros antiguos** - Removido (gestión manual preferida)

### 📦 Estructura de Archivos Actualizada

```
www/
├── CHANGELOG.md          ← NUEVO - Historial de versiones
├── README.md             ← ACTUALIZADO - Documentación v5.0
├── dashboard.php         ← MEJORADO - Selector de color libre
├── assets/
│   ├── css/
│   │   └── style.css     ← MEJORADO - Variables CSS dinámicas, comentarios v5.0
│   └── js/
│       └── app.js        ← MEJORADO - modColor con selector libre, error handling global
└── api/
    └── *.php             ← Sin cambios mayores
```

### 🎯 Uso de Nuevas Características

#### Cambiar Color del Sistema
1. Haz clic en el icono de paleta 🎨 en la barra superior
2. Selecciona cualquier color del selector nativo
3. El sistema aplicará automáticamente:
   - Tu color en navbar, sección headers, botones primarios
   - Gradientes adaptativos en botones de acción
   - Gradientes en stat-cards que mantienen identidad
   - Modo oscuro compatible con tu color

#### Verificar Integridad de Datos
1. Ve al módulo de Inventario
2. Haz clic en "Verificar Integridad" (solo admins)
3. Revisa el reporte de:
   - Productos con stock negativo
   - Ventas huérfanas
   - Registros inválidos

#### Revisar Log de Errores
1. Haz clic en el icono de bug 🐛 en el dashboard
2. Consulta errores recientes con:
   - Timestamp
   - Tipo de error
   - Mensaje descriptivo
   - Stack trace para diagnóstico

---

## [4.0.0] - Octubre 2025

### Características Base
- ✅ CRUD completo de inventario
- ✅ Punto de venta funcional
- ✅ Sistema de ventas fiadas
- ✅ Reportes Excel básicos
- ✅ Gestión de usuarios con roles
- ✅ Modo offline con SQLite

### Problemas Conocidos (Resueltos en v5.0)
- ⚠️ Corte de caja mostraba $0.00
- ⚠️ Tema oscuro con colores hardcodeados
- ⚠️ Sin manejo de errores global
- ⚠️ Sin validación de datos en reportes

---

## Leyenda de Símbolos

- ✅ Característica agregada
- 🔧 Mejora técnica
- 🐛 Bug corregido
- ❌ Característica removida
- ⚠️ Problema conocido
- 📦 Cambio en estructura
- 🎯 Nueva funcionalidad

---

<div align="center">

**Desarrollado con ❤️ por Adán García Lima**

**v5.0 Offline Edition — Noviembre 2025**

</div>
