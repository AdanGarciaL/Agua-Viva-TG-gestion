# TG Gestión - Sistema Integral POS

**Versión:** 2.5.3 (Stable Patch)
**Desarrollador Principal:** Adan G. (Superadmin)
**Organización:** Grupo Agua Viva

## 📋 Descripción del Proyecto

**TG Gestión** es una solución tecnológica híbrida (Web/Local) diseñada a medida para la administración financiera, control de inventarios y punto de venta del grupo "Agua Viva".

El sistema centraliza las operaciones de venta, gestiona créditos internos ("fiados") y administra las aportaciones especiales ("séptimas"), proporcionando una interfaz optimizada, segura y con roles jerárquicos estrictos.

## 🚀 Módulos y Funcionalidades

### 1. 🛒 Módulo de Ventas (POS)
* Interfaz de venta rápida con buscador predictivo.
* Gestión de carrito de compras en tiempo real.
* **Sistema de Créditos (Fiados):** Registro y seguimiento de deudas por usuario/padrino.
* Cálculo automático de totales y control de stock en tiempo real.

### 2. 📦 Gestión de Inventario
* Control de stock con alertas visuales.
* Catálogo con imágenes y códigos de barras.
* Previsualización optimizada de productos (Zoom hover).
* *Soft Delete:* Historial de productos preservado para auditoría.

### 3. 🍷 Control de Séptimas
* Módulo exclusivo para el registro de aportaciones especiales.
* Historial de transacciones por fecha y usuario.
* Estados de pago (Pendiente/Pagado).

### 4. 📊 Reportes y Auditoría
* **Exportación a Excel:** Generación de reportes consolidados (Inventario + Ventas + Deudas).
* **Log de Errores:** Sistema de monitoreo interno para fallos de sistema.
* **Dashboard Financiero:** Visualización rápida de ingresos y egresos manuales.

### 5. 🛡️ Seguridad y Roles (RBAC)
Sistema de Control de Acceso Basado en Roles estricto:
* **Vendedor:** Acceso limitado a Ventas.
* **Administrador:** Gestión de inventario y reportes.
* **Superadmin (TG):** Control total del sistema, gestión de usuarios, logs y configuración crítica.

---

## 🛠️ Stack Tecnológico

* **Backend:** PHP 8.0+ (Arquitectura API RESTful).
* **Frontend:** JavaScript (Vanilla ES6+), CSS3 (Variables, Flexbox, Animaciones).
* **Base de Datos:** MySQL / MariaDB (Relacional, transaccional).
* **Seguridad:** `password_hash` (Bcrypt), Sesiones PHP seguras, Protección contra inyección SQL (PDO).
* **Librerías:** SweetAlert2 (UI), PHPOffice (Reportes Excel).

---

## 🔄 Historial de Actualizaciones (Changelog)

### [v2.5.3] - Estabilidad UI y Corrección de Pestañas
* **FIX CRÍTICO:** Solucionado conflicto de CSS donde las pestañas (Reportes, Séptimas) se mostraban encimadas en el módulo de Ventas para usuarios Superadmin.
* **FIX UI:** Arreglo de "Pantalla Opaca" que bloqueaba clics al iniciar sesión o recibir alertas. Se ajustó el `z-index` de las alertas y formularios.
* **Mejora:** Se implementó una regla de estilo estricta para ocultar secciones inactivas independientemente de los permisos del rol.

### [v2.5.1] - Blindaje y Seguridad
* **Seguridad:** Restricción total en creación de usuarios (Superadmin solo puede crear Administradores).
* **UI:** Módulo de gestión de usuarios movido a "Configuración" para limpieza visual.
* **Estabilidad:** Manejo de errores mejorado en JavaScript para evitar bloqueos de pantalla por fallos de red.

---

## 🔒 Nota de Confidencialidad
Este código es propiedad privada del desarrollador y del grupo Agua Viva.
El acceso no autorizado, copia o distribución de este software está prohibido.

**Contacto Soporte:** Adan G.

## 📸 Galería del Sistema

### Vistas del Sistema
| | |
|:-------------------------:|:-------------------------:|
| ![Vista 1](screenshots/Captura%20de%20pantalla%202025-11-18%20192431.png) | ![Vista 2](screenshots/Captura%20de%20pantalla%202025-11-18%20192447.png) |
| ![Vista 3](screenshots/Captura%20de%20pantalla%202025-11-18%20192503.png) | ![Vista 4](screenshots/Captura%20de%20pantalla%202025-11-18%20192516.png) |
| ![Vista 5](screenshots/Captura%20de%20pantalla%202025-11-18%20192529.png) | ![Vista 6](screenshots/Captura%20de%20pantalla%202025-11-18%20192552.png) |