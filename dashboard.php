<?php
// dashboard.php - v5.0 Offline Edition - Tienda Regional
// Cargar configuración centralizada
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

// Configurar cookies de sesión seguras cuando sea posible
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
} else {
    session_set_cookie_params(0, '/','', false, true);
}
session_start();
if (!isset($_SESSION["usuario"])) { header("Location: index.php"); exit(); }
session_regenerate_id(true);

// Sanitización
$usuario_actual = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
$role_actual = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
$vendedor_nombre = isset($_SESSION['vendedor_nombre']) ? htmlspecialchars($_SESSION['vendedor_nombre'], ENT_QUOTES, 'UTF-8') : $usuario_actual;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tienda Regional (TG Gestión v5.0)</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- FontAwesome: Local + CDN fallback -->
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" onerror="console.warn('FontAwesome CDN fallback')">
    <!-- SweetAlert2: Local + CDN fallback -->
    <link href="assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet" onerror="console.warn('SweetAlert2 CDN fallback')"
</head>

<body data-role="<?php echo $role_actual; ?>">

    <header class="top-navbar">
        <div class="nav-brand">
            <img src="assets/img/logo-agua-viva.png" alt="Logo" class="logo-nav" onerror="this.style.display='none'">
            <div style="display:flex; flex-direction:column; line-height:1.1; margin-left: 10px;">
                <span class="brand-name">Tienda Regional</span>
                <span style="font-size:0.7rem; opacity:0.8; font-weight:400;">v5.0 Offline Edition</span>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="#" class="nav-item active" data-tab="ventas">
                <i class="fas fa-cash-register"></i> <span>Ventas</span>
            </a>
            <a href="#" class="nav-item" data-tab="inventario">
                <i class="fas fa-boxes-stacked"></i> <span>Inventario</span>
            </a>
            <a href="#" class="nav-item" data-tab="registros">
                <i class="fas fa-chart-line"></i> <span>Registros</span>
            </a>
            <!-- Séptimas eliminado en esta versión regional (deprecated) -->
            <a href="#" class="nav-item admin-only" data-tab="reportes">
                <i class="fas fa-file-excel"></i> <span>Reportes</span>
            </a>
            <div class="nav-group superadmin-only" style="display:flex; gap:5px; margin-left:10px; padding-left:10px; border-left:1px solid var(--color-borde);">
                <a href="#" class="nav-item" data-tab="errores"><i class="fas fa-bug"></i></a>
                <a href="#" class="nav-item" data-tab="config"><i class="fas fa-cog"></i></a>
            </div>
        </nav>

        <div class="user-menu">
            <!-- Selector de Color para TODOS -->
            <div style="position:relative; display:flex; align-items:center; gap:8px;">
                <label for="color-picker-input" class="btn-circle" title="Personalizar Color" style="cursor:pointer; margin:0;">
                    <i class="fas fa-palette"></i>
                </label>
                <input type="color" id="color-picker-input" value="#0d47a1" style="opacity:0; position:absolute; pointer-events:all; cursor:pointer; width:40px; height:40px; border:none;" />
            </div>
            
            <button id="btn-theme-toggle" class="btn-circle" title="Cambiar Tema">
                <i class="fas fa-moon"></i>
            </button>
            
            <div class="user-details" style="display:none;"> <span class="user-name"><?php echo $vendedor_nombre; ?></span>
            </div>
            
            <a href="api/api_logout.php" class="btn-logout-nav" title="Cerrar Sesión">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </header>

    <main class="main-container">

        <div class="tab-content active" id="ventas">
            <div class="section-header">
                <h2><i class="fas fa-cash-register"></i> Punto de Venta</h2>
                <span id="reloj-digital">--:--:--</span>
            </div>
            
            <div class="venta-container">
                <div class="venta-izquierda">
                    <div class="input-group" style="position:relative;">
                        <label>Buscar Producto:</label>
                        <input type="text" id="buscar-producto" placeholder="Nombre o código..." autocomplete="off" autofocus>
                        <div id="resultados-busqueda"></div>
                    </div>
                    
                    <div id="producto-seleccionado"></div>
                    
                    <div class="input-group">
                        <label>Cantidad:</label>
                        <input type="number" id="cantidad-venta" value="1" min="1">
                    </div>
                    
                    <button class="btn btn-primario" id="agregar-carrito">
                        <i class="fas fa-plus"></i> Agregar al Ticket
                    </button>
                    
                    <h3 style="margin-top: 2rem;">Fiados Pendientes</h3>
                    <div style="overflow-x:auto;">
                        <table id="tabla-deudores">
                            <thead><tr><th>Deudor</th><th>Monto</th><th>Acción</th></tr></thead>
                            <tbody id="cuerpo-tabla-deudores"></tbody>
                        </table>
                    </div>
                </div>
                
                <div class="venta-derecha">
                    <h3><i class="fas fa-receipt"></i> Ticket Actual</h3>
                    <ul id="carrito-lista"><li style="text-align:center; padding:20px; opacity:0.6;">Vacío</li></ul>
                    <hr style="border-color:var(--color-borde);">
                    
                    <div class="input-group">
                        <label>Pago:</label>
                        <select id="tipo-pago">
                            <option value="pagado">Efectivo</option>
                            <option value="fiado">Fiado</option>
                        </select>
                    </div>
                    
                    <div class="input-group" id="nombre-fiado-group" style="display:none;">
                        <label>Nombre Deudor:</label>
                        <input type="text" id="nombre-fiado" placeholder="Nombre completo">
                    </div>
                    
                    <h2 id="carrito-total" style="text-align:right; color:var(--color-primario);">Total: $0.00</h2>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <button class="btn btn-success" id="finalizar-venta">Cobrar</button>
                        <button class="btn btn-danger" id="cancelar-venta">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="inventario">
            <div class="section-header" style="background: linear-gradient(135deg, var(--color-success), #81c784);">
                <h2><i class="fas fa-boxes-stacked"></i> Inventario</h2>
            </div>
            
            <!-- NUEVO: Dashboard de Admin con estadísticas -->
            <div class="admin-only" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:30px;">
                <!-- Top Producto -->
                <div class="stat-card stat-card-purple" style="color:white;">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <i class="fas fa-crown"></i>
                        <div style="flex:1;">
                            <small>Top Producto del Mes</small>
                            <h3 id="top-producto" style="font-size:1.2rem;">Cargando...</h3>
                            <small id="top-producto-ventas" style="opacity:0.8;">0 ventas</small>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Bajo -->
                <div class="stat-card stat-card-pink" style="color:white; cursor:pointer;" onclick="inventario.mostrarStockBajo()">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div style="flex:1;">
                            <small>Stock Crítico</small>
                            <h3 id="stock-bajo-count" style="font-size:2rem;">0</h3>
                            <small style="opacity:0.8;">productos bajos (click para ver)</small>
                        </div>
                    </div>
                </div>
                
                <!-- Ventas del Mes -->
                <div class="stat-card stat-card-blue" style="color:white;">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <i class="fas fa-chart-line"></i>
                        <div style="flex:1;">
                            <small>Ventas del Mes</small>
                            <h3 id="ventas-mes-total" style="font-size:1.5rem;">$0.00</h3>
                            <small id="ventas-mes-comparativa" style="opacity:0.8;">vs mes anterior</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <button class="btn btn-primario admin-only" id="btn-mostrar-form-producto" style="margin-bottom:20px; width:auto;">
                <i class="fas fa-plus"></i> Nuevo Producto
            </button>
            
            <button class="btn btn-secundario admin-only" id="btn-verificar-integridad" style="margin-bottom:20px; margin-left:10px; width:auto;">
                <i class="fas fa-shield-alt"></i> Verificar Integridad
            </button>
            
            <!-- v5.0: Filtros de inventario (Admin) -->
            <div class="admin-only" style="background:var(--color-blanco); padding:15px; border-radius:12px; margin-bottom:20px; box-shadow:var(--sombra);">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; align-items:end;">
                    <div class="input-group" style="margin:0;">
                        <label>Buscar:</label>
                        <input type="text" id="filtro-buscar" placeholder="Nombre o código...">
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Ordenar por:</label>
                        <select id="filtro-ordenar">
                            <option value="nombre">Nombre A-Z</option>
                            <option value="nombre_desc">Nombre Z-A</option>
                            <option value="stock_asc">Stock Menor a Mayor</option>
                            <option value="stock_desc">Stock Mayor a Menor</option>
                            <option value="precio_asc">Precio Menor a Mayor</option>
                            <option value="precio_desc">Precio Mayor a Menor</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Mostrar:</label>
                        <select id="filtro-stock">
                            <option value="todos">Todos los productos</option>
                            <option value="bajo">Solo stock bajo (&lt;10)</option>
                            <option value="medio">Stock medio (10-50)</option>
                            <option value="alto">Stock alto (&gt;50)</option>
                        </select>
                    </div>
                    <button class="btn btn-primario" onclick="inventario.aplicarFiltros()" style="padding:10px 20px;">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                </div>
            </div>
            
            <form id="form-producto" class="admin-only" style="display:none; background:var(--color-blanco); padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:var(--sombra);">
                <h3 style="margin-top:0; color:var(--color-texto);">Gestión de Producto</h3>
                <input type="hidden" id="producto-id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="input-group"><label>Nombre:</label><input type="text" id="producto-nombre" required></div>
                    <div class="input-group"><label>Código:</label><input type="text" id="producto-codigo"></div>
                    <div class="input-group"><label>Precio:</label><input type="number" id="producto-precio" step="0.50" required></div>
                    <div class="input-group"><label>Stock:</label><input type="number" id="producto-stock" required></div>
                    <div class="input-group" style="grid-column:span 2"><label>Foto URL:</label><input type="text" id="producto-foto"></div>
                </div>
                <button type="submit" class="btn btn-success">Guardar</button>
            </form>

            <div style="overflow-x:auto;">
                <table id="tabla-inventario">
                    <thead><tr><th>Img</th><th>Nombre</th><th>Código</th><th>Precio</th><th>Stock</th><th class="admin-only">Opciones</th></tr></thead>
                    <tbody id="cuerpo-tabla-inventario"></tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="registros">
            <div class="section-header" style="background: linear-gradient(135deg, var(--color-warning), #ffb74d);">
                <h2><i class="fas fa-chart-line"></i> Control de Caja (Hoy)</h2>
            </div>

            <div class="corte-container" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom:25px;">
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; text-align:center; box-shadow:var(--sombra); border-left: 5px solid var(--color-success);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">VENTAS EFECTIVO</small>
                    <h3 id="corte-ventas" style="color:var(--color-success); margin:5px 0; font-size:1.5rem;">$0.00</h3>
                </div>
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; text-align:center; box-shadow:var(--sombra); border-left: 5px solid var(--color-primario);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">INGRESOS / PAGOS</small>
                    <h3 id="corte-ingresos" style="color:var(--color-primario); margin:5px 0; font-size:1.5rem;">$0.00</h3>
                </div>
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; text-align:center; box-shadow:var(--sombra); border-left: 5px solid var(--color-danger);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">GASTOS / RETIROS</small>
                    <h3 id="corte-gastos" style="color:var(--color-danger); margin:5px 0; font-size:1.5rem;">$0.00</h3>
                </div>
                <div style="background:var(--color-texto); padding:15px; border-radius:12px; text-align:center; box-shadow:var(--sombra); color:white;">
                    <small style="opacity:0.8; font-weight:bold;">TOTAL EN CAJÓN</small>
                    <h3 id="corte-total" style="margin:5px 0; font-size:1.8rem; color:#4caf50;">$0.00</h3>
                </div>
            </div>

            <form id="form-registro" class="admin-only" style="background:var(--color-blanco); padding:20px; border-radius:12px; margin-bottom:20px;">
                <div style="display:grid; grid-template-columns: 1fr 2fr 1fr; gap:15px;">
                    <div class="input-group"><label>Tipo:</label><select id="registro-tipo" name="tipo"><option value="ingreso">Ingreso Extra</option><option value="gasto">Gasto</option><option value="egreso">Retiro de Caja</option></select></div>
                    <div class="input-group"><label>Concepto:</label><input type="text" id="registro-concepto" name="concepto" required placeholder="Ej: Pago de Luz"></div>
                    <div class="input-group"><label>Monto:</label><input type="number" id="registro-monto" name="monto" step="0.50" required></div>
                </div>
                <button type="submit" class="btn btn-primario">Registrar Movimiento</button>
            </form>

            <table id="tabla-registros"><thead><tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Monto</th><th>Usuario</th><th class="admin-only">x</th></tr></thead><tbody id="cuerpo-tabla-registros"></tbody></table>
            
            <h3 style="margin-top:30px; color:var(--color-texto);">Historial Ventas</h3>
            <table id="tabla-ventas-historial"><thead><tr><th>Fecha</th><th>Vendedor</th><th>Producto</th><th>Cant</th><th>Total</th><th>Tipo</th><th class="admin-only">Dev</th></tr></thead><tbody id="cuerpo-tabla-ventas"></tbody></table>
        </div>

        <!-- Pestaña 'Séptimas' eliminada para esta edición regional -->

        <div class="tab-content admin-only" id="reportes">
            <div class="section-header" style="background: linear-gradient(135deg, #00695c, #4db6ac);">
                <h2><i class="fas fa-file-excel"></i> Reportes</h2>
            </div>
            <div style="background:var(--color-blanco); padding:40px; border-radius:12px; text-align:center;">
                <h3 style="color:var(--color-texto);">Descargar Excel</h3>
                <div style="display:flex; gap:20px; justify-content:center; margin-top:30px;">
                    <button class="btn btn-success" id="btn-reporte-inventario" style="width:auto;">Solo Inventario</button>
                    <button class="btn btn-primario" id="btn-reporte-consolidado" style="width:auto;">Reporte Completo</button>
                </div>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="config">
            <div class="section-header" style="background: linear-gradient(135deg, #37474f, #78909c);">
                <h2><i class="fas fa-cog"></i> Configuración SuperAdmin</h2>
            </div>
            
            <!-- Estadísticas Globales -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;">
                <div class="stat-card stat-card-green" style="color:white; text-align:center;">
                    <i class="fas fa-database"></i>
                    <h3 id="stat-db-size">Cargando...</h3>
                    <small>Tamaño BD</small>
                </div>
                <div class="stat-card stat-card-orange" style="color:white; text-align:center;">
                    <i class="fas fa-dollar-sign"></i>
                    <h3 id="stat-ventas-total">$0.00</h3>
                    <small>Ventas Históricas</small>
                </div>
                <div class="stat-card stat-card-blue" style="color:white; text-align:center;">
                    <i class="fas fa-users"></i>
                    <h3 id="stat-usuarios-total">0</h3>
                    <small>Usuarios Registrados</small>
                </div>
                <div class="stat-card stat-card-teal" style="color:white; text-align:center;">
                    <i class="fas fa-calendar"></i>
                    <h3 id="stat-ultima-venta" style="font-size:1.1rem;">--</h3>
                    <small>Última Venta</small>
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                <div style="background:var(--color-blanco); padding:25px; border-radius:16px; box-shadow:var(--sombra);">
                    <h3 style="margin-top:0; color:var(--color-primario);"><i class="fas fa-user-shield"></i> Crear Administrador</h3>
                    <form id="form-crear-usuario">
                        <div class="input-group"><label>Usuario:</label><input type="text" id="nuevo-usuario-user" name="username" required placeholder="admin2"></div>
                        <div class="input-group"><label>Contraseña:</label><input type="password" id="nuevo-usuario-pass" name="password" required placeholder="Contraseña segura"></div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Crear Admin</button>
                    </form>
                    <table id="tabla-usuarios-sistema" style="margin-top:20px;"><thead><tr><th>Usuario</th><th>Rol</th><th>Acciones</th></tr></thead><tbody id="cuerpo-tabla-usuarios"></tbody></table>
                </div>
                <div style="background:var(--color-blanco); padding:25px; border-radius:16px; box-shadow:var(--sombra);">
                    <h3 style="margin-top:0; color:var(--color-warning);"><i class="fas fa-tools"></i> Mantenimiento</h3>
                    
                    <button class="btn btn-primario" id="btn-optimizar-bd" style="width:100%; margin-bottom:15px;">
                        <i class="fas fa-rocket"></i> Optimizar Base de Datos
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-10px;">Ejecuta VACUUM para reducir tamaño y mejorar rendimiento</p>
                    
                    <button class="btn btn-success" id="btn-respaldo-db" style="width:100%; margin-bottom:15px;">
                        <i class="fas fa-download"></i> Descargar Respaldo
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-10px;">Descarga copia de seguridad de la base de datos</p>
                    
                    <hr style="margin:25px 0; border-color:var(--color-borde);">
                    
                    <h4 style="color:var(--color-danger);"><i class="fas fa-exclamation-triangle"></i> Zona Peligrosa</h4>
                    <button class="btn btn-danger" id="btn-resetear-demo" style="width:100%;">
                        <i class="fas fa-skull-crossbones"></i> Resetear Sistema Completo
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-danger); margin-top:5px;">⚠️ Elimina TODOS los datos (ventas, inventario, registros)</p>
                </div>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="errores">
            <div class="section-header" style="background: var(--color-danger);"><h2>Log</h2></div>
            <table id="tabla-errores"><thead><tr><th>Fecha</th><th>Error</th></tr></thead><tbody id="cuerpo-tabla-errores"></tbody></table>
        </div>

        <footer class="main-footer">
            &copy; <?php echo date("Y"); ?> <strong>Agua Viva</strong>. Todos los derechos reservados. v4.0
        </footer>
    </main>

    <!-- SweetAlert2: Local + CDN fallback -->
    <script src="assets/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script>
    // Cargar desde CDN si no está disponible localmente
    if (typeof Swal === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js';
        script.onload = function() { console.log('SweetAlert2 cargado desde CDN'); };
        script.onerror = function() {
            console.warn('SweetAlert2 CDN falló, usando fallback');
            window.Swal = {
                fire: function(opts) {
                    if (opts && opts.title) alert((opts.title || '') + "\n" + (opts.text || ''));
                    return Promise.resolve({ isConfirmed: true, value: true });
                }
            };
        };
        document.head.appendChild(script);
    }
    </script>
    <script src="assets/js/app.js" type="module"></script>
</body>
</html>