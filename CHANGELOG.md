# 📋 Historial de Cambios - TG Gestión

## [10.0.0] - Febrero 2026 - "Professional Release"

### 🎨 Nuevas Características

#### Instalación Profesional
- ✅ **Instalador automático** - Ejecutable Windows (.exe) de 180 MB
- ✅ **Inno Setup 6.6.0** - Compilador profesional con compresión LZMA2
- ✅ **Auto-instalación de VC++** - Visual C++ 2022 se descarga automáticamente
- ✅ **Ejecución como Admin** - Configurado automáticamente al instalar
- ✅ **Multiidioma integrado** - Español e Inglés en el instalador
- ✅ **Aviso de Privacidad** - Bilíngue en la instalación
- ✅ **Accesos directos automáticos** - Escritorio y Menú Inicio
- ✅ **Desinstalador completo** - Limpieza total del sistema

#### Sistema de Diagnóstico Avanzado
- ✅ **launcher.php** - Punto de entrada con inicialización blindada
- ✅ **health-check.php** - Verificación JSON completa del sistema
- ✅ **quick-diagnostic.html** - Panel visual de diagnóstico interactivo
- ✅ **diagnostic-api.php** - API de diagnóstico con logging detallado
- ✅ **monitor.html** - Monitor de logs en tiempo real
- ✅ **ping.php** - Health check rápido del sistema

#### Herramientas de Reparación
- ✅ **force-init-db.php** - Recreación forzada de BD (emergencia)
- ✅ **restore-system.php** - Sistema completo de restauración guiado
- ✅ **verify-and-fix.php** - Verificación y reparación automática
- ✅ **verify-superadmin.php** - Verificación del usuario administrador
- ✅ **quickstart.php** - Guía de inicio rápido paso a paso

#### Utilidades del Sistema
- ✅ **routes.php** - Índice de todas las rutas disponibles
- ✅ **setup.php** - Asistente de configuración inicial
- ✅ **Configuración de admin** - Contactar desarrollador para crear cuenta

### 🔧 Mejoras Técnicas

#### Actualización de Dependencias
- ✅ **phpoffice/phpspreadsheet** - Actualizado de 5.2.0 a 5.4.0
- ✅ **maennchen/zipstream-php** - Actualizado de 3.1.2 a 3.2.1
- ✅ **composer.lock actualizado** - Todas las dependencias sincronizadas
- ✅ **Extensión fileinfo habilitada** - Soporte completo para archivos

#### Infraestructura
- ✅ **PHP embebido** - Incluido en el instalador (sin dependencias externas)
- ✅ **SQLite integrado** - Base de datos local sin servidor
- ✅ **Compresión LZMA2** - Máxima eficiencia de almacenamiento
- ✅ **Modo offline total** - 100% funcionalidad sin internet

#### Seguridad
- ✅ **Ejecución administrativa** - Permisos elevados automáticos
- ✅ **Visual C++ 2022** - Runtime actualizado y seguro
- ✅ **Aviso de privacidad obligatorio** - Aceptación requerida en instalación
- ✅ **Gestión de admin segura** - Solo por contacto directo

### 🐛 Correcciones de Bugs

- ✅ **Instalación manual complicada** - Ahora con instalador automático
- ✅ **Problemas de dependencias** - Composer actualizado y validado
- ✅ **Sin PHP embebido** - Ahora incluido en el .exe
- ✅ **Diagnóstico limitado** - Sistema completo de health checks
- ✅ **Recuperación difícil de errores** - Herramientas de reparación automática
- ✅ **Sin herramientas de monitoreo** - Monitor en tiempo real incluido

### 🗑️ Eliminaciones

- ❌ **Instalación manual de PHP** - Ahora automática
- ❌ **Descarga manual de dependencias** - Incluidas en el instalador
- ❌ **Necesidad de configurar VC++** - Auto-descarga e instala
- ❌ **Accesos directos manuales** - Se crean automáticamente
- ❌ **Credenciales por defecto públicas** - Solo por contacto con desarrollador

### 📦 Estructura de Archivos Actualizada

```
Agua-Viva-TG-gestion/
├── www/                           # Aplicación web v10
│   ├── CHANGELOG.md               ← NUEVO - Historial v10
│   ├── README.md                  ← ACTUALIZADO - Botones descarga
│   ├── launcher.php               ← NUEVO - Punto de entrada
│   ├── ping.php                   ← NUEVO - Health check rápido
│   ├── health-check.php           ← NUEVO - Verificación completa
│   ├── diagnostic-api.php         ← NUEVO - API diagnóstico
│   ├── force-init-db.php          ← NUEVO - Restaurar BD
│   ├── restore-system.php         ← NUEVO - Restauración guiada
│   ├── verify-and-fix.php         ← NUEVO - Verificar/reparar
│   ├── verify-superadmin.php      ← NUEVO - Verificar admin
│   ├── quickstart.php             ← NUEVO - Inicio rápido
│   ├── routes.php                 ← NUEVO - Índice de rutas
│   ├── setup.php                  ← NUEVO - Configuración inicial
│   ├── monitor.html               ← NUEVO - Monitor logs
│   ├── quick-diagnostic.html      ← NUEVO - Panel diagnóstico
│   ├── composer.json              ← ACTUALIZADO - Dependencias v10
│   ├── composer.lock              ← ACTUALIZADO - Sincronizado
│   ├── config.php                 ← Sin cambios mayores
│   ├── index.php                  ← Login página
│   ├── dashboard.php              ← Panel principal
│   ├── api/                       # APIs backend
│   ├── assets/                    # CSS, JS, Recursos
│   └── vendor/                    # Librerías actualizadas
├── php/                           # PHP 7.4+ embebido
│   └── php.ini                    ← ACTUALIZADO - fileinfo habilitado
├── installer_output/
│   └── TG-Gestion-Setup-10.0.0.exe  ← NUEVO - Instalador profesional
├── installer.iss                  ← Inno Setup configuration
├── build_installer.bat            ← Script compilación
├── PRIVACY_NOTICE.txt             ← Aviso privacidad bilingüe
└── LEEME.txt                      ← Guía instalación
```

### 🎯 Proceso de Instalación

#### Instalador Automático (Recomendado)
1. **Descargar** TG-Gestion-Setup-10.0.0.exe (180 MB)
2. **Ejecutar** instalador
3. **Seleccionar idioma** (Español/English)
4. **Aceptar privacidad** bilíngüe
5. **Completar instalación** (automática)
6. **Iniciar** desde Escritorio

#### Instalación Manual
1. Clonar repositorio
2. Instalar PHP 7.4+ manualmente
3. Instalar Visual C++ 2022
4. Ejecutar `composer update`
5. Configurar base de datos SQLite

### 🚀 Características de la v10

#### Distribución
- 🎁 **Instalador profesional** - Un solo archivo .exe
- 📦 **180 MB todo incluido** - PHP, SQLite, VC++, todo dentro
- 🌍 **Multiidioma** - Español e Inglés integrados
- 🔐 **Seguro** - Admin ejecutable, VC++ 2022 automático
- 💾 **Offline completo** - Funciona sin internet

#### Mantenibilidad
- 🔧 **Health checks** - Verificación completa del sistema
- 🛠️ **Herramientas de reparación** - Recuperación automática de errores
- 📊 **Monitoreo en tiempo real** - Logs y diagnóstico visual
- 📋 **Documentación completa** - README profesional con botones

#### Seguridad
- 🔒 **Ejecución como Admin** - Configurado automáticamente
- 🛡️ **Contraseña segura** - Solo por contacto con desarrollador
- 📄 **Aviso legal** - Privacidad y términos aceptados
- ✅ **Validación SSL** - Visual C++ 2022 certificado

### 📊 Comparación v5 vs v10

| Característica | v5.0 | v10.0 |
|---|---|---|
| **Formato** | Código fuente | Instalador .exe |
| **Instalación** | Manual | Automática |
| **PHP** | Externo | Embebido |
| **Visual C++** | Manual | Automático |
| **Tamaño** | - | 180 MB |
| **Idioma** | Español | Español + Inglés |
| **Admin ejecutable** | - | ✅ |
| **Health checks** | Básico | Completo |
| **Monitoreo** | - | Tiempo real |

### 🛠️ Herramientas Incluidas

#### Diagnóstico
- `health-check.php` - Verificación JSON
- `quick-diagnostic.html` - Panel visual
- `monitor.html` - Logs en tiempo real
- `ping.php` - Health check rápido

#### Reparación
- `force-init-db.php` - Recrear BD
- `restore-system.php` - Restauración completa
- `verify-and-fix.php` - Verificación automática
- `verify-superadmin.php` - Validar admin

#### Utilidades
- `launcher.php` - Inicialización segura
- `quickstart.php` - Guía paso a paso
- `routes.php` - Índice de rutas
- `setup.php` - Configuración inicial

### 📥 Descarga

**URL de Descarga:**
```
https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases/download/v10/TG-Gestion-Setup-10.0.0.exe
```

**Requisitos:**
- Windows 7 SP1+ (64-bit)
- 500 MB espacio disponible
- 4 GB RAM recomendada

### ⚡ Cambios Importantes para Usuarios

#### Si vienes de v5.0

1. **Instalación** - Ahora es automática, no manual
2. **PHP** - Ya no necesitas instalarlo, viene incluido
3. **VC++** - Se descarga e instala automáticamente
4. **Admin** - Contacta al desarrollador para configurar
5. **Idiomas** - Ahora con opción English en instalador
6. **Diagnóstico** - Usa las nuevas herramientas de health check

#### Configuración Inicial

1. Instalar usando el .exe
2. Seleccionar idioma (ES/EN)
3. Aceptar privacidad
4. Esperar a que complete
5. Contactar desarrollador para crear admin
6. Ingresar a http://localhost:8080

### 🎉 Novedades Destacadas

- ⭐ **Instalador profesional** - Windows estándar
- ⭐ **Multiidioma** - Español e Inglés completo
- ⭐ **Auto-admin** - Ejecutable con permisos elevados
- ⭐ **Diagnostico avanzado** - 6 herramientas nuevas
- ⭐ **180 MB todo incluido** - Sin dependencias externas
- ⭐ **Composer actualizado** - Todas las librerías al día

---

## [5.0.0] - Noviembre 2025 - "Offline Edition"

### 🎨 Nuevas Características

#### Personalización Visual
- ✅ **Selector de color libre** - Elige cualquier color del espectro completo
- ✅ **Gradientes dinámicos adaptativos** - Los botones mantienen identidad visual
- ✅ **Sistema de color inteligente** - Generación automática de paletas
- ✅ **Modo oscuro mejorado** - Se adapta a cualquier color personalizado
- ✅ **Persistencia de preferencias** - Tu color y tema se guardan

#### Tarjetas de Estadísticas
- ✅ **Gradientes inteligentes en stat-cards** - Adaptación visual automática
- ✅ **Adaptación automática** - Tema claro y oscuro
- ✅ **Consistencia visual** - Dashboard unificado

### 🔧 Mejoras Técnicas

#### Robustez del Sistema
- ✅ **Manejo global de errores** - Captura automática de fallos
- ✅ **Logging inteligente** - Registro con timestamp y stack trace
- ✅ **Recuperación automática** - Sistema continúa ante fallos parciales
- ✅ **Retry con backoff exponencial** - 3 reintentos automáticos

#### Validación y Seguridad
- ✅ **Validación dual mejorada** - Frontend + Backend
- ✅ **Corte de caja preciso** - Cálculos correctos
- ✅ **Control de stock robusto** - Prevención de ventas sin inventario
- ✅ **Sanitización XSS** - Protección contra ataques

### 🐛 Correcciones de Bugs

- ✅ **Corte de caja mostraba $0.00** - Búsqueda correcta de efectivo
- ✅ **Reportes vacíos** - Validación de datos
- ✅ **Stock negativo** - Herramienta de integridad
- ✅ **Errores sin capturar** - Error handling global
- ✅ **Tema oscuro con colores hardcodeados** - Variables CSS dinámicas

---

## [4.0.0] - Octubre 2025

### Características Base
- ✅ CRUD completo de inventario
- ✅ Punto de venta funcional
- ✅ Sistema de ventas fiadas
- ✅ Reportes Excel básicos
- ✅ Gestión de usuarios con roles
- ✅ Modo offline con SQLite

---

## Leyenda de Símbolos

- ✅ Característica agregada
- 🔧 Mejora técnica
- 🐛 Bug corregido
- ❌ Característica removida
- ⚠️ Problema conocido
- 📦 Cambio en estructura
- 🎯 Nueva funcionalidad
- ⭐ Novedad destacada

---

<div align="center">

**Desarrollado con ❤️ por Adán García López**

**v10.0 Professional Release — Febrero 2026**

*Instalador profesional, multiidioma, 100% offline*

</div>
  - Reinicia la base de datos desde cero
  - Crea backup automático antes de eliminar
  - Recrea todas las tablas con estructura correcta
  - Crea usuario superadmin automáticamente
  - Útil para recuperación de emergencias

- `verify-and-fix.php` - Verificación y reparación automática (232 líneas)
  - Verifica integridad de la BD
  - Repara tablas faltantes
  - Verifica y recrea directorios
  - Verifica y restaura archivos críticos
  - Modo automático y modo interactivo

- `verify-superadmin.php` - Verificación del superadmin (98 líneas)
  - Verifica existencia del usuario AdanGL
  - Recrea el superadmin si no existe
  - Valida permisos de administrador
  - Útil después de migraciones o problemas de BD

- `restore-system.php` - Sistema de restauración completo (267 líneas)
  - Interfaz visual para restaurar backups
  - Lista todos los backups disponibles
  - Preview de información antes de restaurar
  - Validación de integridad de backups
  - Restauración con confirmación

#### Utilidades
- `quickstart.php` - Guía de inicio rápido (178 líneas)
  - Wizard paso a paso para nuevos usuarios
  - Verifica configuración del sistema
  - Guía de acceso inicial
  - Enlaces a todas las herramientas importantes

- `routes.php` - Índice de rutas disponibles (142 líneas)
  - Lista completa de todos los endpoints
  - Descripción de cada ruta
  - Métodos HTTP soportados
  - Útil para desarrollo y documentación

- `setup.php` - Asistente de configuración (196 líneas)
  - Configuración inicial del sistema
  - Validación de requisitos
  - Creación de estructura de carpetas
  - Inicialización de base de datos

- `monitor.html` - Monitor de logs en tiempo real (231 líneas)
  - Interfaz visual para ver logs
  - Actualización automática cada 5 segundos
  - Filtrado por tipo de log
  - Búsqueda en logs
  - Descarga de logs

- `quick-diagnostic.html` - Panel de diagnóstico visual (198 líneas)
  - Dashboard visual del estado del sistema
  - Indicadores de salud
  - Información del sistema en tiempo real
  - Enlaces a herramientas de reparación

#### Testing y Verificación
- `test-login-direct.php` - Test de login sin JS (87 líneas)
  - Verifica funcionamiento del login
  - Útil para debugging de sesiones
  - No requiere JavaScript

- `test-sqlite-only.php` - Test enfocado en SQLite (124 líneas)
  - Verifica extensión SQLite
  - Test de lectura/escritura
  - Test de transacciones
  - Verifica PRAGMAs

- `test_api.html` - Test interactivo de APIs (156 líneas)
  - Interfaz para probar todos los endpoints
  - Muestra respuestas JSON
  - Útil para desarrollo

- `test_salud.html` - Test de salud visual (143 líneas)
  - Visualización del health check
  - Actualización en tiempo real
  - Indicadores visuales

- `test_db.php` - Test completo de base de datos (167 líneas)
  - Verifica todas las tablas
  - Test de integridad referencial
  - Verifica estructura de columnas
  - Test de operaciones CRUD

- `test_migration.php` - Verificación de migración (89 líneas)
  - Compara estructura de BD
  - Verifica migración de datos
  - Identifica problemas de migración

- `test_nosession.php` - Test sin sesión (45 líneas)
  - Verifica comportamiento sin login
  - Test de redirecciones

- `test_simple.php` - Test básico (67 líneas)
  - Verifica PHP y configuración básica
  - Test mínimo de funcionalidad

- `test-dark-mode.html` - Test de modo oscuro (134 líneas)
  - Verifica estilos del modo oscuro
  - Toggle de tema
  - Preview de componentes

#### API - Nuevos Archivos
- `api/ensure_superadmin.php` - Garantiza existencia de superadmin (72 líneas)
  - Verifica y crea superadmin si falta
  - Llamado automáticamente en inicialización
  - Manejo robusto de errores

- `api/error_handler.php` - Manejo centralizado de errores (95 líneas)
  - Logger de errores unificado
  - Formato de respuesta JSON estandarizado
  - Niveles de logging (debug, info, error, critical)

- `api/healthcheck.php` - Health check para APIs (58 líneas)
  - Endpoint de salud específico para APIs
  - Verifica conexión a BD desde contexto API
  - Respuesta rápida

- `api/migrate_db.php` - Script de migración (234 líneas)
  - Migración automática de MySQL a SQLite
  - Preservación de datos
  - Validación de integridad
  - Rollback en caso de error

- `api/connection_retry.php` - Reintentos de conexión (87 líneas)
  - Lógica de reconexión automática
  - Backoff exponencial
  - Útil para problemas transitorios

#### Documentación
- `README_V10.md` - Documentación completa (231 líneas)
  - Guía de instalación
  - Descripción de características
  - Arquitectura del sistema
  - Troubleshooting
  - Guías de uso

- Este archivo `CHANGELOG_V10.md` (237 líneas)
  - Documentación detallada de todos los cambios
  - Categorización por tipo de cambio
  - Referencias a archivos modificados

### 🔄 Cambiado

#### Arquitectura Principal
- **Migración completa de MySQL a SQLite**
  - Nueva función `getDbConnection()` con soporte SQLite
  - Adaptación de todas las queries para SQLite
  - Optimizaciones específicas de SQLite (PRAGMAs)

- `config.php` - Reescritura completa (87 líneas)
  - Nueva configuración para SQLite
  - Paths dinámicos para portabilidad
  - Configuración de modo offline
  - Constantes para logging

- `api/db.php` - Refactorización total (156 líneas)
  - Nueva clase `Database` para gestión de conexiones
  - Soporte para SQLite exclusivamente
  - PRAGMA optimizations:
    - `journal_mode = WAL`
    - `synchronous = NORMAL`
    - `temp_store = MEMORY`
    - `foreign_keys = ON`
  - Manejo robusto de errores
  - Conexión singleton

- `api/init.php` - Mejoras significativas (189 líneas)
  - Inicialización mejorada de sesiones
  - Carga de configuración robusta
  - Autoloader mejorado
  - Error handling centralizado

- `api/setup_db.php` - Actualización completa (267 líneas)
  - Schema actualizado para SQLite
  - Nuevas tablas y columnas
  - Migración de datos incluida
  - Verificación de integridad

#### APIs Backend
- `api/api_login.php` - Refactorización
  - Mejor validación de credenciales
  - Logging de intentos de login
  - Mejor manejo de sesiones

- `api/api_ventas.php` - Mejoras importantes
  - Mejor manejo de transacciones

- `api/api_inventario.php` - Mejoras
  - Soporte para stock_minimo
  - Queries optimizadas

- `api/api_registros.php` - Actualizaciones
  - Compatibilidad completa con SQLite

- `api/api_septimas.php` - Mejoras
  - Optimizaciones de queries

- `api/api_usuarios.php` - Mejoras de seguridad
  - Validación mejorada
  - Hashing seguro de contraseñas

- `api/api_reportes.php` - Optimizaciones
  - Generación más rápida
  - Mejor manejo de memoria

#### Frontend
- `index.php` - Mejoras visuales y funcionales
  - Nueva UI más moderna
  - Mejor manejo de errores de login
  - Mensajes más claros

- `dashboard.php` - Refactorización importante
  - Nuevo panel de "Salud del Sistema" para admins
  - Modo oscuro mejorado
  - Mejor organización del código
  - Optimizaciones de carga

- `assets/css/style.css` - Extensas mejoras
  - Variables CSS para temas
  - Modo oscuro completo
  - Mejoras de responsividad
  - Estilos para nuevos componentes

- `assets/js/app.js` - Refactorización completa
  - Nueva función `cargarSaludSistema()`
  - Mejor manejo de errores
  - Código más modular

- `assets/js/login.js` - Mejoras
  - Mejor feedback visual
  - Manejo mejorado de errores

### 🗑️ Eliminado

#### Archivos Obsoletos
- `CHANGELOG.md` (antiguo) - Reemplazado por este archivo
- `README.md` (antiguo) - Reemplazado por README_V10.md
- `LICENSE.txt` (antiguo) - Actualizado en nueva versión
- `packaging/launcher.php` - Movido a raíz como archivo principal
- `packaging/php-desktop.ini` - Configuración obsoleta

#### Dependencias
- Todas las dependencias de MySQL/MariaDB
- Referencias a servicios en la nube
- CDNs externos (ahora todo es local)

### 🔧 Corregido

- Error de inicialización de sesión en múltiples archivos
- Problema con verificación de contraseña en login
- Error de conexión a BD al inicio
- Problemas de permisos en carpeta data/
- Error en generación de reportes con SQLite
- Problema con stock_minimo en productos
- Error de JSON en API de salud del sistema
- Problema de modo oscuro en panel de salud
- Error en lectura de configuración

### 🔒 Seguridad

- Implementación de password hashing mejorado
- Validación estricta de inputs en todas las APIs
- Sanitización de datos en queries
- Protección contra SQL injection mejorada
- Mejores prácticas de manejo de sesiones
- Logging de intentos de acceso fallidos

### ⚡ Rendimiento

- Optimizaciones PRAGMA para SQLite
  - `journal_mode = WAL`
  - `synchronous = NORMAL`
  - `temp_store = MEMORY`
  - `foreign_keys = ON`
- Queries optimizadas para SQLite
- Reducción de tamaño de respuestas JSON
- Carga diferida de componentes pesados
- Minificación de assets

### 📚 Documentación

- README_V10.md - Documentación completa del sistema
- Comentarios mejorados en todos los archivos PHP
- Documentación inline de funciones
- Guías de troubleshooting
- Ejemplos de uso de APIs

### 🏗️ Infraestructura

- Estructura de carpetas reorganizada
- Sistema de logging mejorado
- Backup automático de BD
- Sistema de migraciones de BD
- Herramientas de diagnóstico completas

## [9.x.x] - Versiones Anteriores

Las versiones anteriores usaban MySQL y no están documentadas aquí.
Para información de versiones antiguas, consultar el historial de Git.

---

## Tipos de Cambios

- `✨ Añadido` - Para nuevas funcionalidades
- `🔄 Cambiado` - Para cambios en funcionalidades existentes
- `🗑️ Eliminado` - Para funcionalidades eliminadas
- `🔧 Corregido` - Para corrección de errores
- `🔒 Seguridad` - En caso de vulnerabilidades
- `⚡ Rendimiento` - Para mejoras de rendimiento
- `📚 Documentación` - Para cambios en documentación
- `🏗️ Infraestructura` - Para cambios en la infraestructura

## Enlaces

- [Repositorio](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion)
- [Reportar un problema](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/issues)

---

**Mantenido por**: Adán García Lima  
**Última actualización**: Febrero 2026
