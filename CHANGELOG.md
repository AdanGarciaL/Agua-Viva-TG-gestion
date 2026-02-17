# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Versionamiento Semántico](https://semver.org/spec/v2.0.0.html).

## [10.0.0] - 2026-02-17

### 🎯 Cambios Mayores

Esta versión representa una **reescritura completa** del sistema con migración de arquitectura MySQL/Cloud a **SQLite offline**.

**Resumen de cambios:**
- 69 archivos modificados
- 13,337 inserciones (+)
- 2,081 eliminaciones (-)

### ✨ Añadido

#### Sistema de Inicialización Robusto
- `launcher.php` - **Nuevo punto de entrada principal** (153 líneas)
  - Inicialización automática de base de datos
  - Verificación de estructura de directorios
  - Creación automática de superadmin si no existe
  - Sistema de logging detallado en `data/launcher.log`
  - Manejo robusto de errores con reintentos automáticos
  - Redirección inteligente después de inicialización

#### Herramientas de Diagnóstico
- `ping.php` - Health check rápido (92 líneas)
  - Endpoint simple para verificar que el sistema está vivo
  - Verifica conexión a BD y usuarios
  - Respuesta JSON con estado del sistema
  
- `health-check.php` - Verificación completa de salud (147 líneas)
  - Verifica PHP, extensiones, base de datos
  - Valida todas las tablas requeridas
  - Verifica estructura de directorios y permisos
  - Verifica existencia de archivos críticos
  - Respuesta JSON detallada con todos los checks

- `diagnostic-api.php` - API de diagnóstico con logging (63 líneas)
  - Endpoint para obtener información completa del sistema
  - Recopila logs de todos los archivos de log
  - Información de PHP, SQLite, permisos
  - Útil para troubleshooting remoto

#### Herramientas de Mantenimiento
- `force-init-db.php` - Inicialización forzada de BD (185 líneas)
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
