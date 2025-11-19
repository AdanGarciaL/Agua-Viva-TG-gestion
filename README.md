# TG Gestión - Sistema Integral POS

**Versión:** 2.6.0 (Portable Edition)
**Desarrollador Principal:** Adan G. (Superadmin)
**Organización:** Grupo Agua Viva

## 📋 Descripción del Proyecto

**TG Gestión** es una solución tecnológica híbrida diseñada a medida para la administración financiera, control de inventarios y punto de venta del grupo "Agua Viva".

El sistema centraliza las operaciones de venta, gestiona créditos internos ("fiados") y administra las aportaciones especiales ("séptimas"), proporcionando una interfaz optimizada, segura y con roles jerárquicos estrictos.

## 🚀 Características Versión Portátil (v2.6.0)
Esta versión ha sido migrada a arquitectura **Serverless (SQLite)**, lo que permite:
* **Portabilidad Total:** Ejecución directa desde USB o carpeta local sin necesidad de instalar XAMPP o MySQL.
* **Respaldo Simplificado:** Toda la información reside en un único archivo (`api/database.sqlite`).
* **Despliegue Rápido:** Solo requiere copiar la carpeta y ejecutar.

## 🛠️ Módulos y Funcionalidades

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
* **Exportación a Excel:** Generación de reportes consolidados.
* **Log de Errores:** Sistema de monitoreo interno.
* **Dashboard Financiero:** Visualización de ingresos/egresos.

### 5. 🛡️ Seguridad y Roles (RBAC)
* **Vendedor:** Acceso limitado a Ventas.
* **Administrador:** Gestión de inventario y reportes.
* **Superadmin (TG):** Control total, gestión de usuarios (creación restringida de Admins).

---

## 🔄 Historial de Actualizaciones (Changelog)

### [v2.6.0] - Migración a Portátil (SQLite)
* **Arquitectura:** Cambio de motor de base de datos de MySQL a SQLite para eliminar dependencia de servidores locales.
* **Instalador:** Inclusión de script `setup_db.php` para autogeneración de base de datos.
* **Optimización:** Ajuste de conexiones API para lectura de archivo local.

### [v2.5.3] - Estabilidad UI
* **FIX CRÍTICO:** Solución a pestañas encimadas y pantalla opaca en Login.
* **UI:** Mejoras visuales en tablas y formularios.

---

## 🔒 Nota de Confidencialidad
Este código es propiedad privada del desarrollador y del grupo Agua Viva.

**Contacto Soporte:** Adan G.

## 📸 Galería del Sistema

### Vistas del Sistema
| | |
|:-------------------------:|:-------------------------:|
| ![Vista 1](screenshots/Captura%20de%20pantalla%202025-11-18%20192431.png) | ![Vista 2](screenshots/Captura%20de%20pantalla%202025-11-18%20192447.png) |
| ![Vista 3](screenshots/Captura%20de%20pantalla%202025-11-18%20192503.png) | ![Vista 4](screenshots/Captura%20de%20pantalla%202025-11-18%20192516.png) |
| ![Vista 5](screenshots/Captura%20de%20pantalla%202025-11-18%20192529.png) | ![Vista 6](screenshots/Captura%20de%20pantalla%202025-11-18%20192552.png) |