# TG Gestión v10 - Sistema de Gestión Offline

## 🎯 Descripción

TG Gestión v10 es un sistema completo de gestión offline para negocios locales, desarrollado con PHP y SQLite. Esta versión ha sido completamente rediseñada para funcionar 100% sin conexión a internet, con inicialización automática y herramientas de diagnóstico avanzadas.

## ✨ Novedades de la Versión 10

### Características Principales
- ✅ **100% Offline**: No requiere conexión a internet
- ✅ **SQLite Local**: Base de datos embebida de alto rendimiento
- ✅ **Auto-Inicialización**: Sistema blindado de arranque automático
- ✅ **Diagnóstico Completo**: Herramientas de monitoreo y reparación
- ✅ **PHP Desktop Ready**: Optimizado para distribución como aplicación de escritorio

### Nuevos Archivos del Sistema

#### Inicialización y Salud
- `launcher.php` - Punto de entrada con inicialización blindada
- `ping.php` - Health check rápido del sistema
- `health-check.php` - Verificación completa de salud
- `diagnostic-api.php` - API de diagnóstico con logging

#### Mantenimiento y Reparación
- `force-init-db.php` - Inicialización forzada de BD (emergencia)
- `verify-and-fix.php` - Verificación y reparación automática
- `verify-superadmin.php` - Verificación del usuario administrador
- `restore-system.php` - Sistema completo de restauración

#### Utilidades
- `quickstart.php` - Guía de inicio rápido
- `routes.php` - Índice de todas las rutas disponibles
- `setup.php` - Asistente de configuración inicial
- `monitor.html` - Monitor de logs en tiempo real
- `quick-diagnostic.html` - Panel de diagnóstico visual

#### Testing
- `test-login-direct.php` - Test de login sin JavaScript
- `test-sqlite-only.php` - Verificación de SQLite
- `test_api.html`, `test_salud.html` - Tests de API
- `test_db.php` - Test completo de base de datos

## 📦 Estructura del Proyecto

```
V10/
├── api/                    # APIs del backend
│   ├── api_admin.php
│   ├── api_login.php
│   ├── api_ventas.php
│   └── db.php             # Conexión a base de datos
├── assets/                # Recursos del frontend
│   ├── css/
│   ├── js/
│   └── vendor/           # Librerías incluidas (offline)
├── data/                  # Datos y respaldos
│   ├── backups/
│   └── reportes/
├── scripts/              # Scripts de configuración
├── config.php           # Configuración del sistema
├── launcher.php         # Punto de entrada principal
├── index.php           # Página de login
└── dashboard.php       # Panel principal
```

## 🚀 Instalación

### Requisitos
- PHP 7.2 o superior
- Extensión SQLite habilitada
- PHP Desktop (para distribución como .exe)

### Instalación Rápida

1. **Clonar el repositorio**
```bash
git clone https://github.com/AdanGarciaL/Agua-Viva-TG-gestion.git
cd Agua-Viva-TG-gestion
```

2. **Primera ejecución**
   - Abrir en navegador: `http://localhost:8080/launcher.php`
   - El sistema se inicializará automáticamente
   - O usar `quickstart.php` para guía paso a paso

3. **Credenciales por defecto**
   - Usuario: `AdanGL`
   - Contraseña: `Agl252002`
   - ⚠️ **Cambiar después del primer acceso**

## 🔧 Configuración

El archivo `config.php` contiene toda la configuración del sistema:

```php
// Base de datos SQLite (offline)
define('DB_DRIVER', 'sqlite');
define('DB_PATH', getenv('APPDATA') . '\\TG_Gestion\\database.sqlite');

// Modo offline
$config['offline'] = true;
```

### Ubicación de la Base de Datos
- **Windows**: `C:\Users\[Usuario]\AppData\Local\TG_Gestion\database.sqlite`
- **Portable**: Se puede configurar en carpeta local

## 🛠️ Herramientas de Diagnóstico

### Health Check Rápido
```bash
curl http://localhost:8080/ping.php
```

### Diagnóstico Completo
Abrir en navegador:
- `health-check.php` - Verificación JSON completa
- `quick-diagnostic.html` - Panel visual interactivo
- `monitor.html` - Monitor de logs en tiempo real

### Reparación de Emergencia

Si el sistema no arranca:
1. Abrir `force-init-db.php` - Recrear BD desde cero
2. Abrir `restore-system.php` - Sistema de restauración guiado
3. Abrir `verify-superadmin.php` - Verificar usuario admin

## 📊 Funcionalidades

### Módulos Principales
1. **Inventario** - Gestión de productos
2. **Ventas** - Registro de ventas y fiados
3. **Registros** - Control de ingresos/egresos
4. **Séptimas** - Gestión de donaciones
5. **Usuarios** - Administración de accesos
6. **Reportes** - Generación de Excel/PDF

### Características Técnicas
- SQLite con modo WAL (Write-Ahead Logging)
- PRAGMA optimizations para rendimiento
- Sistema de backup automático
- Logs detallados de errores
- Audit trail completo

## 🔐 Seguridad

- Contraseñas con hashing bcrypt
- Sesiones seguras con regeneración
- Protección CSRF
- Validación de inputs
- Sanitización de datos

## 📱 Distribución

### Como Aplicación de Escritorio (PHP Desktop)

1. Colocar archivos en carpeta `www/`
2. Configurar `settings.json` de PHP Desktop
3. Compilar con PHP Desktop Chrome
4. Resultado: Aplicación .exe autónoma

### Configuración PHP Desktop
```json
{
  "main_window": {
    "title": "TG Gestión",
    "default_size": [1024, 768]
  },
  "web_server": {
    "listen_on": ["127.0.0.1", 8080],
    "www_directory": "www",
    "index_files": ["launcher.php"]
  }
}
```

## 🐛 Solución de Problemas

### El sistema no inicia
1. Abrir `quick-diagnostic.html`
2. Si falla, usar `force-init-db.php`
3. Verificar logs en `data/launcher.log`

### Error de permisos
```powershell
# Windows PowerShell (como administrador)
icacls "C:\\Program Files\\TG Gestion Estables\\V10\\data" /grant Users:F
```

### Base de datos corrupta
1. Abrir `force-init-db.php` (hace backup automático)
2. O restaurar manualmente desde `data/backups/`

## 📝 Changelog

### v10.0.0 (2026-02-17)
- ✨ Nueva arquitectura 100% offline
- ✨ Sistema de inicialización blindado
- ✨ Herramientas de diagnóstico avanzadas
- ✨ Monitor de logs en tiempo real
- ✨ Sistema de reparación automática
- 🔧 Migración completa a SQLite
- 🔧 Optimizaciones de rendimiento
- 📚 Documentación completa

Ver [CHANGELOG.md](CHANGELOG.md) para detalles completos.

## 👥 Contribuir

Este es un proyecto privado. Para reportar problemas, contacta al desarrollador.

## 📄 Licencia

Uso interno - Todos los derechos reservados

## 👨‍💻 Autor

**Adán García Lima**
- GitHub: [@AdanGarciaL](https://github.com/AdanGarciaL)
- Universidad: Benemérita Universidad Autónoma De Puebla (BUAP)

## 🙏 Agradecimientos

- Proyecto Agua Viva
- Comunidad PHP
- SQLite Foundation

---

**Versión**: 10.0.0 Beta  
**Última actualización**: Febrero 2026  
**Estado**: ✅ En producción
