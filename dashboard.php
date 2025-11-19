<?php
// dashboard.php
// VERSIÓN FINAL BLINDADA v2.7
// Interfaz principal con protección de roles y manejo de errores visuales.

session_start();

// 1. SEGURIDAD DE SESIÓN
// Si no hay usuario, sacar inmediatamente al login
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

// 2. SANITIZACIÓN DE DATOS (Anti-Hack)
// Limpiamos cualquier dato que venga de la sesión para que no pueda inyectar HTML/JS
$usuario_actual = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
$role_actual = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
$vendedor_nombre = isset($_SESSION['vendedor_nombre']) ? htmlspecialchars($_SESSION['vendedor_nombre'], ENT_QUOTES, 'UTF-8') : $usuario_actual;
$vendedor_padrino = isset($_SESSION['vendedor_padrino']) ? htmlspecialchars($_SESSION['vendedor_padrino'], ENT_QUOTES, 'UTF-8') : 'N/A';

// Definir si es Admin para mostrar/ocultar bloques de HTML desde el servidor (más seguro que CSS)
$esAdmin = ($role_actual === 'admin' || $role_actual === 'superadmin');
$esSuperAdmin = ($role_actual === 'superadmin');
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
            <img src="assets/img/logo-agua-viva.png" alt="Logo" class="logo-nav" onerror="this.style.display='none'">
            <div style="display:flex; flex-direction:column; line-height:1.1; margin-left: 10px;">
                <span class="brand-name" style="font-size:1.2rem;">TG Gestión</span>
                <span style="font-size:0.75rem; opacity:0.8; font-weight:400;">Agua Viva</span>
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
            
            <div class="nav-group superadmin-only" style="display:flex; gap:5px; margin-left:10px; border-left:1px solid rgba(255,255,255,0.3); padding-left:10px;">
                <a href="#" class="nav-item" data-tab="errores" title="Log de Errores">
                    <i class="fas fa-triangle-exclamation"></i>
                </a>
                <a href="#" class="nav-item" data-tab="config" title="Configuración y Usuarios">
                    <i class="fas fa-cog"></i>
                </a>
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
            <a href="api/api_logout.php" class="btn-logout-nav" title="Cerrar Sesión">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </header>

    <main class="main-container">

        <div class="tab-content active" id="ventas">
            <div class="section-header ventas-header">
                <h2><i class="fas fa-cash-register"></i> Punto de Venta</h2>
                <span id="reloj-digital">--:--:-- --</span>
            </div>
            
            <div class="venta-container">
                <div class="venta-izquierda">
                    <h3>Registrar Venta</h3>
                    <div class="input-group">
                        <label for="buscar-producto">Buscar Producto:</label>
                        <input type="text" id="buscar-producto" placeholder="Escribe nombre o código..." autocomplete="off">
                        <div id="resultados-busqueda"></div>
                    </div>
                    
                    <div id="producto-seleccionado"></div>
                    
                    <div class="input-group">
                        <label for="cantidad-venta">Cantidad:</label>
                        <input type="number" id="cantidad-venta" value="1" min="1" onkeypress="return event.charCode >= 48">
                    </div>
                    
                    <button class="btn btn-primario" id="agregar-carrito">
                        <i class="fas fa-cart-plus"></i> Agregar al Carrito
                    </button>
                    
                    <hr style="margin: 2rem 0;">
                    
                    <h3><i class="fas fa-user-clock"></i> Deudores (Fiados Pendientes)</h3>
                    <div class="lista-deudores-container">
                        <table id="tabla-deudores">
                            <thead>
                                <tr>
                                    <th>Padrino/Madrina</th>
                                    <th>Deuda</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla-deudores">
                                </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="venta-derecha">
                    <h3><i class="fas fa-shopping-cart"></i> Carrito de Compra</h3>
                    <ul id="carrito-lista">
                        <li style="text-align:center; color:#888; padding:20px;">Carrito vacío</li>
                    </ul>
                    <hr>
                    <div class="input-group">
                        <label for="tipo-pago">Método de Pago:</label>
                        <select id="tipo-pago">
                            <option value="pagado">💰 Efectivo (Pagado)</option>
                            <option value="fiado">📝 Fiado (Crédito)</option>
                        </select>
                    </div>
                    
                    <div class="input-group" id="nombre-fiado-group" style="display:none;">
                        <label for="nombre-fiado">Nombre del Deudor:</label>
                        <input type="text" id="nombre-fiado" placeholder="Nombre completo del Padrino/Madrina">
                    </div>
                    
                    <h3 id="carrito-total" style="font-size: 1.5rem; color: var(--color-primario); text-align: right;">Total: $0.00</h3>
                    
                    <div class="carrito-botones">
                        <button class="btn btn-success" id="finalizar-venta">
                            <i class="fas fa-check-circle"></i> Cobrar
                        </button>
                        <button class="btn btn-danger" id="cancelar-venta">
                            <i class="fas fa-trash-alt"></i> Vaciar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="inventario">
            <div class="section-header inventario-header">
                <h2><i class="fas fa-boxes-stacked"></i> Inventario</h2>
            </div>
            
            <button class="btn btn-primario admin-only" id="btn-mostrar-form-producto">
                <i class="fas fa-plus-circle"></i> Agregar Nuevo Producto
            </button>
            
            <form id="form-producto" class="admin-only" style="display:none;">
                <h3>Gestión de Producto</h3>
                <input type="hidden" id="producto-id">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Nombre del Producto:</label>
                        <input type="text" id="producto-nombre" required>
                    </div>
                    <div class="input-group">
                        <label>Código de Barras (Opcional):</label>
                        <input type="text" id="producto-codigo">
                    </div>
                    <div class="input-group">
                        <label>Precio Venta ($):</label>
                        <input type="number" id="producto-precio" step="0.50" min="0" required>
                    </div>
                    <div class="input-group">
                        <label>Stock Inicial:</label>
                        <input type="number" id="producto-stock" min="0" required>
                    </div>
                    <div class="input-group form-span-2">
                        <label>URL de Foto (Opcional):</label>
                        <input type="text" id="producto-foto" placeholder="https://ejemplo.com/imagen.jpg">
                        <small>Deja vacío para usar imagen por defecto.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>

            <h3>Catálogo de Productos</h3>
            <div style="overflow-x:auto;">
                <table id="tabla-inventario">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Img</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th class="admin-only">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-inventario"></tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="registros">
            <div class="section-header registros-header">
                <h2><i class="fas fa-chart-line"></i> Registros de Caja</h2>
            </div>

            <form id="form-registro" class="admin-only">
                <h3>Registrar Movimiento Manual</h3>
                <div class="form-grid">
                    <div class="input-group">
                        <label>Tipo de Movimiento:</label>
                        <select id="registro-tipo">
                            <option value="ingreso">🟢 Ingreso (Dinero entra)</option>
                            <option value="gasto">🔴 Gasto (Dinero sale)</option>
                            <option value="egreso">📤 Retiro de Caja</option>
                            <option value="merma">⚠️ Merma (Pérdida)</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Concepto (Motivo):</label>
                        <input type="text" id="registro-concepto" required placeholder="Ej: Pago de luz, Compra de bolsas">
                    </div>
                    <div class="input-group">
                        <label>Monto ($):</label>
                        <input type="number" id="registro-monto" step="0.50" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primario">
                    <i class="fas fa-save"></i> Registrar Movimiento
                </button>
            </form>
            
            <h3>Últimos Movimientos de Caja</h3>
            <table id="tabla-registros">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                        <th>Usuario</th>
                        <th class="admin-only">Borrar</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-registros"></tbody>
            </table>
            
            <h3 style="margin-top: 2rem;">Historial Reciente de Ventas</h3>
            <table id="tabla-ventas-historial">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Vendedor</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Total</th>
                        <th>Tipo</th>
                        <th>Fiado</th>
                        <th class="admin-only">Devolución</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-ventas"></tbody>
            </table>
        </div>

        <div class="tab-content admin-only" id="septimas">
            <div class="section-header septimas-header">
                <h2><i class="fas fa-wine-glass"></i> Control de Séptimas</h2>
            </div>

            <form id="form-septima">
                <h3>Nueva Séptima</h3>
                <input type="hidden" id="septima-id">
                <div class="form-grid">
                    <div class="input-group">
                        <label>¿Quién pidió?</label>
                        <input type="text" id="septima-nombre" required placeholder="Nombre del Padrino/Madrina">
                    </div>
                    <div class="input-group">
                        <label>Monto ($):</label>
                        <input type="number" id="septima-monto" step="0.50" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primario">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </form>
            
            <h3>Listado de Séptimas</h3>
            <table id="tabla-septimas">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Padrino/Madrina</th>
                        <th>Monto</th>
                        <th>Registró</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-septimas"></tbody>
            </table>
        </div>

        <div class="tab-content admin-only" id="reportes">
            <div class="section-header reportes-header">
                <h2><i class="fas fa-file-excel"></i> Centro de Reportes</h2>
            </div>
            
            <div style="background:white; padding:2rem; border-radius:10px; text-align:center;">
                <h3>Generar Archivos Excel</h3>
                <p>Selecciona el tipo de reporte que deseas descargar. El archivo se guardará automáticamente en tu carpeta de <strong>Descargas</strong>.</p>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content:center; margin-top:2rem;">
                    <button class="btn btn-success" id="btn-reporte-inventario">
                        <i class="fas fa-boxes"></i> Solo Inventario
                    </button>
                    
                    <button class="btn btn-primario" id="btn-reporte-consolidado">
                        <i class="fas fa-file-contract"></i> Reporte Completo (Todo)
                    </button>
                </div>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="errores">
            <div class="section-header errores-header">
                <h2><i class="fas fa-triangle-exclamation"></i> Log de Errores</h2>
            </div>
            <button id="btn-recargar-errores" class="btn">
                <i class="fas fa-sync"></i> Actualizar Lista
            </button>
            <table id="tabla-errores">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Descripción del Error</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-errores"></tbody>
            </table>
        </div>

        <div class="tab-content superadmin-only" id="config">
            <div class="section-header config-header">
                <h2><i class="fas fa-cog"></i> Configuración del Sistema</h2>
            </div>
            
            <div style="background:white; padding:2rem; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:2rem; border-left: 5px solid var(--color-admin);">
                <h3 style="margin-top:0; color:var(--color-admin);"><i class="fas fa-user-plus"></i> Crear Nuevo Administrador</h3>
                <p>Utiliza este formulario para dar acceso a nuevos encargados.</p>
                
                <form id="form-crear-usuario" style="box-shadow:none; padding:0; margin:1rem 0;">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Usuario (Login):</label>
                            <input type="text" id="nuevo-usuario-user" required placeholder="Ej: admin_nuevo" autocomplete="off">
                        </div>
                        <div class="input-group">
                            <label>Contraseña:</label>
                            <input type="password" id="nuevo-usuario-pass" required placeholder="******" autocomplete="new-password">
                        </div>
                        <div class="input-group">
                            <label>Rol Asignado:</label>
                            <input type="text" value="Administrador" disabled style="background-color: #e9ecef; cursor: not-allowed; color: #555; font-weight:bold;">
                            <input type="hidden" id="nuevo-usuario-role" value="admin">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:auto;">
                        <i class="fas fa-check"></i> Crear Administrador
                    </button>
                </form>

                <h4><i class="fas fa-users"></i> Usuarios del Sistema</h4>
                <table id="tabla-usuarios-sistema" style="margin-top:0.5rem;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-usuarios"></tbody>
                </table>
            </div>

            <hr>

            <h3><i class="fas fa-palette"></i> Personalización</h3>
            <div class="input-group" style="max-width:200px;">
                <label>Color Principal:</label>
                <input type="color" id="color-picker" value="#0d47a1">
            </div>
            <p><small>El color se guardará automáticamente.</small></p>

            <hr>

            <h3><i class="fas fa-database"></i> Seguridad y Respaldo</h3>
            <p>Descarga una copia de seguridad completa de la base de datos (Ventas, Productos, Usuarios).</p>
            <button id="btn-respaldo-db" class="btn btn-primario" style="background-color: #2c3e50;">
                <i class="fas fa-download"></i> Descargar Respaldo (.sqlite)
            </button>
        </div>
    </main>
    
    <script src="assets/js/app.js" type="module"></script>
</body>
</html>