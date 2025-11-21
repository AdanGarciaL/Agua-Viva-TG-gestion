<?php
// dashboard.php - v4.0
session_start();
if (!isset($_SESSION["usuario"])) { header("Location: index.php"); exit(); }

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
    <title>Dashboard - TG Gestión v4.0</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body data-role="<?php echo $role_actual; ?>">

    <header class="top-navbar">
        <div class="nav-brand">
            <img src="assets/img/logo-agua-viva.png" alt="Logo" class="logo-nav" onerror="this.style.display='none'">
            <div style="display:flex; flex-direction:column; line-height:1.1; margin-left: 10px;">
                <span class="brand-name">TG Gestión</span>
                <span style="font-size:0.7rem; opacity:0.8; font-weight:400;">v4.0</span>
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
            <a href="#" class="nav-item admin-only" data-tab="septimas">
                <i class="fas fa-wine-glass"></i> <span>Séptimas</span>
            </a>
            <a href="#" class="nav-item admin-only" data-tab="reportes">
                <i class="fas fa-file-excel"></i> <span>Reportes</span>
            </a>
            <div class="nav-group superadmin-only" style="display:flex; gap:5px; margin-left:10px; padding-left:10px; border-left:1px solid var(--color-borde);">
                <a href="#" class="nav-item" data-tab="errores"><i class="fas fa-bug"></i></a>
                <a href="#" class="nav-item" data-tab="config"><i class="fas fa-cog"></i></a>
            </div>
        </nav>

        <div class="user-menu">
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
            
            <button class="btn btn-primario admin-only" id="btn-mostrar-form-producto" style="margin-bottom:20px; width:auto;">
                <i class="fas fa-plus"></i> Nuevo Producto
            </button>
            
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

        <div class="tab-content admin-only" id="septimas">
            <div class="section-header" style="background: linear-gradient(135deg, var(--color-septima), #ab47bc);">
                <h2><i class="fas fa-wine-glass"></i> Séptimas</h2>
            </div>
            <form id="form-septima" style="background:var(--color-blanco); padding:20px; border-radius:12px; margin-bottom:20px;">
                <div style="display:flex; gap:15px; align-items:flex-end;">
                    <div class="input-group" style="flex:2"><label>Padrino/Madrina:</label><input type="text" id="septima-nombre" name="nombre" required></div>
                    <div class="input-group" style="flex:1"><label>Monto:</label><input type="number" id="septima-monto" name="monto" step="0.50" required></div>
                    <button type="submit" class="btn btn-primario" style="margin-bottom:15px; width:auto;">Registrar</button>
                </div>
            </form>
            <table><thead><tr><th>Fecha</th><th>Nombre</th><th>Monto</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="cuerpo-tabla-septimas"></tbody></table>
        </div>

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
                <h2><i class="fas fa-cog"></i> Configuración</h2>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                <div style="background:var(--color-blanco); padding:20px; border-radius:12px;">
                    <h3 style="margin-top:0;">Crear Admin</h3>
                    <form id="form-crear-usuario">
                        <div class="input-group"><label>Usuario:</label><input type="text" id="nuevo-usuario-user" name="username" required></div>
                        <div class="input-group"><label>Contraseña:</label><input type="password" id="nuevo-usuario-pass" name="password" required></div>
                        <button type="submit" class="btn btn-success">Crear</button>
                    </form>
                    <table id="tabla-usuarios-sistema" style="margin-top:20px;"><thead><tr><th>User</th><th>Rol</th><th>x</th></tr></thead><tbody id="cuerpo-tabla-usuarios"></tbody></table>
                </div>
                <div style="background:var(--color-blanco); padding:20px; border-radius:12px;">
                    <h3 style="margin-top:0;">Sistema</h3>
                    <div class="input-group"><label>Color Tema:</label><input type="color" id="color-picker" style="height:40px;"></div>
                    <hr style="border-color:var(--color-borde);">
                    <button id="btn-respaldo-db" class="btn btn-primario" style="background:#34495e;">Descargar BD (.sqlite)</button>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/app.js" type="module"></script>
</body>
</html>