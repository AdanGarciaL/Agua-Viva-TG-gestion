# TG Gestión v10.8 - Sistema de Gestión Offline

![Version](https://img.shields.io/badge/Versión-10.8.0-blue?style=flat-square)
![License](https://img.shields.io/badge/Licencia-Privada-red?style=flat-square)
![Status](https://img.shields.io/badge/Estado-Estable-brightgreen?style=flat-square)
![Windows](https://img.shields.io/badge/Windows-7%2B-0078D4?style=flat-square&logo=windows)

---

## 📥 Descargar Instalador

<div align="center">

### **TG Gestión V10.8 - Instalador Profesional**

[![Descargar Instalador](https://img.shields.io/badge/⬇️_DESCARGAR_INSTALADOR-180MB-brightgreen?style=for-the-badge&logo=windows&logoColor=white)](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases)

**o**

[![Release en GitHub](https://img.shields.io/badge/Ver%20en%20GitHub-Releases-informational?style=flat-square&logo=github)](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases)

</div>

---

## 💻 Requisitos del Sistema

| Requisito | Especificación |
|-----------|----------------|
| **Sistema Operativo** | Windows 7 SP1 o superior (64-bit) |
| **Espacio Disponible** | Mínimo 500 MB |
| **RAM Recomendada** | 4 GB o superior |
| **Conexión a Internet** | No requiere (100% Offline) |
| **Arquitectura** | x64 (64-bit) |

### ✅ Incluido en el Instalador
- ✓ PHP 7.4+ integrado
- ✓ SQLite embebido
- ✓ Visual C++ Redistributable 2022
- ✓ Todas las librerías necesarias
- ✓ **No requiere instalaciones adicionales**

---

## 🎯 Descripción

TG Gestión v10.8 es un sistema completo de gestión offline para negocios locales, desarrollado con PHP y SQLite. Esta versión consolida mejoras de experiencia de usuario, reportes ejecutivos más claros para junta, diagnósticos más confiables, una interfaz más profesional y una estructura de documentación más cuidada.

## ✨ Características Principales

- ✅ **100% Offline** - No requiere conexión a internet
- ✅ **Auto-Instalación** - Instalador profesional y automatizado
- ✅ **SQLite Local** - Base de datos embebida de alto rendimiento
- ✅ **Auto-Inicialización** - Sistema blindado de arranque automático
- ✅ **Multiidioma** - Interfaz en Español e Inglés
- ✅ **Ejecución como Admin** - Configurado automáticamente
- ✅ **Diagnóstico Completo** - Herramientas de monitoreo y reparación
- ✅ **Seguro** - Cifrado de contraseñas y sesiones protegidas
- ✅ **Ayuda por Rol** - El centro de ayuda muestra solo lo que cada usuario necesita
- ✅ **Cuentas para Vendedor** - Acceso operativo a cuentas sin exponer módulos de admin
- ✅ **Logs más limpios** - Filtros inteligentes para errores y mensajes de sistema
- ✅ **UI más ligera** - Tarjetas y alertas rediseñadas para mejor rendimiento en laptops

## 🖼️ Capturas de Pantalla

Las siguientes capturas representan módulos reales de la aplicación y ayudan a documentar el comportamiento visual de la versión 10.8:

| Captura | Archivo | Descripción |
|--------|---------|-------------|
| Inicio | [inicio de secion.png](assets/Capturas%20de%20Pantalla/inicio%20de%20secion.png) | Pantalla de acceso principal |
| Ventas | [ventas.png](assets/Capturas%20de%20Pantalla/ventas.png) | Punto de venta en tema claro |
| Ventas oscuro | [ventas dark.png](assets/Capturas%20de%20Pantalla/ventas%20dark.png) | Punto de venta en tema oscuro |
| Inventario | [inventario.png](assets/Capturas%20de%20Pantalla/inventario.png) | Tabla de inventario con compra/venta |
| Inventario oscuro | [inventario dark.png](assets/Capturas%20de%20Pantalla/inventario%20dark.png) | Inventario en tema oscuro |
| Reportes | [reportes.png](assets/Capturas%20de%20Pantalla/reportes.png) | Centro de exportación |
| Reporte inventario | [reporte exel inventario.png](assets/Capturas%20de%20Pantalla/reporte%20exel%20inventario.png) | Exportación Excel de inventario |
| Reporte consolidado | [reporte exel consolidado.png](assets/Capturas%20de%20Pantalla/reporte%20exel%20consolidado.png) | Exportación consolidada ejecutiva |
| Cuentas | [cuentas.png](assets/Capturas%20de%20Pantalla/cuentas.png) | Gestión de cuentas y saldos |
| Cortes | [cortes.png](assets/Capturas%20de%20Pantalla/cortes.png) | Gestión de cortes de caja |
| Séptimas | [septima.png](assets/Capturas%20de%20Pantalla/septima.png) | Registro de séptimas |
| Configuración | [configuracion.png](assets/Capturas%20de%20Pantalla/configuracion.png) | Panel de superadmin |

> Nota: los archivos de captura están almacenados en [assets/Capturas de Pantalla](assets/Capturas%20de%20Pantalla/).

## 📦 Nuevos Archivos del Sistema (v10.8)

### Inicialización y Salud
- `launcher.php` - Punto de entrada con inicialización blindada
- `ping.php` - Health check rápido del sistema
- `health-check.php` - Verificación completa de salud
- `diagnostic-api.php` - API de diagnóstico con logging

### Mantenimiento y Reparación
- `force-init-db.php` - Inicialización forzada de BD (emergencia)
- `verify-and-fix.php` - Verificación y reparación automática
- `verify-superadmin.php` - Verificación del usuario administrador
- `restore-system.php` - Sistema completo de restauración

### Utilidades
- `quickstart.php` - Guía de inicio rápido
- `routes.php` - Índice de todas las rutas disponibles
- `setup.php` - Asistente de configuración inicial
- `monitor.html` - Monitor de logs en tiempo real
- `quick-diagnostic.html` - Panel de diagnóstico visual

---

## 🚀 Instalación Rápida

### Opción 1: Instalador Automático (Recomendado)

1. **Descargar** el instalador desde el botón arriba
2. **Ejecutar** `TG-Gestion-Setup-10.8.0.exe`
3. **Seleccionar** idioma (Español/English)
4. **Aceptar** el Aviso de Privacidad
5. **Completar** la instalación (automática)
6. **Iniciar** desde el Escritorio o Menú Inicio

### Opción 2: Instalación Manual

1. **Clonar el repositorio**
```bash
git clone https://github.com/AdanGarciaL/Agua-Viva-TG-gestion.git
cd Agua-Viva-TG-gestion/www
```

2. **Primera ejecución**
   - Abrir en navegador: `http://localhost:8080/launcher.php`
   - El sistema se inicializará automáticamente

### 🔐 Primera Configuración

Al instalar, el sistema solicitará crear una cuenta de administrador.

⚠️ **Importante**: 
- Contacta con el desarrollador para configurar el administrador
- Define un nombre de usuario y contraseña segura
- Guarda tus credenciales en un lugar seguro

---

## 🔧 Estructura del Proyecto

```
Agua-Viva-TG-gestion/
├── www/                   # Aplicación web
│   ├── api/              # APIs del backend
│   ├── assets/           # CSS, JS, Recursos
│   ├── config.php        # Configuración
│   ├── index.php         # Login
│   ├── dashboard.php     # Panel principal
│   └── launcher.php      # Punto de entrada
├── php/                  # PHP embebido
├── data/                 # Base de datos
└── installer_output/     # Instalador (.exe)
```

---

## 📊 Módulos Principales

1. **Inventario** - Gestión de productos y stock
2. **Ventas** - Registro de ventas y fiados
3. **Registros** - Control de ingresos/egresos
4. **Séptimas** - Gestión de donaciones
5. **Usuarios** - Administración de accesos
6. **Reportes** - Generación de Excel/PDF

---

## 🛠️ Herramientas de Diagnóstico

Si tienes problemas, usa estas herramientas:

### Health Check Rápido
```
http://localhost:8080/ping.php
```

### Diagnóstico Completo
```
http://localhost:8080/quick-diagnostic.html
```

### Reparación de Emergencia
Si el sistema no arranca:
1. Abrir `force-init-db.php` - Recrear BD
2. Abrir `restore-system.php` - Restauración completa
3. Abrir `verify-superadmin.php` - Verificar admin

---

## 🔐 Seguridad

- ✓ Contraseñas con hashing bcrypt
- ✓ Sesiones seguras con regeneración
- ✓ Protección CSRF
- ✓ Validación de inputs
- ✓ Audit trail completo
- ✓ Ejecución como Administrador

---

## 📄 Base de Datos

**SQLite Local** - No requiere servidor externo

Ubicaciones según SO:
- **Windows**: `C:\Users\[Usuario]\AppData\Local\TG_Gestion\database.sqlite`
- **Portable**: Carpeta local de la aplicación

Características:
- Modo WAL (Write-Ahead Logging)
- Optimizaciones automáticas
- Backup automático
- Recuperación ante errores

---

## 💡 Primeros Pasos

1. **Instalar** usando el instalador automático
2. **Iniciar sesión** con credenciales por defecto
3. **Cambiar contraseña** inmediatamente
4. **Explorar** los módulos disponibles
5. **Configurar** parámetros de tu negocio

---

## 🐛 Reportar Problemas

¿Encontraste un bug? Crea un issue en GitHub:
[Reportar Problema](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/issues)

---

### 📝 Licencia

Este proyecto se distribuye bajo una licencia de uso autorizado para el entorno Agua Viva / TG Gestión. Consulta [LICENSE.txt](LICENSE.txt) para los términos completos.

---

## 👨‍💼 Desarrollador

**Adán García Lima**  
📧 [Contacto](https://github.com/AdanGarciaL)

---

<div align="center">

### **¿Listo para instalar?**

[![Descargar Ahora](https://img.shields.io/badge/⬇️_DESCARGAR_V10.8.0-brightgreen?style=for-the-badge&logo=windows&logoColor=white)](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases)

*Windows 7 SP1+ (64-bit) - 180 MB - Incluye todo lo necesario*

</div>
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

### v10.8.0 (2026-05-09)

- Consolidado ejecutivo ampliado por secciones
- Diagnósticos más confiables y sin falsos negativos
- Versionado visual actualizado a 10.8 en la interfaz
- README con capturas reales del sistema
- ✨ Centro de ayuda separado por rol
- ✨ Cuentas habilitadas para vendedor
- ✨ Rediseño ligero de alertas y tarjetas críticas
- ✨ Filtros mejorados en logs y registros
- 🔧 Corrección de warnings PHP comunes
- 🔧 Mejoras de estabilidad en notificaciones y carga de módulos

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

**Versión**: 10.8.0  
**Última actualización**: Abril 2026  
**Estado**: ✅ En producción
