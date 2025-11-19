<?php
// dashboard.php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}
$usuario_actual = htmlspecialchars($_SESSION['usuario']);
$role_actual = htmlspecialchars($_SESSION['role']);
$vendedor_nombre = isset($_SESSION['vendedor_nombre']) ? htmlspecialchars($_SESSION['vendedor_nombre']) : $usuario_actual;
$vendedor_padrino = isset($_SESSION['vendedor_padrino']) ? htmlspecialchars($_SESSION['vendedor_padrino']) : 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TG Gestión</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
</head>
<body data-role="<?php echo $role_actual; ?>">

    <header class="top-navbar">
        <div class="nav-brand">
            <img src="assets/img/logo-agua-viva.png" alt="Logo" class="logo-nav">
            <div style="display:flex; flex-direction:column; line-height:1.1; margin-left: 10px;">
                <span class="brand-name" style="font-size:1.2rem;">TG Gestión</span>
                <span style="font-size:0.75rem; opacity:0.8; font-weight:400;">Agua Viva</span>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="#" class="nav-item active" data-tab="ventas"><i class="fas fa-cash-register"></i> <span>Ventas</span></a>
            <a href="#" class="nav-item" data-tab="inventario"><i class="fas fa-boxes-stacked"></i> <span>Inventario</span></a>
            <a href="#" class="nav-item" data-tab="registros"><i class="fas fa-chart-line"></i> <span>Registros</span></a>
            <a href="#" class="nav-item admin-only" data-tab="septimas"><i class="fas fa-wine-glass"></i> <span>Séptimas</span></a>
            <a href="#" class="nav-item admin-only" data-tab="reportes"><i class="fas fa-file-excel"></i> <span>Reportes</span></a>
            
            <div class="nav-group superadmin-only" style="display:flex; gap:5px; margin-left:10px; border-left:1px solid rgba(255,255,255,0.3); padding-left:10px;">
                <a href="#" class="nav-item" data-tab="errores" title="Log Errores"><i class="fas fa-triangle-exclamation"></i></a>
                <a href="#" class="nav-item" data-tab="config" title="Configuración y Usuarios"><i class="fas fa-cog"></i></a>
            </div>
        </nav>

        <div class="user-menu">
            <div class="user-details">
                <span class="user-name"><?php echo $vendedor_nombre; ?></span>
                <span class="user-role badge-role"><?php echo ucfirst($role_actual); ?></span>
                <?php if ($role_actual === 'vendedor'): ?>
                    <small style="display:block; font-size:0.7em; opacity:0.8;">Pad: <?php echo $vendedor_padrino; ?></small>
                <?php endif; ?>
            </div>
            <a href="api/api_logout.php" class="btn-logout-nav" title="Cerrar Sesión"><i class="fas fa-power-off"></i></a>
        </div>
    </header>

    <main class="main-container">
        <div class="tab-content active" id="ventas">
            <div class="section-header ventas-header">
                <h2><i class="fas fa-cash-register"></i> Módulo de Ventas</h2>
                <span id="reloj-digital">--:--:-- --</span>
            </div>
            <div class="venta-container">
                <div class="venta-izquierda">
                    <h3>Registrar Venta</h3>
                    <div class="input-group">
                        <label>Buscar (Nombre o Código):</label>
                        <input type="text" id="buscar-producto" placeholder="Escribe para buscar...">
                        <div id="resultados-busqueda"></div>
                    </div>
                    <div id="producto-seleccionado"></div>
                    <div class="input-group">
                        <label>Cantidad:</label>
                        <input type="number" id="cantidad-venta" value="1" min="1">
                    </div>
                    <button class="btn btn-primario" id="agregar-carrito"><i class="fas fa-cart-plus"></i> Agregar al Carrito</button>
                    <h3 style="margin-top: 2rem;"><i class="fas fa-user-clock"></i> Deudores</h3>
                    <div class="lista-deudores-container">
                        <table id="tabla-deudores">
                            <thead><tr><th>Padrino/Madrina</th><th>Total Deuda</th><th>Acción</th></tr></thead>
                            <tbody id="cuerpo-tabla-deudores"></tbody>
                        </table>
                    </div>
                </div>
                <div class="venta-derecha">
                    <h3><i class="fas fa-shopping-cart"></i> Carrito</h3>
                    <ul id="carrito-lista"></ul>
                    <hr>
                    <div class="input-group">
                        <label>Tipo de Pago:</label>
                        <select id="tipo-pago">
                            <option value="pagado">Pagado (Efectivo)</option>
                            <option value="fiado">Fiado (Crédito)</option>
                        </select>
                    </div>
                    <div class="input-group" id="nombre-fiado-group" style="display:none;">
                        <label>Nombre (Padrino/Madrina):</label>
                        <input type="text" id="nombre-fiado" placeholder="Escribe el nombre completo">
                    </div>
                    <h3 id="carrito-total">Total: $0.00</h3>
                    <div class="carrito-botones">
                        <button class="btn btn-success" id="finalizar-venta"><i class="fas fa-check"></i> Finalizar</button>
                        <button class="btn btn-danger" id="cancelar-venta"><i class="fas fa-times"></i> Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="inventario">
            <div class="section-header inventario-header"><h2><i class="fas fa-boxes-stacked"></i> Gestión de Inventario</h2></div>
            <button class="btn btn-primario admin-only" id="btn-mostrar-form-producto"><i class="fas fa-plus"></i> Agregar Producto</button>
            <form id="form-producto" class="admin-only" style="display:none;">
                <h3>Nuevo/Editar Producto</h3>
                <input type="hidden" id="producto-id">
                <div class="form-grid">
                    <div class="input-group"><label>Nombre:</label><input type="text" id="producto-nombre" required></div>
                    <div class="input-group"><label>Código Barras:</label><input type="text" id="producto-codigo"></div>
                    <div class="input-group"><label>Precio Venta:</label><input type="number" id="producto-precio" step="0.01" required></div>
                    <div class="input-group"><label>Stock Inicial:</label><input type="number" id="producto-stock" required></div>
                    <div class="input-group form-span-2"><label>URL Foto:</label><input type="text" id="producto-foto"></div>
                </div>
                <button type="submit" class="btn btn-success">Guardar Producto</button>
            </form>
            <h3>Inventario</h3>
            <table id="tabla-inventario">
                <thead><tr><th>Foto</th><th>Nombre</th><th>Código</th><th>Precio</th><th>Stock</th><th class="admin-only">Acciones</th></tr></thead>
                <tbody id="cuerpo-tabla-inventario"></tbody>
            </table>
        </div>

        <div class="tab-content" id="registros">
            <div class="section-header registros-header"><h2><i class="fas fa-chart-line"></i> Registros y Ventas</h2></div>
            <form id="form-registro" class="admin-only">
                <h3>Nuevo Registro Manual</h3>
                <div class="form-grid">
                    <div class="input-group"><label>Tipo:</label><select id="registro-tipo"><option value="ingreso">Ingreso</option><option value="gasto">Gasto</option><option value="egreso">Egreso</option><option value="merma">Merma</option></select></div>
                    <div class="input-group"><label>Concepto:</label><input type="text" id="registro-concepto" required></div>
                    <div class="input-group"><label>Monto ($):</label><input type="number" id="registro-monto" step="0.01" required></div>
                </div>
                <button type="submit" class="btn btn-primario">Agregar Registro</button>
            </form>
            <h3>Registros Manuales</h3>
            <table id="tabla-registros"><thead><tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Monto</th><th>Usuario</th><th class="admin-only">Acciones</th></tr></thead><tbody id="cuerpo-tabla-registros"></tbody></table>
            <h3 style="margin-top: 2rem;">Historial de Ventas</h3>
            <table id="tabla-ventas-historial"><thead><tr><th>Fecha</th><th>Vendedor</th><th>Producto</th><th>Cant.</th><th>Total</th><th>Tipo Pago</th><th>Fiado a</th><th class="admin-only">Acciones</th></tr></thead><tbody id="cuerpo-tabla-ventas"></tbody></table>
        </div>

        <div class="tab-content admin-only" id="septimas">
            <div class="section-header septimas-header"><h2><i class="fas fa-wine-glass"></i> Séptimas</h2></div>
            <form id="form-septima">
                <h3>Registrar Séptima</h3>
                <input type="hidden" id="septima-id">
                <div class="form-grid">
                    <div class="input-group"><label>¿Quién pidió?</label><input type="text" id="septima-nombre" required></div>
                    <div class="input-group"><label>Monto ($):</label><input type="number" id="septima-monto" step="0.01" required></div>
                </div>
                <button type="submit" class="btn btn-primario">Guardar</button>
            </form>
            <h3>Historial</h3>
            <table id="tabla-septimas"><thead><tr><th>Fecha</th><th>Padrino/Madrina</th><th>Monto</th><th>Registró</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="cuerpo-tabla-septimas"></tbody></table>
        </div>

        <div class="tab-content admin-only" id="reportes">
            <div class="section-header reportes-header"><h2><i class="fas fa-file-excel"></i> Reportes</h2></div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn btn-success" id="btn-reporte-inventario"><i class="fas fa-file-excel"></i> Inventario (Excel)</button>
                <button class="btn btn-primario" id="btn-reporte-consolidado"><i class="fas fa-file-archive"></i> Reporte Completo (Excel)</button>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="errores">
            <div class="section-header errores-header"><h2><i class="fas fa-triangle-exclamation"></i> Log de Errores</h2></div>
            <button id="btn-recargar-errores" class="btn"><i class="fas fa-sync"></i> Recargar</button>
            <table id="tabla-errores"><thead><tr><th>Fecha</th><th>Error</th></tr></thead><tbody id="cuerpo-tabla-errores"></tbody></table>
        </div>

        <div class="tab-content superadmin-only" id="config">
            <div class="section-header config-header"><h2><i class="fas fa-cog"></i> Configuración</h2></div>
            
            <div style="background:white; padding:2rem; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:2rem; border-left: 5px solid var(--color-admin);">
                <h3 style="margin-top:0; color:var(--color-admin);"><i class="fas fa-users-cog"></i> Crear Nuevo Administrador</h3>
                <p>Solo puedes crear cuentas con permisos de <strong>Administrador</strong>.</p>
                
                <form id="form-crear-usuario" style="box-shadow:none; padding:0; margin:1rem 0;">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Usuario (Login):</label>
                            <input type="text" id="nuevo-usuario-user" required placeholder="Ej: admin_nuevo">
                        </div>
                        <div class="input-group">
                            <label>Contraseña:</label>
                            <input type="password" id="nuevo-usuario-pass" required placeholder="******">
                        </div>
                        <div class="input-group">
                            <label>Rol Asignado:</label>
                            <input type="text" value="Administrador" disabled style="background-color: #e9ecef; cursor: not-allowed; color: #555; font-weight:bold;">
                            <input type="hidden" id="nuevo-usuario-role" value="admin">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:auto;"><i class="fas fa-user-plus"></i> Crear Administrador</button>
                </form>

                <h4>Usuarios del Sistema</h4>
                <table id="tabla-usuarios-sistema" style="margin-top:0.5rem;">
                    <thead><tr><th>ID</th><th>Usuario</th><th>Rol</th><th>Acciones</th></tr></thead>
                    <tbody id="cuerpo-tabla-usuarios"></tbody>
                </table>
            </div>

            <hr>
            <h3>Personalización</h3>
            <div class="input-group" style="max-width:200px;">
                <label>Color Principal:</label>
                <input type="color" id="color-picker" value="#0d47a1">
            </div>
            <hr>
            <h3>Seguridad</h3>
            <button id="btn-respaldo-db" class="btn btn-primario" style="background-color: #2c3e50;"><i class="fas fa-download"></i> Descargar Respaldo BD</button>
        </div>
    </main>
    
    <script src="assets/js/app.js" type="module"></script>
</body>
</html>