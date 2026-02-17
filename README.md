# 🏪 TG Gestión v10 Beta — Offline Edition

![Estado](https://img.shields.io/badge/Estado-Beta-informational?style=for-the-badge)
![Versión](https://img.shields.io/badge/Versión-v10_Beta_Offline_Edition-blue?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Offline](https://img.shields.io/badge/100%25_Offline-Sin_Internet-orange?style=for-the-badge)

> **Sistema Integral de Punto de Venta para Hacienda Regional**  
> *100% Offline — Sin necesidad de conexión a Internet*

---

## ⚠️ AVISO IMPORTANTE - USO RESTRINGIDO

**Esta aplicación es de uso EXCLUSIVO para Hacienda Agua Viva.**

- 🔒 **NO distribuir** el instalador a terceros
- 🔒 **NO usar** en otros negocios sin autorización
- 🔒 **NO compartir** con otras haciendas
- ✅ **USO AUTORIZADO**: Solo personal de Hacienda Agua Viva

Para más detalles sobre restricciones de uso, consultar `README_DISTRIBUCION.txt`

---

## 📥 Descarga Rápida

[![Descargar para Windows](https://img.shields.io/badge/WINDOWS-DESCARGAR_v10_Beta-0078D6?style=for-the-badge&logo=windows&logoColor=white)](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases/latest/download/Instalador_TG_Gestion_v10_Beta_Offline.exe)

*Instalador todo-en-uno: PHP, base de datos y navegador incluidos*

---

## ✨ Novedades v10 Beta

### 🚀 **Estabilidad y Usabilidad Mejoradas**

Esta versión **v10 Beta** trae correcciones críticas y mejoras de estabilidad sobre v5.0.

| Mejora | Descripción |
|--------|-------------|
| 🐛 **Sistema de Errores Avanzado** | Logging automático con captura global, sin pérdida de datos |
| 🧹 **Limpieza de Logs** | Nuevo botón para limpiar registros y evitar ralentizaciones |
| 🌓 **Modo Oscuro Perfeccionado** | Corrección de contraste y legibilidad en todos los módulos |
| 📊 **Caja e Inventario Optimizados** | Mejor contraste de colores, tablas más legibles |
| 🖥️ **UI Sin Scroll Horizontal** | Eliminados desbordes visuales en todas las secciones |
| 📝 **Código Limpio** | Eliminados comentarios innecesarios, codebase más profesional |
| 🔄 **Versión Unificada** | Todas las pantallas muestran consistentemente "v10 Beta" |
| 🎨 **Temas Personalizables** | Selector de color libre heredado de v5.0 |
| ✅ **Corte de Caja Preciso** | Cálculo automático del dinero en caja con desglose completo |
| 📊 **Reportes Mejorados** | Exportación Excel con formato profesional |
| 🔐 **Seguridad Reforzada** | CSRF, validación dual, sanitización total |

### 🔁 Diferencias v5 vs v10 Beta

#### Lo que cambió de v5.0 a v10 Beta:

- ✅ **Modo oscuro corregido:** textos siempre legibles, contrastes ajustados
- ✅ **Caja e inventario más claros:** colores optimizados para lectura rápida
- ✅ **Registro de errores robusto:** captura automática de errores JavaScript y PHP
- ✅ **Botón limpiar log:** evita acumulación de logs que ralentizan el sistema
- ✅ **UI más estable:** sin scroll horizontal, elementos bien alineados
- ✅ **Código profesional:** sin comentarios de desarrollo, solo código producción
- ✅ **Versionado consistente:** "v10 Beta" en todas las pantallas y archivos

#### Lo que se mantiene de v5.0:

- ✅ Selector de color libre con millones de opciones
- ✅ Gradientes dinámicos en botones y tarjetas
- ✅ Animaciones fluidas (fadeIn, slideIn, pulse)
- ✅ Sistema de roles (Vendedor, Admin, SuperAdmin)
- ✅ Corte de caja automático con cálculos precisos
- ✅ Exportación profesional a Excel
- ✅ 100% offline sin necesidad de Internet
- ✅ Protección CSRF y seguridad integral

### 💪 Estabilidad y Robustez (Mejorado en v10 Beta)

- **Error Handling Global Mejorado:** Captura automática de errores JavaScript y PHP
- **Logging Inteligente:** Registro de errores con contexto completo y timestamp
- **Gestión de Logs:** Visualización y limpieza de logs desde el panel de admin
- **Validación DOM:** Verificación de elementos antes de manipularlos
- **Try-Catch Estratégico:** Manejo de excepciones en operaciones críticas
- **Recuperación Automática:** El sistema continúa funcionando ante fallos parciales
- **UI Optimizada:** Sin desbordamientos, mejor contraste en modo claro y oscuro

---

## 🏪 Características Principales

### 📦 **Gestión de Inventario**
- ✅ CRUD completo de productos (Crear, Leer, Actualizar, Eliminar)
- ✅ Control de stock en tiempo real
- ✅ Búsqueda rápida por nombre o código de barras
- ✅ Soporte para imágenes de productos
- ✅ Validación de precios y cantidades
- ✅ Paginación eficiente para grandes inventarios
- ✅ Actualización automática de stock post-venta
- ✅ **NUEVO v10:** Mejor contraste visual en modo oscuro

### 💰 **Punto de Venta (POS)**
- ✅ Carrito de compras interactivo
- ✅ Ventas en efectivo con cálculo automático de cambio
- ✅ Ventas fiadas (a crédito) con seguimiento de deudores
- ✅ Búsqueda inteligente con sugerencias en tiempo real
- ✅ Validación de stock antes de procesar venta
- ✅ Interfaz rápida y eficiente
- ✅ **NUEVO v10:** Logging de errores en operaciones críticas

### 📈 **Registros y Corte de Caja**
- ✅ Historial completo de todas las ventas
- ✅ Registro de ingresos y gastos adicionales
- ✅ Control de retiros de caja
- ✅ **Corte de caja automático** con cálculos precisos:
  - Ventas en efectivo del día
  - Ingresos adicionales
  - Gastos y retiros
  - Fiados pendientes de cobro
  - **Total real en caja**
- ✅ Filtros por fecha y tipo de movimiento
- ✅ Códigos de color para identificación rápida
- ✅ **NUEVO v10:** Colores optimizados para mejor legibilidad

### 📊 **Reportes Excel**
- ✅ Exportación profesional a formato XLSX
- ✅ Reportes de ventas por período
- ✅ Reportes de inventario actualizado
- ✅ Reportes de movimientos financieros
- ✅ Formato automático con totales calculados
- ✅ Encabezados y estilos profesionales

### 👥 **Gestión de Usuarios**
- ✅ Sistema de roles (Vendedor, Admin, SuperAdmin)
- ✅ Control de acceso basado en permisos
- ✅ Contraseñas seguras con bcrypt
- ✅ Sesiones protegidas con regeneración de ID
- ✅ Panel de configuración para administradores

> ⚠️ **IMPORTANTE SOBRE ADMINISTRADORES:**  
> Solo el **SuperAdmin** (desarrollador) puede crear cuentas de **Administrador**.  
> Si necesitas agregar un admin a tu instalación, contacta al desarrollador.  
> Los admins pueden crear/editar/eliminar vendedores.

### 🛡️ **Seguridad Integral**
- ✅ Protección CSRF en todas las operaciones críticas
- ✅ Validación dual (Frontend + Backend)
- ✅ Sanitización contra XSS
- ✅ Prepared statements contra SQL Injection
- ✅ Control de stock para prevenir ventas sin inventario
- ✅ Protección de base de datos vía .htaccess
- ✅ **NUEVO v10:** Sistema de logging de errores para auditoría

### 🐛 **Panel de Administración (v10 Beta)**
- ✅ **Visor de Errores:** Visualiza los últimos 200 errores registrados
- ✅ **Limpieza de Logs:** Botón para limpiar el archivo de errores
- ✅ **Salud del Sistema:** Métricas de base de datos, memoria y logs
- ✅ **Verificación de Integridad:** Detecta inconsistencias en datos
- ✅ **Gestión de Temas:** Personalización de colores persistente

---

## 📸 Galería del Sistema

### 🔐 Inicio de Sesión

<div align="center">

| Modo Claro | Modo Oscuro |
|:---:|:---:|
| ![Login](assets/Capturas%20de%20Pantalla/Inicio%20de%20secion.png) | ![Cambio Dark](assets/Capturas%20de%20Pantalla/cambio%20de%20color%20dark.png) |

</div>

### 💰 Módulo de Ventas (POS)

<div align="center">

| Modo Claro | Modo Oscuro |
|:---:|:---:|
| ![Ventas](assets/Capturas%20de%20Pantalla/Seccion%20ventas.png) | ![Ventas Dark](assets/Capturas%20de%20Pantalla/seccion%20ventas%20dark.png) |

*Interfaz rápida para procesar ventas en efectivo o fiado*

</div>

### 📦 Gestión de Inventario

<div align="center">

| Modo Claro | Modo Oscuro |
|:---:|:---:|
| ![Inventario](assets/Capturas%20de%20Pantalla/inventario.png) | ![Inventario Dark](assets/Capturas%20de%20Pantalla/inventario%20dark.png) |

*Control completo de productos con CRUD, búsqueda y paginación*  
*v10 Beta: Mejor contraste y legibilidad*

</div>

### 📈 Registros y Corte de Caja

<div align="center">

| Modo Claro | Modo Oscuro |
|:---:|:---:|
| ![Registros](assets/Capturas%20de%20Pantalla/registro.png) | ![Registros Dark](assets/Capturas%20de%20Pantalla/registro%20dark.png) |

*Historial de movimientos con corte de caja automático*  
*v10 Beta: Colores optimizados para lectura rápida*

</div>

### 📊 Centro de Reportes

<div align="center">

| Generación de Reportes | Reportes (Dark) |
|:---:|:---:|
| ![Reportes](assets/Capturas%20de%20Pantalla/reportes.png) | ![Reportes Dark](assets/Capturas%20de%20Pantalla/reportes%20dark.png) |

![Creación Exitosa](assets/Capturas%20de%20Pantalla/creacion%20de%20reportes.png)

*Exportación profesional a Excel con un clic*

</div>

### ⚙️ Panel de Configuración

<div align="center">

![Configuración](assets/Capturas%20de%20Pantalla/configuracion.png)

*Gestión de usuarios, verificación de integridad y configuraciones del sistema*

</div>

---

## 🚀 Instalación Rápida

### Para Usuarios Finales

1. **Descarga** el instalador desde el botón de arriba
2. **Ejecuta** el archivo `.exe` como administrador
3. **Sigue** el asistente de instalación (siguiente → siguiente → instalar)
4. **Inicia** la aplicación desde el acceso directo del escritorio
5. **Login** con credenciales por defecto:
   ```
   Usuario: admin
   Contraseña: admin123
   ```
6. **Cambia la contraseña** inmediatamente por seguridad

¡Listo! El sistema funciona 100% offline, sin necesidad de Internet.

---

## 💻 Requisitos del Sistema

| Componente | Requerimiento |
|-----------|---------------|
| **Sistema Operativo** | Windows 7/8/10/11 (64-bit) |
| **RAM** | Mínimo 2 GB (Recomendado 4 GB) |
| **Espacio en Disco** | 200 MB libres |
| **Procesador** | Intel/AMD de 1 GHz o superior |
| **Conexión a Internet** | ❌ **NO requerida** |
| **Dependencias** | ✅ Todas incluidas en el instalador |

---

## 🎯 Guía de Uso Rápido

### 1️⃣ Agregar Productos al Inventario

1. Haz clic en la pestaña **Inventario**
2. Presiona el botón **+ Nuevo Producto**
3. Completa el formulario:
   - Nombre del producto
   - Código de barras (opcional)
   - Precio de venta
   - Stock inicial
   - Imagen (opcional)
4. Haz clic en **Guardar**

### 2️⃣ Realizar una Venta

1. Ve a la pestaña **Ventas**
2. Busca el producto escribiendo su nombre o código
3. Selecciona el producto de la lista
4. Ajusta la cantidad si es necesario
5. Haz clic en **Agregar al Carrito**
6. Repite para más productos
7. Selecciona tipo de pago:
   - **Efectivo:** Ingresa el monto recibido para calcular cambio
   - **Fiado:** Ingresa el nombre del deudor
8. Haz clic en **Finalizar Venta**

### 3️⃣ Hacer Corte de Caja

1. Ve a la pestaña **Registros**
2. En la parte superior verás el resumen del día:
   ```
   💵 Ventas Efectivo: $X,XXX.XX
   💰 Ingresos Extra:  $XXX.XX
   💸 Gastos/Retiros:  -$XXX.XX
   ━━━━━━━━━━━━━━━━━━━━━━━━━
   🏦 Total en Caja:   $X,XXX.XX
   ```
3. Este es el dinero que **debe estar físicamente** en el cajón
4. Compara con el efectivo real y registra cualquier diferencia

### 4️⃣ Personalizar el Tema

1. En la barra superior, haz clic en el **icono de paleta** 🎨
2. Se abrirá el selector de color nativo de tu sistema
3. Elige cualquier color que desees (o ingresa un código HEX)
4. El sistema aplicará automáticamente:
   - Color primario (el que elegiste)
   - Color secundario (versión más oscura)
   - Color de acento (versión más clara)
   - Gradientes en todos los botones y tarjetas
5. Tu elección se guarda automáticamente
6. **Funciona con modo claro y oscuro**

### 5️⃣ Exportar Reportes

1. Ve a **Reportes** (solo para administradores)
2. Selecciona el tipo de reporte:
   - Ventas
   - Inventario
   - Movimientos
3. Elige el rango de fechas
4. Haz clic en **Descargar Excel**
5. El archivo `.xlsx` se descargará automáticamente

### 6️⃣ Ver y Limpiar Logs de Errores (v10 Beta) 🆕

1. Ve a **Configuración** (solo administradores)
2. Haz clic en la pestaña **Errores** (ícono de bug 🐛)
3. Verás la lista de errores recientes con:
   - Timestamp
   - Nivel (error, warning, info)
   - Mensaje de error
   - Archivo y línea donde ocurrió
4. Para limpiar el log:
   - Haz clic en **Limpiar Log de Errores**
   - Confirma la acción
   - El sistema creará un archivo de respaldo antes de limpiar

> **Tip:** Limpia los logs periódicamente para mantener el sistema rápido

---

## 📁 Estructura del Proyecto

```
TG_Gestion/
├── api/                      # Backend PHP
│   ├── api_inventario.php    # CRUD de productos
│   ├── api_ventas.php        # Procesamiento de ventas
│   ├── api_registros.php     # Movimientos y corte de caja
│   ├── api_reportes.php      # Generación de Excel
│   ├── api_usuarios.php      # Gestión de usuarios
│   ├── api_admin.php         # Panel admin + logging (v10 Beta)
│   ├── db.php                # Conexión a BD
│   └── setup_db.php          # Inicialización
├── assets/
│   ├── css/
│   │   └── style.css         # Estilos v10 Beta con overflow-x fix
│   ├── js/
│   │   ├── app.js            # Lógica principal + error handling
│   │   └── login.js          # Autenticación
│   └── img/                  # Recursos gráficos
├── data/                     # Directorio de datos
│   ├── app.log               # Log de errores (v10 Beta)
│   └── color_tema.txt        # Persistencia de color
├── packaging/                # Configuración phpdesktop
│   ├── installer.iss         # Script Inno Setup
│   ├── php-desktop.ini       # Config phpdesktop
│   └── launcher.php          # Launcher
├── vendor/                   # Dependencias Composer
├── config.php                # Config global + error handlers (v10)
├── dashboard.php             # Panel principal
├── index.php                 # Login
└── README.md                 # Esta documentación
```

---

## 🔧 Para Desarrolladores

### Instalación desde Código Fuente

```bash
# 1. Clonar repositorio
git clone https://github.com/AdanGarciaL/Agua-Viva-TG-gestion.git
cd Agua-Viva-TG-gestion/www

# 2. Instalar dependencias
composer install

# 3. Inicializar base de datos
php api/setup_db.php

# 4. Iniciar servidor de desarrollo
php -S localhost:8000

# 5. Abrir en navegador
# http://localhost:8000
```

### Tecnologías Utilizadas

- **Backend:** PHP 8.0+ con SQLite3
- **Frontend:** JavaScript ES6+ vanilla (sin frameworks)
- **Base de Datos:** SQLite 3
- **CSS:** Variables CSS personalizadas para temas dinámicos
- **Librerías:**
  - PHPSpreadsheet (Generación de Excel)
  - SweetAlert2 (Notificaciones)
  - Font Awesome (Iconos)
- **Empaquetado:** phpdesktop-chrome + Inno Setup
- **Algoritmos de Color:** Conversión RGB/HEX, darken, lighten para generación automática de paletas
- **Error Logging:** Sistema personalizado con niveles (debug, info, warning, error, fatal) - v10 Beta

### Rutas de Base de Datos

La aplicación busca/crea la base de datos en:

1. **Primera opción:** `%APPDATA%\Local\TG_Gestion\database.sqlite` (Windows)
2. **Fallback:** `./api/database.sqlite` (desarrollo)

Los backups automáticos se guardan en `%APPDATA%\Local\TG_Gestion\backups\`

Los logs de errores se guardan en `./data/app.log` (v10 Beta)

---

## 🐛 Solución de Problemas

### ❌ La aplicación no inicia

**Problema:** Al hacer doble clic no sucede nada

**Solución:**
1. Verifica que phpdesktop esté instalado correctamente
2. Ejecuta como administrador (clic derecho → Ejecutar como administrador)
3. Revisa que tu antivirus no esté bloqueando la aplicación
4. **NUEVO v10:** Busca errores en el log: `data/app.log`
5. Busca errores de phpdesktop en `%APPDATA%\phpdesktop\debug.log`
6. Reinstala la aplicación en una ruta sin espacios ni caracteres especiales

---

### ❌ "Stock insuficiente" pero hay productos

**Problema:** El sistema no permite vender aunque aparentemente hay stock

**Solución:**
1. Ve a la pestaña **Inventario**
2. Verifica el stock real del producto en la tabla
3. Si es 0 o negativo, edita el producto y actualiza el stock manualmente
4. Haz clic en el botón **"Verificar Integridad"** (solo admins)
   - Esto analizará la base de datos y detectará inconsistencias
   - Te mostrará un reporte de problemas encontrados
5. Si el stock es correcto en la tabla pero no en ventas, recarga la página (F5)
6. **NUEVO v10:** Revisa el log de errores por si hay problemas de validación

---

### ❌ El corte de caja muestra $0.00

**Problema:** Todos los valores del corte aparecen en cero

**Solución:**
1. ✅ **Este error está corregido en v10 Beta**
2. Si persiste, verifica que estés usando la versión más reciente
3. Ve al panel de **Registros** y verifica que haya movimientos del día
4. **NUEVO v10:** Revisa el panel de errores (ícono de bug) para más detalles
5. Revisa el log en `data/app.log` para errores de cálculo
6. Como última opción, cierra sesión y vuelve a iniciar

**Causa técnica resuelta:** En versiones anteriores, el sistema buscaba `tipo_pago = 'pagado'` pero ahora guardamos `'efectivo'`. Esto ya está corregido en v10 Beta.

---

### ❌ No se pueden exportar reportes

**Problema:** El botón de Excel no funciona o muestra error

**Solución:**
1. El sistema valida antes de exportar
   - Si ves "No hay datos", significa que no hay información en el período seleccionado
   - Cambia el rango de fechas a un período con ventas/movimientos
2. Verifica que tengas espacio en disco (mínimo 10 MB libres)
3. Asegúrate de que tu navegador permita descargas automáticas
4. Si usas el navegador integrado, verifica permisos de escritura en la carpeta de descargas
5. Revisa que haya datos reales:
   - Para reporte de inventario: debe haber productos activos
   - Para reporte de ventas: debe haber ventas en el período
   - Para reporte consolidado: debe haber al menos productos
6. **NUEVO v10:** Revisa el log de errores para detalles técnicos

**Tip:** El sistema ahora te dirá exactamente qué falta en lugar de solo fallar.

---

### ❌ "Error de conexión" o "fetch failed"

**Problema:** Mensajes de error al intentar operaciones

**Solución:**
1. El sistema reintenta automáticamente (3 veces)
   - Verás en consola: "Fetch falló, reintentando..."
   - Espera unos segundos, el sistema se recuperará solo
2. Si después de 3 reintentos sigue fallando:
   - **NUEVO v10:** Revisa el panel de errores (ícono de bug) para detalles específicos
   - Revisa `data/app.log` para errores de conexión
   - Cierra y vuelve a abrir la aplicación
   - Verifica que no haya otros programas usando el puerto 8000
3. Como última opción, reinicia el sistema

**Mejora v10:** El sistema ahora usa **backoff exponencial** (espera 1s, 2s, 4s entre reintentos) para ser más resiliente ante fallos temporales.

---

### ❌ Los datos no se guardan

**Problema:** Productos, ventas o movimientos no se guardan en la base de datos

**Solución:**
1. Verifica que la carpeta `%APPDATA%\Local\TG_Gestion\` tenga permisos de escritura
2. Ve a **Configuración** → **Verificar Integridad** (admins)
3. **NUEVO v10:** Revisa el log de errores en el panel de administración
4. Revisa el archivo `data/app.log` para errores de escritura
5. Verifica que el archivo `database.sqlite` exista en `%APPDATA%\Local\TG_Gestion\`
6. Si el problema persiste, haz un respaldo manual:
   - Copia `%APPDATA%\Local\TG_Gestion\database.sqlite` a un lugar seguro
   - Reinstala la aplicación
   - Restaura el archivo copiado

---

### ❌ "Solo el SuperAdmin puede crear administradores"

**Problema:** Necesitas agregar un administrador pero no tienes permisos

**Solución:**
1. Esto es **por diseño de seguridad**
2. Solo el **SuperAdmin** (desarrollador) puede crear cuentas de **Admin**
3. Si necesitas un admin nuevo:
   - Contacta al desarrollador/creador del sistema
   - Proporciona el nombre de usuario y contraseña temporal deseados
   - El SuperAdmin lo creará remotamente o te enviará un script SQL
4. Los **admins existentes** SÍ pueden crear usuarios vendedores normales

---

### ❌ El sistema está lento (v10 Beta) 🆕

**Problema:** La aplicación responde lento o se congela

**Solución:**
1. **Causa probable:** Archivo de log muy grande
2. Ve a **Configuración** → **Errores** (solo admins)
3. Haz clic en **Limpiar Log de Errores**
4. El sistema:
   - Creará un respaldo automático con timestamp
   - Limpiará el archivo principal
   - Liberará espacio y memoria
5. Recarga la aplicación (F5)
6. Si persiste:
   - Verifica que no tengas miles de productos o ventas sin paginar
   - Exporta reportes antiguos y archívalos
   - Reinicia la aplicación

> **Tip v10 Beta:** Limpia los logs al menos una vez al mes para mantener el sistema ágil.

---

### 🔧 Herramientas de Diagnóstico (v10 Beta)

**Nuevas funcionalidades para resolver problemas:**

1. **Verificar Integridad** (Botón en Inventario - Solo Admins)
   - Detecta stock negativo
   - Encuentra ventas huérfanas (productos eliminados)
   - Identifica registros con montos inválidos
   - Te muestra un reporte detallado

2. **Log de Errores** (Panel de Administración - Pestaña Errores)
   - Todos los errores JavaScript y PHP se registran automáticamente
   - Incluye timestamp, nivel, mensaje y ubicación
   - Últimos 200 errores visibles en pantalla
   - Útil para diagnóstico técnico

3. **Limpieza de Logs** (Botón en Panel de Errores)
   - Limpia el archivo `data/app.log`
   - Crea respaldo automático con fecha
   - Libera espacio y mejora rendimiento

4. **Retry Automático**
   - El sistema reintenta operaciones fallidas automáticamente
   - 3 intentos con backoff exponencial
   - Transparente para el usuario

5. **Salud del Sistema** (API admin)
   - Tamaño de base de datos
   - Uso de memoria
   - Tamaño del archivo de log
   - Métricas de rendimiento

---

### 📞 ¿Aún necesitas ayuda?

Si ninguna solución funcionó:

1. **Revisa el README completo** (esta documentación)
2. **Consulta el panel de errores** (ícono de bug en el dashboard)
3. **Revisa el archivo de log** en `data/app.log` (v10 Beta)
4. **Abre un Issue en GitHub** con:
   - Captura de pantalla del error
   - Descripción detallada de qué intentaste hacer
   - Pasos para reproducir el problema
   - Versión del sistema (v10 Beta Offline Edition)
   - Fragmentos relevantes del log de errores
5. **Contacta al desarrollador** si es urgente

---

## 🔐 Seguridad y Mejores Prácticas

### ✅ Recomendaciones Obligatorias

1. **Cambia la contraseña del admin** en el primer inicio
2. **Crea usuarios específicos** para cada vendedor (no uses admin para todo)
3. **Haz backups regulares** de `%APPDATA%\Local\TG_Gestion\database.sqlite`
4. **No expongas la aplicación a Internet** (es para uso local/LAN únicamente)
5. **Revisa el log de errores periódicamente** desde el panel de admin (v10 Beta)
6. **Limpia los logs cada mes** para mantener el sistema rápido (v10 Beta)

### 🛡️ Características de Seguridad

- ✅ Contraseñas hasheadas con bcrypt (cost 12)
- ✅ Protección CSRF en todas las operaciones de escritura
- ✅ Sesiones seguras con httponly y samesite
- ✅ Sanitización de entradas contra XSS
- ✅ Prepared statements contra SQL Injection
- ✅ Validación dual (Frontend + Backend)
- ✅ Control de acceso basado en roles
- ✅ Regeneración de session ID en login
- ✅ **NUEVO v10:** Logging de errores para auditoría y diagnóstico
- ✅ **NUEVO v10:** Captura global de excepciones PHP

---

## 📞 Soporte

### ¿Necesitas Ayuda?

1. **Consulta este README** primero
2. **Revisa el panel de errores** en la aplicación (ícono de bug)
3. **Revisa el archivo de log** en `data/app.log` (v10 Beta)
4. **Abre un Issue** en [GitHub](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/issues)
5. **Incluye capturas de pantalla** y descripción detallada del problema
6. **Adjunta fragmentos del log** si es un error técnico

### ¿Quieres Contribuir?

1. Haz **fork** del repositorio
2. Crea una rama: `git checkout -b feature/mi-mejora`
3. Haz tus cambios y commitea: `git commit -m 'Agrega X funcionalidad'`
4. Push: `git push origin feature/mi-mejora`
5. Abre un **Pull Request**

---

## 📜 Licencia

Este proyecto está bajo la licencia **MIT**. Ver archivo `LICENSE` para más detalles.

---

## 🙏 Créditos

**Desarrollado por:** Adán García Lima  
**GitHub:** [@AdanGarciaL](https://github.com/AdanGarciaL)

### Librerías de Terceros

- [phpdesktop](https://github.com/cztomczak/phpdesktop) - Empaquetado de aplicaciones PHP
- [PHPSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) - Generación de Excel
- [SweetAlert2](https://sweetalert2.github.io/) - Notificaciones elegantes
- [Font Awesome](https://fontawesome.com/) - Iconos profesionales

---

## 📊 Estadísticas

![GitHub stars](https://img.shields.io/github/stars/AdanGarciaL/Agua-Viva-TG-gestion?style=social)
![GitHub forks](https://img.shields.io/github/forks/AdanGarciaL/Agua-Viva-TG-gestion?style=social)
![GitHub issues](https://img.shields.io/github/issues/AdanGarciaL/Agua-Viva-TG-gestion)
![GitHub last commit](https://img.shields.io/github/last-commit/AdanGarciaL/Agua-Viva-TG-gestion)

---

## 📚 Ver También

- [CHANGELOG.md](CHANGELOG.md) - Historial completo de versiones y cambios detallados
- [Releases](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/releases) - Descargas de versiones estables
- [Issues](https://github.com/AdanGarciaL/Agua-Viva-TG-gestion/issues) - Reportar problemas o sugerir mejoras

---

<div align="center">

### ⭐ Si este proyecto te fue útil, dale una estrella en GitHub ⭐

**Made with ❤️ for AGL**

**v10 Beta Offline Edition — Febrero 2026**

</div>