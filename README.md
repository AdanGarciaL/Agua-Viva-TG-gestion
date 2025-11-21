# 🐘 TG Gestión v4.0 Platinum

![Banner](https://img.shields.io/badge/Estado-Estable-success?style=for-the-badge)
![Version](https://img.shields.io/badge/Versión-v4.0_Platinum-blue?style=for-the-badge)
![Tech](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)

> **Sistema Integral de Punto de Venta y Gestión Financiera Agua Viva.**
> *Estabilidad, Seguridad y Diseño Profesional.*

---

## 📥 DESCARGAR INSTALADOR

¿Eres usuario final? Descarga la aplicación lista para usar aquí:

[![Descargar para Windows](https://img.shields.io/badge/WINDOWS-DESCARGAR_INSTALADOR_v4.0-0078D6?style=for-the-badge&logo=windows&logoColor=white)](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases/latest/download/Instalador_TG_Gestion_v4_Platinum.exe)

*(Si el botón no descarga directo, ve a la sección de [Releases](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases) y descarga el .exe)*

---

## ✨ Novedades v4.0 Platinum

### 🎨 Experiencia de Usuario (UX/UI)
* **Modo Oscuro Nativo:** Detección automática y cambio manual de tema.
* **Interfaz Limpia:** Secciones organizadas, sin elementos encimados.
* **Animaciones:** Transiciones suaves en botones y ventanas.

### 🛡️ Seguridad y Blindaje
* **Protección de Stock:** El sistema impide ventas si el inventario es insuficiente (Validación Backend + Frontend).
* **Base de Datos Protegida:** Bloqueo de acceso directo a `.sqlite` vía `.htaccess`.
* **Login Seguro:** Validación estricta para Administradores y Vendedores.

### 💰 Funciones Financieras
* **Corte de Caja Real:** Panel en tiempo real que calcula: `(Ventas Efvo + Ingresos) - Gastos = Total en Cajón`.
* **Control de Fiados:** Gestión visual de deudores y abonos.
* **Séptimas:** Módulo dedicado para registro de aportaciones.

---

## 📸 Galería del Sistema

### 🔐 Acceso y Seguridad
| Login Seguro | Configuración |
|:---:|:---:|
| ![Login](assets/Capturas%20de%20Pantalla/Inicio%20de%20secion.png) | ![Config](assets/Capturas%20de%20Pantalla/configuracion.png) |

### 🛒 Módulos Principales (Modo Oscuro/Claro)
| Ventas (Dark) | Ventas (Light) |
|:---:|:---:|
| ![Ventas Dark](assets/Capturas%20de%20Pantalla/seccion%20ventas%20dark.png) | ![Ventas](assets/Capturas%20de%20Pantalla/Seccion%20ventas.png) |

| Inventario (Dark) | Inventario (Light) |
|:---:|:---:|
| ![Inv Dark](assets/Capturas%20de%20Pantalla/inventario%20dark.png) | ![Inv](assets/Capturas%20de%20Pantalla/inventario.png) |

### 💰 Control Financiero
| Corte de Caja (Dark) | Control de Séptimas |
|:---:|:---:|
| ![Caja](assets/Capturas%20de%20Pantalla/registro%20dark.png) | ![Séptimas](assets/Capturas%20de%20Pantalla/septimas%20dark.png) |

### 📊 Reportes Excel
| Centro de Reportes | Generación Exitosa |
|:---:|:---:|
| ![Reportes](assets/Capturas%20de%20Pantalla/reportes%20dark.png) | ![Exito](assets/Capturas%20de%20Pantalla/creacion%20de%20reportes.png) |

---

## 🛠️ Instalación (Para Desarrolladores)

Si deseas modificar el código fuente:

1. Clona el repositorio.
2. Asegúrate de tener un servidor PHP local (o usa el entorno portable incluido).
3. La base de datos `database.sqlite` se autogenera en `api/` si no existe.

---
© 2025 **Grupo Agua Viva** - Desarrollado por Adan G.