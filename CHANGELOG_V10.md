# Changelog - TG Gestión v10

Todos los cambios notables del proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [10.0.0] - 2026-02-17

### 🎯 Cambio Mayor: Arquitectura Offline Completa

Esta versión representa una refactorización completa del sistema para funcionar 100% offline con SQLite.

### ✨ Añadido

#### Inicialización y Arranque
- `launcher.php` - Sistema de inicialización blindado con logging detallado
- `ping.php` - Endpoint de health check rápido
- `health-check.php` - Verificación exhaustiva del sistema
- `diagnostic-api.php` - API de diagnóstico con recopilación de logs

#### Herramientas de Mantenimiento
- `force-init-db.php` - Inicialización forzada de base de datos (emergencia)
- `verify-and-fix.php` - Verificación y reparación automática
- `verify-superadmin.php` - Verificación detallada del usuario superadmin
- `restore-system.php` - Sistema completo de restauración con opciones guiadas
- `reset-db.php` - Reinicio completo de base de datos con backup

#### Utilidades y Guías
- `quickstart.php` - Guía de inicio rápido para nuevos usuarios
- `routes.php` - Índice completo de todas las rutas disponibles
- `setup.php` - Asistente de configuración inicial
- `offline-check.php` - Verificación de dependencias offline
- `report-wrapper.php` - Wrapper para reportes con botón de retorno

#### Monitoreo y Diagnóstico
- `monitor.html` - Monitor de logs en tiempo real estilo terminal
- `quick-diagnostic.html` - Panel de diagnóstico visual interactivo
- `health.php` - Health check adicional

#### Testing y Validación
- `test-login-direct.php` - Test de login sin JavaScript
- `test-sqlite-only.php` - Verificación exclusiva de SQLite
- `test-dark-mode.html` - Test del panel de salud en modo oscuro
- `test_api.html` - Test visual de APIs
- `test_db.php` - Test completo de base de datos
- `test_migration.php` - Verificación de migraciones
- `test_nosession.php` - Test sin sesión activa
- `test_salud.html` - Test del endpoint de salud del sistema
- `test_simple.php` - Test básico de PHP
- `simulate_salud_sistema.php` - Simulación del endpoint de salud

#### API y Backend
- `api/api_admin_backup.php` - Respaldo de API de administración
- `api/api_reportes_simple.php` - API simplificada de reportes
- `api/connection_retry.php` - Sistema de reintentos de conexión
- `api/ensure_superadmin.php` - Garantía de existencia del superadmin
- `api/error_handler.php` - Manejador centralizado de errores
- `api/healthcheck.php` - Health check de API
- `api/init.php` - Inicialización de API
- `api/migrate_db.php` - Sistema de migraciones
- `api/test_debug.php` - Herramienta de depuración

#### Assets
- `assets/js/download-handler.js` - Manejador de descargas

#### Scripts de Configuración
- `scripts/package.json` - Configuración de scripts Node.js
- `scripts/setup-firestore.js` - Script de setup de Firestore (referencia)
- `scripts/setup-gcp.sh` - Script de configuración GCP (referencia)

### 🔄 Cambiado

#### Configuración Principal
- `config.php` - Completamente refactorizado para SQLite offline
  - Nuevo sistema de detección de ambiente (PHP Desktop vs Web)
  - Configuración de ruta de BD en AppData
  - Modo offline por defecto
  - Eliminadas dependencias de MySQL

#### Base de Datos
- `api/db.php` - Reescrito para SQLite exclusivamente
  - Optimizaciones PRAGMA para rendimiento
  - Sistema de conexión robusto con reintentos
  - Logging mejorado de errores

#### APIs del Sistema
- `api/api_admin.php` - Refactorización completa
  - Endpoint `salud_sistema` mejorado
  - Mejor manejo de sesiones
  - Logging detallado de errores

- `api/api_login.php` - Mejoras sustanciales
  - Validación mejorada de credenciales
  - Logging de intentos de login
  - Mejor manejo de errores de sesión

- `api/api_ventas.php` - Optimizaciones
  - Queries optimizadas para SQLite
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
