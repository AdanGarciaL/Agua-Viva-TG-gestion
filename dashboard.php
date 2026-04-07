<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
} else {
    session_set_cookie_params(0, '/','', false, true);
}
session_start();
if (!isset($_SESSION["usuario"])) { header("Location: index.php"); exit(); }
session_regenerate_id(true);

$usuario_actual = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
$role_actual = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
$vendedor_nombre = isset($_SESSION['vendedor_nombre']) ? htmlspecialchars($_SESSION['vendedor_nombre'], ENT_QUOTES, 'UTF-8') : $usuario_actual;
$es_admin_help = in_array($_SESSION['role'], ['admin', 'superadmin'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tienda Regional (TG Gestión v10 Beta)</title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link href="assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
</head>

<body data-role="<?php echo $role_actual; ?>">

    <header class="top-navbar">
        <div class="nav-brand">
            <img src="assets/img/logo-agua-viva.png" alt="Logo" class="logo-nav" onerror="this.style.display='none'">
            <div style="display:flex; flex-direction:column; line-height:1.1; margin-left: 10px;">
                <span class="brand-name">Tienda Regional</span>
                <span style="font-size:0.7rem; opacity:0.8; font-weight:400;">v10 Beta Offline Edition</span>
            </div>
        </div>

        <div id="banner-stock-alerta" style="display:none;" class="floating-banner" onclick="document.querySelector('[data-tab=inventario]').click()">
            <i class="fas fa-exclamation-triangle animate-pulse"></i>
            <span id="banner-stock-text">⚠️ Productos sin stock</span>
        </div>

        <nav class="nav-menu">
            <a href="#" class="nav-item active" data-tab="ventas">
                <i class="fas fa-cash-register"></i> <span>Ventas</span>
            </a>
            <a href="#" class="nav-item" data-tab="inventario">
                <i class="fas fa-boxes-stacked"></i> <span>Inventario</span>
                <span id="badge-stock-bajo" style="display:none; position:absolute; top:5px; right:5px; background:#f44336; color:white; font-size:11px; font-weight:bold; padding:2px 6px; border-radius:10px; min-width:18px; text-align:center;"></span>
            </a>
            <a href="#" class="nav-item" data-tab="registros">
                <i class="fas fa-chart-line"></i> <span>Registros</span>
            </a>
            <a href="#" class="nav-item admin-only" data-tab="septimas">
                <i class="fas fa-hand-holding-heart"></i> <span>Séptimas</span>
            </a>
            <a href="#" class="nav-item admin-only" data-tab="reportes">
                <i class="fas fa-file-excel"></i> <span>Reportes</span>
            </a>
            <a href="#" class="nav-item" data-tab="cuentas">
                <i class="fas fa-user-tie"></i> <span>Cuentas</span>
            </a>
            <a href="#" class="nav-item admin-only" data-tab="cortes">
                <i class="fas fa-cash-register"></i> <span>Cortes</span>
            </a>
            <div class="nav-group superadmin-only" style="display:flex; gap:5px; margin-left:10px; padding-left:10px; border-left:1px solid var(--color-borde);">
                <a href="#" class="nav-item" data-tab="errores"><i class="fas fa-bug"></i></a>
                <a href="#" class="nav-item" data-tab="config"><i class="fas fa-cog"></i></a>
            </div>
        </nav>

        <div class="user-menu">
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
                </div>
                
                <div class="venta-derecha">
                    <h3><i class="fas fa-receipt"></i> Ticket Actual</h3>
                    <ul id="carrito-lista"><li style="text-align:center; padding:20px; opacity:0.6;">Vacío</li></ul>
                    <hr style="border-color:var(--color-borde);">
                    
                    <div class="input-group">
                        <label>Pago:</label>
                        <select id="tipo-pago">
                            <option value="pagado">Efectivo</option>
                            <option value="tarjeta">Tarjeta (Débito/Crédito)</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cuenta">A cuenta</option>
                        </select>
                    </div>
                    
                    <div class="input-group" id="comprobante-tarjeta-group" style="display:none;">
                        <label>Comprobante Tarjeta:</label>
                        <input type="text" id="comprobante-tarjeta" placeholder="Ej: 123456 (últimos dígitos)">
                    </div>
                    
                    <div class="input-group" id="referencia-transferencia-group" style="display:none;">
                        <label>Referencia Transferencia:</label>
                        <input type="text" id="referencia-transferencia" placeholder="Folio o referencia del banco">
                    </div>
                    
                    <div class="input-group" id="cuenta-nombre-group" style="display:none; position:relative;">
                        <label>Cuenta:</label>
                        <input type="text" id="cuenta-nombre" placeholder="Escribe el nombre de la cuenta" autocomplete="off">
                        <div id="cuenta-sugerencias" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:30; background:var(--color-blanco); border:1px solid var(--color-borde); border-radius:10px; box-shadow:var(--sombra); max-height:240px; overflow-y:auto;"></div>
                    </div>
                    
                    <div class="input-group" id="cuenta-grupo-group" style="display:none;">
                        <label>Grupo/Región:</label>
                        <input type="text" id="cuenta-grupo" placeholder="Se llena al seleccionar" readonly>
                    </div>

                    <div class="input-group" id="cuenta-numero-group" style="display:none;">
                        <label>Número:</label>
                        <input type="text" id="cuenta-numero" placeholder="Se llena al seleccionar" readonly>
                    </div>

                    <div id="cuenta-seleccionada-info" style="display:none; margin:8px 0 12px; padding:10px; border-radius:10px; border-left:4px solid var(--color-info); background:var(--color-input-bg); font-size:0.92rem;"></div>
                    
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
            
            <div class="admin-only" style="display:grid; grid-template-columns: 1fr; gap:20px; margin-bottom:30px;">
                <div class="stock-alert-card" onclick="inventario.mostrarStockBajo(); return false;" role="button" tabindex="0" aria-label="Ver productos con stock bajo">
                    <div class="stock-alert-card__icon">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="stock-alert-card__content">
                        <span class="stock-alert-card__eyebrow">ALERTA OPERATIVA</span>
                        <div class="stock-alert-card__headline">
                            <span class="stock-alert-card__count" id="stock-bajo-count">0</span>
                            <div class="stock-alert-card__text">
                                <strong>Productos bajo umbral</strong>
                                <span>Haz clic para revisar el inventario y reabastecer</span>
                            </div>
                        </div>
                    </div>
                    <div class="stock-alert-card__action">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </div>
            
            <div class="module-toolbar admin-only">
                <button class="btn btn-primario" id="btn-mostrar-form-producto" style="width:auto;">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </button>
                <button class="btn btn-secundario" id="btn-verificar-integridad" style="width:auto;">
                    <i class="fas fa-shield-alt"></i> Verificar Integridad
                </button>
                <div class="toolbar-status" id="inventario-resumen-ui">
                    <i class="fas fa-layer-group"></i>
                    <span id="inventario-count">0 productos</span>
                </div>
            </div>
            
            <div class="admin-only table-toolbar-card">
                <div class="table-toolbar-grid">
                    <div class="input-group" style="margin:0;">
                        <label>Búsqueda:</label>
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
                    <div class="input-group" style="margin:0;">
                        <label>Tipo:</label>
                        <select id="filtro-tipo">
                            <option value="todos">Producto + Preparado</option>
                            <option value="producto">Solo Producto</option>
                            <option value="preparado">Solo Preparado</option>
                        </select>
                    </div>
                </div>
                <div class="table-toolbar-actions">
                    <button class="btn btn-secundario" id="btn-limpiar-filtros-inventario" type="button">
                        <i class="fas fa-eraser"></i> Limpiar filtros
                    </button>
                    <button class="btn btn-primario" id="btn-aplicar-filtros-inventario" type="button">
                        <i class="fas fa-filter"></i> Aplicar filtros
                    </button>
                </div>
            </div>
            
            <div id="modal-producto" class="modal" style="display:none !important;">
                <div class="modal-content" style="animation: slideUp 0.3s ease-out;">
                    <div class="modal-header">
                        <h3 id="modal-titulo-producto" style="margin:0; color:var(--color-texto);">Nuevo Producto</h3>
                        <button type="button" class="btn-close" onclick="document.getElementById('modal-producto').style.display='none'; document.getElementById('form-producto').reset();">×</button>
                    </div>
                    <form id="form-producto" style="padding:20px;">
                        <input type="hidden" id="producto-id" name="id">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="input-group"><label>Nombre:</label><input type="text" id="producto-nombre" name="nombre" required></div>
                            <div class="input-group"><label>Tipo:</label><select id="producto-tipo" name="tipo_producto" required>
                                <option value="">-- Selecciona tipo --</option>
                                <option value="producto">Producto</option>
                                <option value="preparado">Preparado</option>
                            </select></div>
                            <div class="input-group"><label>Código:</label><input type="text" id="producto-codigo" name="codigo"></div>
                            <div class="input-group"><label>Precio:</label><input type="number" id="producto-precio" name="precio" step="0.50" required></div>
                            <div class="input-group"><label>Stock:</label><input type="number" id="producto-stock" name="stock" required></div>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                            <button type="button" class="btn btn-secundario" onclick="document.getElementById('modal-producto').style.display='none'; document.getElementById('form-producto').reset();">Cancelar</button>
                            <button type="submit" class="btn btn-success">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-wrap">
                <table id="tabla-inventario">
                    <thead><tr><th>Nombre</th><th>Tipo</th><th>Código</th><th>Precio</th><th>Stock</th><th class="admin-only">Opciones</th></tr></thead>
                    <tbody id="cuerpo-tabla-inventario"></tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="registros">
            <div class="section-header" style="background: linear-gradient(135deg, var(--color-warning), #ffb74d);">
                <h2><i class="fas fa-cash-register"></i> Movimientos de Efectivo</h2>
            </div>

            <div class="module-toolbar admin-only">
                <button class="btn btn-primario" id="btn-agregar-registro" style="width:auto;">
                    <i class="fas fa-plus"></i> Agregar Movimiento
                </button>
                <button class="btn btn-danger" id="btn-eliminar-todos-registros" style="width:auto;">
                    <i class="fas fa-trash-alt"></i> Eliminar Todo
                </button>
                <div class="toolbar-status" id="registros-resumen-ui">
                    <i class="fas fa-chart-line"></i>
                    <span id="registros-count">0 movimientos</span>
                    <strong id="registros-total-visibles" style="margin-left:12px;">$0.00</strong>
                </div>
            </div>

            <div class="table-toolbar-card">
                <div class="table-toolbar-grid">
                    <div class="input-group" style="margin:0;">
                        <label>Búsqueda:</label>
                        <input type="text" id="reg-filtro-buscar" placeholder="Concepto, usuario, tipo...">
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Tipo:</label>
                        <select id="reg-filtro-tipo">
                            <option value="todos">Todos</option>
                            <option value="ingreso">Ingreso</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="fiado">Fiado</option>
                            <option value="gasto">Gasto</option>
                            <option value="egreso">Egreso</option>
                            <option value="merma">Merma</option>
                            <option value="septima">Séptima</option>
                            <option value="septima_especial">Séptima Especial</option>
                            <option value="arca_ingreso">Arca Ingreso</option>
                            <option value="arca_egreso">Arca Egreso</option>
                            <option value="arca_gasto">Arca Gasto</option>
                            <option value="arca_merma">Arca Merma</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Categoría:</label>
                        <select id="reg-filtro-categoria">
                            <option value="todas">Todas</option>
                            <option value="caja">Caja</option>
                            <option value="arca">Arca</option>
                            <option value="ingreso_tienda">Ingreso tienda</option>
                            <option value="septima">Séptima</option>
                            <option value="septima_especial">Séptima Especial</option>
                            <option value="merma">Merma</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Usuario:</label>
                        <input type="text" id="reg-filtro-usuario" placeholder="Ej: AdanGL">
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Desde:</label>
                        <input type="date" id="reg-filtro-desde">
                    </div>
                    <div class="input-group" style="margin:0;">
                        <label>Hasta:</label>
                        <input type="date" id="reg-filtro-hasta">
                    </div>
                </div>
                <div class="table-toolbar-actions">
                    <button class="btn btn-secundario" id="btn-limpiar-filtros-registros" type="button">
                        <i class="fas fa-eraser"></i> Limpiar filtros
                    </button>
                    <button class="btn btn-primario" id="btn-aplicar-filtros-registros" type="button">
                        <i class="fas fa-search"></i> Buscar en tabla
                    </button>
                </div>
            </div>

            <div id="modal-registro" class="modal" style="display:none !important;">
                <div class="modal-content" style="animation: slideUp 0.3s ease-out;">
                    <div class="modal-header">
                        <h3 id="modal-titulo-registro" style="margin:0; color:var(--color-texto);">Nuevo Movimiento</h3>
                        <button type="button" class="btn-close" onclick="document.getElementById('modal-registro').style.display='none'; document.getElementById('form-registro').reset();">×</button>
                    </div>
                    <form id="form-registro" style="padding:20px;">
                        <input type="hidden" id="registro-id" name="id">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; align-items:end;">
                            <div class="input-group"><label>Tipo:</label><select id="registro-tipo" name="tipo"><option value="ingreso">Ingreso Extra</option><option value="gasto">Gasto</option><option value="egreso">Retiro de Caja</option><option value="merma">Merma</option><option value="septima">Séptima</option><option value="septima_especial">Séptima Especial</option><option value="arca_ingreso">Arca Ingreso</option><option value="arca_egreso">Arca Egreso</option><option value="arca_gasto">Arca Gasto</option><option value="arca_merma">Arca Merma</option></select></div>
                            <div class="input-group"><label>Categoría:</label><select id="registro-categoria" name="categoria"><option value="caja">Caja</option><option value="septima">Séptima</option><option value="septima_especial">Séptima Especial</option><option value="merma">Merma</option><option value="arca">Arca de Servicio</option></select></div>
                            <div class="input-group"><label>Servicio (solo arcas):</label>
                                <select id="registro-servicio" name="servicio">
                                    <option value="">--</option>
                                    <option value="COM">COM</option>
                                    <option value="AE">AE</option>
                                    <option value="AI">AI</option>
                                    <option value="Literatura">Literatura</option>
                                    <option value="RSG">RSG</option>
                                </select>
                            </div>
                            <div class="input-group"><label>Concepto:</label><input type="text" id="registro-concepto" name="concepto" required placeholder="Ej: Pago de Luz"></div>
                            <div class="input-group"><label>Monto:</label><input type="number" id="registro-monto" name="monto" step="0.50" required></div>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                            <button type="button" class="btn btn-secundario" onclick="document.getElementById('modal-registro').style.display='none'; document.getElementById('form-registro').reset();">Cancelar</button>
                            <button type="submit" class="btn btn-success">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-wrap">
                <table id="tabla-registros">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Servicio</th><th>Concepto</th><th>Monto</th><th>Usuario</th><th class="admin-only">Acciones</th></tr></thead><tbody id="cuerpo-tabla-registros"></tbody></table>
            </div>
            
            <h3 style="margin-top:30px; color:var(--color-texto);">Historial Ventas</h3>
            <div class="table-wrap">
                <table id="tabla-ventas-historial">
                    <thead><tr><th>Fecha</th><th>Vendedor</th><th>Producto</th><th>Cant</th><th>Total</th><th>Tipo</th><th>Eliminar</th></tr></thead><tbody id="cuerpo-tabla-ventas"></tbody></table>
            </div>
        </div>


        <div class="tab-content admin-only" id="septimas">
            <div class="section-header" style="background: linear-gradient(135deg, #5e35b1, #9575cd);">
                <h2><i class="fas fa-hand-holding-heart"></i> Séptimas</h2>
            </div>
            <form id="form-septima" style="background:var(--color-blanco); padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:var(--sombra);">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:15px; align-items:end;">
                    <div class="input-group"><label>Padrino/Origen:</label><input type="text" id="septima-nombre" name="nombre_padrino" required></div>
                    <div class="input-group"><label>Monto:</label><input type="number" id="septima-monto" name="monto" min="0" step="0.50" required></div>
                    <div class="input-group"><label>Tipo:</label><select id="septima-tipo" name="tipo"><option value="normal">Séptima</option><option value="especial">Séptima Especial</option></select></div>
                    <div class="input-group"><label>Servicio (opcional):</label>
                        <select id="septima-servicio" name="servicio">
                            <option value="">-- Sin servicio --</option>
                            <option value="COM">COM</option>
                            <option value="AE">AE</option>
                            <option value="AI">AI</option>
                            <option value="Literatura">Literatura</option>
                            <option value="RSG">RSG</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="min-width:200px;">Registrar</button>
                </div>
            </form>

            <div class="table-wrap">
                <table id="tabla-septimas">
                    <thead><tr><th>Fecha</th><th>Padrino/Origen</th><th>Monto</th><th>Tipo</th><th>Servicio</th><th>Pagado</th><th class="admin-only">Acciones</th></tr></thead>
                    <tbody id="cuerpo-tabla-septimas"></tbody>
                </table>
            </div>
        </div>

        <div class="tab-content admin-only" id="reportes">
            <div class="section-header" style="background: linear-gradient(135deg, #00695c, #4db6ac);">
                <h2><i class="fas fa-file-excel"></i> Reportes</h2>
            </div>
            <div class="reportes-shell">
                <div class="reportes-hero">
                    <div>
                        <h3>Centro de Exportacion</h3>
                        <p>Genera reportes listos para auditoria y toma de decisiones en formato Excel.</p>
                    </div>
                    <div class="reportes-kpis">
                        <div class="reportes-kpi">
                            <small>Formatos</small>
                            <strong>2 Excel</strong>
                        </div>
                        <div class="reportes-kpi">
                            <small>Modo</small>
                            <strong>Offline</strong>
                        </div>
                    </div>
                </div>

                <div class="reportes-save-path">
                    <div class="reportes-save-label"><i class="fas fa-folder-open"></i> Guardado automatico</div>
                    <code>C:\Users\Tu_Usuario\Downloads\</code>
                </div>

                <div class="reportes-grid">
                    <article class="reporte-card reporte-card-inventario">
                        <div class="reporte-card-head">
                            <span class="reporte-pill">OPERATIVO</span>
                            <h4><i class="fas fa-boxes"></i> Inventario Actual</h4>
                        </div>
                        <p>Lista de productos, stock, tipo y precios. Ideal para control diario de tienda.</p>
                        <button class="btn btn-success reporte-btn" id="btn-reporte-inventario">
                            <i class="fas fa-file-excel"></i> Descargar Excel Inventario
                        </button>
                    </article>

                    <article class="reporte-card reporte-card-consolidado">
                        <div class="reporte-card-head">
                            <span class="reporte-pill">GERENCIAL</span>
                            <h4><i class="fas fa-chart-line"></i> Reporte Completo</h4>
                        </div>
                        <p>Consolidado con ventas, cuentas, cortes y movimientos para revision administrativa.</p>
                        <button class="btn btn-info reporte-btn" id="btn-reporte-consolidado">
                            <i class="fas fa-file-excel"></i> Descargar Excel Completo
                        </button>
                    </article>
                </div>

                <div class="reportes-footnote">
                    <i class="fas fa-shield-alt"></i> Los archivos se generan localmente en tu equipo. No se envia informacion a internet.
                </div>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="config">
            <div class="section-header" style="background: linear-gradient(135deg, #37474f, #78909c);">
                <h2><i class="fas fa-cog"></i> Configuración SuperAdmin</h2>
            </div>
            
            <div class="admin-only salud-sistema-panel" style="background: linear-gradient(135deg, #0d47a1, #1565c0); color:#ffffff; padding:25px; border-radius:16px; margin-bottom:30px; box-shadow:var(--sombra);">
                <h3 style="margin-top:0; color:#ffffff;"><i class="fas fa-heartbeat"></i> SALUD DEL SISTEMA</h3>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                    <div style="background:rgba(255,255,255,0.15); padding:15px; border-radius:12px; backdrop-filter:blur(10px); color:#ffffff;">
                        <small style="opacity:0.9; display:block; margin-bottom:5px; color:#ffffff;"><i class="fas fa-database"></i> Tamaño de BD</small>
                        <h3 id="salud-db-size" style="margin:5px 0; font-size:1.5rem; color:#ffffff;">--</h3>
                    </div>
                    <div style="background:rgba(255,255,255,0.15); padding:15px; border-radius:12px; backdrop-filter:blur(10px); color:#ffffff;">
                        <small style="opacity:0.9; display:block; margin-bottom:5px; color:#ffffff;"><i class="fas fa-users"></i> Usuarios Totales</small>
                        <h3 id="salud-usuarios-total" style="margin:5px 0; font-size:1.5rem; color:#ffffff;">0</h3>
                    </div>
                    <div style="background:rgba(255,255,255,0.15); padding:15px; border-radius:12px; backdrop-filter:blur(10px); color:#ffffff;">
                        <small style="opacity:0.9; display:block; margin-bottom:5px; color:#ffffff;"><i class="fas fa-boxes"></i> Productos Activos</small>
                        <h3 id="salud-productos-activos" style="margin:5px 0; font-size:1.5rem; color:#ffffff;">0</h3>
                    </div>
                    <div style="background:rgba(255,255,255,0.15); padding:15px; border-radius:12px; backdrop-filter:blur(10px); color:#ffffff;">
                        <small style="opacity:0.9; display:block; margin-bottom:5px; color:#ffffff;"><i class="fas fa-coins"></i> Total Ventas</small>
                        <h3 id="salud-ventas-total" style="margin:5px 0; font-size:1.3rem; color:#ffffff;">$0.00</h3>
                    </div>
                    <div style="background:rgba(255,255,255,0.15); padding:15px; border-radius:12px; backdrop-filter:blur(10px); color:#ffffff;">
                        <small style="opacity:0.9; display:block; margin-bottom:5px; color:#ffffff;"><i class="fas fa-credit-card"></i> Fiados Pendientes</small>
                        <h3 id="salud-fiados-pendientes" style="margin:5px 0; font-size:1.3rem; color:#ffffff;">$0.00</h3>
                    </div>
                    <div style="background:rgba(255,255,255,0.15); padding:15px; border-radius:12px; backdrop-filter:blur(10px); color:#ffffff;">
                        <small style="opacity:0.9; display:block; margin-bottom:5px; color:#ffffff;"><i class="fas fa-hdd"></i> Espacio Libre</small>
                        <h3 id="salud-disk-free" style="margin:5px 0; font-size:1.5rem; color:#ffffff;">-- GB</h3>
                    </div>
                </div>
                <button class="btn btn-light" id="btn-refresh-salud" style="margin-top:20px; width:100%; background:rgba(255,255,255,0.2); color:#ffffff; border:1px solid rgba(255,255,255,0.3);">
                    <i class="fas fa-rotate"></i> Refrescar Salud del Sistema
                </button>
            </div>
            
            <div class="config-layout">
                <div class="config-card config-card-users">
                    <h3 style="margin-top:0; color:var(--color-primario);"><i class="fas fa-user-shield"></i> Crear Administrador</h3>
                    <form id="form-crear-usuario">
                        <div class="input-group"><label>Usuario:</label><input type="text" id="nuevo-usuario-user" name="username" required placeholder="admin2"></div>
                        <div class="input-group"><label>Contraseña:</label><input type="password" id="nuevo-usuario-pass" name="password" required placeholder="Contraseña segura"></div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Crear Admin</button>
                    </form>
                    <div class="table-wrap">
                        <table id="tabla-usuarios-sistema" style="margin-top:20px;">
                            <thead><tr><th>Usuario</th><th>Rol</th><th>Acciones</th></tr></thead><tbody id="cuerpo-tabla-usuarios"></tbody></table>
                    </div>
                </div>
                <div class="config-card config-card-tools">
                    <h3 style="margin-top:0; color:var(--color-warning);"><i class="fas fa-tools"></i> Mantenimiento</h3>

                    <div class="config-section-block">
                    <button class="btn btn-primario" id="btn-optimizar-bd" style="width:100%; margin-bottom:15px;">
                        <i class="fas fa-rocket"></i> Optimizar Base de Datos
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-10px;">Ejecuta VACUUM para reducir tamaño y mejorar rendimiento</p>

                    <button class="btn btn-success" id="btn-respaldo-db" style="width:100%; margin-bottom:15px;">
                        <i class="fas fa-download"></i> Descargar Respaldo
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-10px;">Descarga copia de seguridad de la base de datos</p>
                    </div>

                    <hr style="margin:25px 0; border-color:var(--color-borde);">

                    <div class="config-section-block">
                    <h4 style="color:#ff9800;"><i class="fas fa-laptop"></i> Optimización de Aplicación</h4>
                    <button class="btn btn-warning" id="btn-diagnostico-rendimiento" style="width:100%; margin-bottom:10px;">
                        <i class="fas fa-stethoscope"></i> Ver Diagnóstico
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-8px; margin-bottom:12px;">Analiza FPS, memoria, CPU del equipo</p>

                    <button class="btn btn-info" id="btn-optimizar-app" style="width:100%; margin-bottom:15px;">
                        <i class="fas fa-tachometer-alt"></i> Optimizar Rendimiento
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-10px;">Desactiva animaciones y reduce carga para equipos lentos</p>
                    </div>

                    <hr style="margin:25px 0; border-color:var(--color-borde);">

                    <div class="config-section-block">
                    <h4 style="color:var(--color-primario);"><i class="fas fa-bell"></i> Notificaciones Inteligentes</h4>
                    <p style="font-size:0.9rem; color:var(--color-texto); opacity:0.8; margin-top:-6px;">Prueba visual y auditiva de notificaciones para validar experiencia del usuario.</p>
                    <div class="config-actions-grid">
                        <button class="btn btn-success" id="btn-noti-demo-success" style="width:100%;"><i class="fas fa-check-circle"></i> Demo Éxito</button>
                        <button class="btn btn-warning" id="btn-noti-demo-warning" style="width:100%;"><i class="fas fa-exclamation-triangle"></i> Demo Alerta</button>
                        <button class="btn btn-danger" id="btn-noti-demo-error" style="width:100%;"><i class="fas fa-times-circle"></i> Demo Error</button>
                        <button class="btn btn-info" id="btn-noti-demo-info" style="width:100%;"><i class="fas fa-info-circle"></i> Demo Info</button>
                    </div>
                    </div>

                    <hr style="margin:25px 0; border-color:var(--color-borde);">

                    <div class="config-section-block">
                    <h4 style="color:#2e7d32;"><i class="fas fa-vial"></i> Pruebas de Sistema (1 Clic)</h4>
                    <p style="font-size:0.9rem; color:var(--color-texto); opacity:0.8; margin-top:-6px;">Ejecuta chequeos de salud, conectividad, integridad y componentes críticos.</p>
                    <button class="btn btn-success" id="btn-ejecutar-pruebas-sistema" style="width:100%; margin-bottom:10px;">
                        <i class="fas fa-play-circle"></i> Ejecutar Pruebas Ahora
                    </button>
                    <div id="pruebas-sistema-resumen" class="config-test-result">Sin ejecutar</div>
                    </div>

                    <hr style="margin:25px 0; border-color:var(--color-borde);">

                    <div class="config-section-block">
                    <h4 style="color:var(--color-primario);"><i class="fas fa-database"></i> Migración de Datos</h4>
                    <div style="background:#fff3e0; border-left: 4px solid #ff9800; padding:12px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;">
                        Importa datos de una base de datos anterior. Se ejecuta una sola vez.
                    </div>
                    <div id="migracion-estado" style="background:#f5f5f5; padding:15px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;">
                        <p><i class="fas fa-spinner fa-spin"></i> Verificando...</p>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr auto; gap:10px; margin-bottom:15px;">
                        <input type="file" id="migracion-archivo" accept=".db,.sqlite,.sqlite3" style="padding:8px; border:1px dashed #ccc; border-radius:6px;">
                        <button class="btn btn-warning" id="btn-cargar-bd-antigua" style="white-space:nowrap;">
                            <i class="fas fa-upload"></i> Cargar
                        </button>
                    </div>
                    <button class="btn btn-success" id="btn-ejecutar-migracion" style="display:none; width:100%; margin-bottom:15px;">
                        <i class="fas fa-play"></i> Ejecutar Migración
                    </button>
                    <div id="migracion-resultado" style="display:none; padding:12px; background:#e8f5e9; border-radius:8px; color:#2e7d32; border-left: 4px solid #4caf50; font-size:0.9rem;"></div>
                    </div>

                    <hr style="margin:25px 0; border-color:var(--color-borde);">

                    <div class="config-section-block">
                    <h4 style="color:var(--color-danger);"><i class="fas fa-exclamation-triangle"></i> Zona Peligrosa</h4>
                    <button class="btn btn-danger" id="btn-resetear-demo" style="width:100%;">
                        <i class="fas fa-skull-crossbones"></i> Resetear Sistema Completo
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-danger); margin-top:5px;">⚠️ Elimina TODOS los datos (ventas, inventario, registros)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="errores">
            <div class="section-header" style="background: var(--color-danger);"><h2>Log</h2></div>
            <div style="display:grid; grid-template-columns: 180px 1fr auto auto; gap:10px; margin:10px 0; align-items:end;">
                <div class="input-group" style="margin:0;">
                    <label style="font-size:0.8rem; font-weight:700;">Nivel</label>
                    <select id="log-filter-level" style="width:100%;">
                        <option value="all">Todos</option>
                        <option value="error">Solo Errores</option>
                        <option value="warning">Solo Warnings</option>
                        <option value="notice">Solo Notices</option>
                    </select>
                </div>
                <div class="input-group" style="margin:0;">
                    <label style="font-size:0.8rem; font-weight:700;">Buscar en mensaje</label>
                    <input type="text" id="log-filter-text" placeholder="ej. session, header, inventario, db..." style="width:100%;">
                </div>
                <button class="btn btn-info" id="btn-refrescar-log" style="width:auto; min-width:140px;">
                    <i class="fas fa-rotate"></i> Refrescar
                </button>
                <button class="btn btn-danger" id="btn-limpiar-log" style="width:auto;">
                    <i class="fas fa-broom"></i> Limpiar Log
                </button>
            </div>
            <div class="table-wrap">
                <table id="tabla-errores">
                    <thead><tr><th>Fecha</th><th>Error</th></tr></thead><tbody id="cuerpo-tabla-errores"></tbody></table>
            </div>
        </div>

        <!-- NUEVA PESTAÑA: CUENTAS (Gestión de Cuentas) -->
        <div class="tab-content" id="cuentas">
            <div class="section-header" style="background: linear-gradient(135deg, var(--color-info), #29b6f6);">
                <h2><i class="fas fa-user-tie"></i> Gestión de Cuentas</h2>
                <p style="margin:5px 0 0 0; opacity:0.9; font-size:0.9rem;">Administra cuentas y saldos de clientes</p>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px;">
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; box-shadow:var(--sombra); border-left: 5px solid var(--color-info);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">CUENTAS ACTIVAS</small>
                    <h3 id="cuentas-total" style="color:var(--color-info); margin:10px 0 0 0; font-size:1.8rem;">0</h3>
                </div>
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; box-shadow:var(--sombra); border-left: 5px solid var(--color-danger);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">SALDO ADEUDADO</small>
                    <h3 id="cuentas-saldo-total" style="color:var(--color-danger); margin:10px 0 0 0; font-size:1.8rem;">$0.00</h3>
                </div>
            </div>

            <div style="background:var(--color-blanco); padding:20px; border-radius:12px; box-shadow:var(--sombra); margin-bottom:20px;">
                <div style="display:grid; grid-template-columns: 1fr auto; gap:15px; margin-bottom:15px;">
                    <div class="input-group" style="margin:0;">
                        <label>Buscar Cuenta:</label>
                        <input type="text" id="cuentas-buscar" placeholder="Nombre o teléfono..." style="width:100%;">
                    </div>
                    <button class="btn btn-success" id="btn-crear-cuenta" style="align-self:flex-end; white-space:nowrap;">
                        <i class="fas fa-plus-circle"></i> Nueva Cuenta
                    </button>
                </div>
            </div>

            <div class="table-wrap">
                <table id="tabla-cuentas" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Grupo/Región</th>
                            <th>Último movimiento</th>
                            <th style="text-align:right;">Saldo</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-cuentas">
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;"><i class="fas fa-inbox"></i> Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- NUEVA PESTAÑA: CORTES (Gestión de Caja) -->
        <div class="tab-content admin-only" id="cortes">
            <div class="section-header" style="background: linear-gradient(135deg, var(--color-success), #81c784);">
                <h2><i class="fas fa-cash-register"></i> Gestión de Cortes de Caja</h2>
                <p style="margin:5px 0 0 0; opacity:0.9; font-size:0.9rem;">Abre y cierra cortes de caja, visualiza movimientos</p>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px;">
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; box-shadow:var(--sombra); border-left: 5px solid var(--color-success);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">CORTE ACTUAL</small>
                    <h3 id="corte-estado" style="color:var(--color-success); margin:10px 0 0 0; font-size:1.4rem;">Cerrado</h3>
                    <small id="corte-hora" style="opacity:0.7;">--</small>
                </div>
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; box-shadow:var(--sombra); border-left: 5px solid var(--color-warning);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">MOVIMIENTO HOY</small>
                    <h3 id="corte-movimiento" style="color:var(--color-warning); margin:10px 0 0 0; font-size:1.8rem;">$0.00</h3>
                </div>
                <div style="background:var(--color-blanco); padding:15px; border-radius:12px; box-shadow:var(--sombra); border-left: 5px solid var(--color-info);">
                    <small style="color:var(--color-texto); opacity:0.7; font-weight:bold;">VENTAS HOY</small>
                    <h3 id="corte-ventas" style="color:var(--color-info); margin:10px 0 0 0; font-size:1.8rem;">0</h3>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin-bottom:25px;">
                <button class="btn btn-success" id="btn-abrir-corte" style="padding:15px;">
                    <i class="fas fa-door-open"></i> Abrir Corte
                </button>
                <button class="btn btn-danger" id="btn-cerrar-corte" style="padding:15px;">
                    <i class="fas fa-door-closed"></i> Cerrar Corte
                </button>
                <button class="btn btn-info" id="btn-ver-corte-actual" style="padding:15px;">
                    <i class="fas fa-eye"></i> Ver Corte Actual
                </button>
            </div>

            <div class="table-wrap">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                    <h3 style="margin:0;">Historial de Cortes</h3>
                    <button class="btn btn-danger" id="btn-eliminar-historial-cortes" style="padding:10px 14px;">
                        <i class="fas fa-trash"></i> Eliminar Historial
                    </button>
                </div>
                <table id="tabla-cortes" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Fecha Apertura</th>
                            <th>Hora Cierre</th>
                            <th style="text-align:right;">Total Movimiento</th>
                            <th style="text-align:center;">Estado</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-cortes">
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#999;"><i class="fas fa-inbox"></i> Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content superadmin-only" id="errores">
            &copy; <?php echo date("Y"); ?> <strong>Agua Viva</strong>. Todos los derechos reservados. v10 Beta
        </footer>
    </main>

    <div id="modal-auth-venta" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:white; padding:30px; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.3); max-width:400px; width:90%;">
            <h2 style="margin-top:0; color:#d32f2f;"><i class="fas fa-lock"></i> Confirmación de Seguridad</h2>
            <p style="color:#666; margin:15px 0;">Para eliminar una venta, debes ingresar las credenciales de administrador.</p>
            
            <div class="input-group" style="margin:15px 0;">
                <label style="font-weight:bold; color:#333;">Usuario Admin:</label>
                <input type="text" id="auth-venta-user" placeholder="Usuario de administrador" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
            </div>
            
            <div class="input-group" style="margin:15px 0;">
                <label style="font-weight:bold; color:#333;">Contraseña:</label>
                <input type="password" id="auth-venta-pass" placeholder="Contraseña" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
            </div>
            
            <div style="display:flex; gap:10px; margin-top:25px;">
                <button id="btn-auth-venta-cancel" class="btn btn-secondary" style="flex:1; background:#999;">Cancelar</button>
                <button id="btn-auth-venta-confirm" class="btn btn-danger" style="flex:1; background:#d32f2f;">Eliminar Venta</button>
            </div>
        </div>
    </div>

    <script src="assets/vendor/sweetalert2/sweetalert2.min.js"></script>
    <style>
        #btn-help-flotante {
            position: fixed;
            bottom: 30px;
            right: 30px;
            min-width: 142px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #0d47a1, #1976d2);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 800;
            box-shadow: 0 10px 26px rgba(13, 71, 161, 0.38);
            z-index: 9999;
            font-family: inherit;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.2px;
        }

        #btn-help-flotante .help-fab-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        #btn-help-flotante .help-fab-text {
            font-size: 0.92rem;
            text-transform: uppercase;
        }
        
        #btn-help-flotante:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 16px 30px rgba(13, 71, 161, 0.45);
        }
        
        #btn-help-flotante:active {
            transform: scale(0.95);
        }
        
        /* MODAL DE AYUDA - SOLO VISIBLE CUANDO SE ABRE */
        #help-modal {
            display: none !important;
            position: fixed;
            z-index: 10001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        #help-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        
        .help-modal-content {
            background-color: var(--color-fondo, white);
            padding: 26px;
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.35);
            max-width: 980px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            color: var(--color-texto, #333);
            animation: slideUp 0.3s ease;
            margin: auto;
        }
        
        .help-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--color-borde, #dfe5ec);
            padding-bottom: 14px;
        }
        
        .help-modal-header h2 {
            color: var(--color-primario, #3498db);
            margin: 0;
            font-size: 1.35rem;
        }

        .help-modal-subtitle {
            margin: 6px 0 0 0;
            color: var(--color-texto, #333);
            opacity: 0.85;
            font-size: 0.95rem;
        }
        
        .help-modal-close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--color-primario, #3498db);
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .help-modal-close-btn:hover {
            background: var(--color-input-bg, #f5f5f5);
        }
        
        .help-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .help-tab {
            padding: 10px 15px;
            border: 2px solid var(--color-primario, #3498db);
            background: rgba(255,255,255,0.9);
            color: var(--color-primario, #3498db);
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 13px;
        }
        
        .help-tab:hover {
            background: var(--color-primario, #3498db);
            color: white;
        }
        
        .help-tab.active {
            background: var(--color-primario, #3498db);
            color: white;
        }
        
        .help-content-section {
            display: none;
        }
        
        .help-content-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .help-module-item {
            background: var(--color-input-bg, #f9f9f9);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-primario, #3498db);
            border: 1px solid var(--color-borde, #dfe5ec);
        }
        
        .help-module-item h3 {
            color: var(--color-primario, #3498db);
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .help-module-item p {
            margin: 8px 0;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .help-steps {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        .help-step {
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
            font-size: 13px;
        }
        
        .help-step:before {
            content: attr(data-step);
            position: absolute;
            left: 0;
            top: 0;
            background: var(--color-primario, #3498db);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .help-tip {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.12), rgba(41, 182, 246, 0.08));
            border-left: 3px solid var(--color-primario, #3498db);
            padding: 12px;
            margin-top: 10px;
            border-radius: 8px;
            font-size: 12.5px;
        }

        .help-quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
            margin: 14px 0 2px 0;
        }

        .help-quick-btn {
            border: 1px solid var(--color-borde, #dfe5ec);
            background: var(--color-blanco, #fff);
            color: var(--color-texto, #333);
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .help-quick-btn:hover {
            border-color: var(--color-primario, #3498db);
            color: var(--color-primario, #3498db);
            transform: translateY(-2px);
        }
        
        .help-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
        }
        
        .help-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .help-btn-primary {
            background: var(--color-primario, #3498db);
            color: white;
        }
        
        .help-btn-primary:hover {
            opacity: 0.9;
        }
        
        .help-btn-secondary {
            background: var(--color-input-bg, #f5f5f5);
            color: var(--color-texto, #333);
            border: 1px solid var(--color-primario, #3498db);
        }
        
        .help-btn-secondary:hover {
            background: var(--color-primario, #3498db);
            color: white;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        [data-theme="dark"] #help-modal {
            background-color: rgba(0,0,0,0.7);
        }
        
        [data-theme="dark"] .help-modal-content {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }
        
        [data-theme="dark"] .help-tab {
            background: #2c3e50;
            border-color: var(--color-primario, #3498db);
            color: var(--color-primario, #3498db);
        }
        
        [data-theme="dark"] .help-module-item {
            background: #2c3e50;
        }

        [data-theme="dark"] #btn-help-flotante {
            background: linear-gradient(135deg, #1565c0, #1e88e5);
            box-shadow: 0 12px 28px rgba(30, 136, 229, 0.35);
        }

        [data-theme="dark"] .help-modal-subtitle {
            color: #cfd8dc;
        }

        [data-theme="dark"] .help-quick-btn {
            background: #263238;
            border-color: #455a64;
            color: #e0e0e0;
        }

        [data-theme="dark"] .help-quick-btn:hover {
            color: #81d4fa;
            border-color: #4fc3f7;
        }

        @media (max-width: 700px) {
            #btn-help-flotante {
                min-width: 56px;
                width: 56px;
                padding: 0;
                border-radius: 14px;
            }

            #btn-help-flotante .help-fab-text {
                display: none;
            }
        }
    </style>
    
    <div id="help-modal">
        <div class="help-modal-content">
            <div class="help-modal-header">
                <div>
                    <h2>📚 Centro de Ayuda TG Gestión</h2>
                    <p class="help-modal-subtitle"><?php echo $es_admin_help ? 'Guia practica para administradores: revisa todos los modulos y controles avanzados.' : 'Guia practica para vendedores: usa solo lo necesario para operar rapido y seguro.'; ?></p>
                </div>
                <button class="help-modal-close-btn" onclick="cerrarAyuda()" title="Cerrar">×</button>
            </div>
            
            <div class="help-tabs">
                <button class="help-tab active" onclick="mostrarModulo('help-inicio', event)">🚀 Inicio rapido</button>
                <button class="help-tab" onclick="mostrarModulo('help-ventas', event)">🛒 Ventas</button>
                <button class="help-tab" onclick="mostrarModulo('help-inventario', event)">📦 Inventario</button>
                <button class="help-tab" onclick="mostrarModulo('help-registros', event)">📝 Registros</button>
                <button class="help-tab" onclick="mostrarModulo('help-cuentas', event)">👤 Cuentas</button>
                <?php if ($es_admin_help): ?>
                <button class="help-tab" onclick="mostrarModulo('help-cortes', event)">🧾 Cortes</button>
                <button class="help-tab" onclick="mostrarModulo('help-reportes', event)">📊 Reportes</button>
                <button class="help-tab" onclick="mostrarModulo('help-septimas', event)">💸 Séptimas</button>
                <button class="help-tab" onclick="mostrarModulo('help-config', event)">⚙️ Config</button>
                <?php endif; ?>
            </div>

            <div id="help-inicio" class="help-content-section active">
                <div class="help-module-item">
                    <h3>🚀 Primeros 3 pasos para empezar</h3>
                    <p><strong>Objetivo:</strong> que cualquier usuario nuevo pueda operar la tienda en menos de 3 minutos.</p>
                    
                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Registra productos:</strong> entra a Inventario, crea productos o preparados y valida stock.</div>
                        <div class="help-step" data-step="2"><strong>Haz una venta de prueba:</strong> en Ventas busca producto, agrega al ticket y cobra.</div>
                        <div class="help-step" data-step="3"><strong>Revisa resultados:</strong> consulta Registros/Cortes y genera Reporte Completo en Excel.</div>
                    </div>

                    <div class="help-tip">💡 Si tienes poco tiempo, usa los accesos rapidos de abajo para ir directo al modulo que necesitas.</div>
                    <div class="help-quick-actions">
                        <button class="help-quick-btn" onclick="irATab('ventas')">Ir a Ventas</button>
                        <button class="help-quick-btn" onclick="irATab('inventario')">Ir a Inventario</button>
                        <button class="help-quick-btn" onclick="irATab('registros')">Ir a Registros</button>
                        <button class="help-quick-btn" onclick="irATab('cuentas')">Ir a Cuentas</button>
                        <?php if ($es_admin_help): ?>
                        <button class="help-quick-btn" onclick="irATab('cortes')">Ir a Cortes</button>
                        <button class="help-quick-btn" onclick="irATab('reportes')">Ir a Reportes</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div id="help-ventas" class="help-content-section">
                <div class="help-module-item">
                    <h3>🛒 Módulo de Ventas</h3>
                    <p><strong>Descripción:</strong> Registra y procesa ventas de forma rápida y eficiente. Este módulo te permite buscar productos, seleccionar cantidades y procesar pagos.</p>
                    
                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Buscar Producto:</strong> Escribe el nombre o código en el campo "Buscar Producto". El sistema mostrará opciones coincidentes.</div>
                        <div class="help-step" data-step="2"><strong>Seleccionar Producto:</strong> Haz clic en el producto deseado de la lista. Se mostrarán automáticamente el precio y disponibilidad.</div>
                        <div class="help-step" data-step="3"><strong>Cantidad:</strong> Especifica cuántas unidades deseas vender. Verifica que no supere el stock disponible.</div>
                        <div class="help-step" data-step="4"><strong>Forma de Pago:</strong> Selecciona: Efectivo, Débito, Crédito o Transferencia. El sistema calculará automáticamente el total.</div>
                        <div class="help-step" data-step="5"><strong>Procesar Venta:</strong> Haz clic en "Agregar al Ticket" y luego "Cobrar" para finalizar. Se generará un comprobante automáticamente.</div>
                    </div>
                    
                    <div class="help-tip">💡 Consejo: Usa las teclas + y - para ajustar cantidades rápidamente</div>
                </div>
            </div>
            
            <div id="help-inventario" class="help-content-section">
                <div class="help-module-item">
                    <h3>📦 Módulo de Inventario</h3>
                    <p><strong>Descripción:</strong> Gestiona el stock de productos de tu negocio. Visualiza disponibilidad, crea nuevos productos y edita cantidades de inventario.</p>
                    
                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Ver Stock Actual:</strong> Visualiza todos los productos y su disponibilidad actual. Se ordenan por relevancia.</div>
                        <div class="help-step" data-step="2"><strong>Crear Nuevo Producto:</strong> Haz clic en "Nuevo Producto" e ingresa: nombre, código, categoría, precio y cantidad inicial.</div>
                        <div class="help-step" data-step="3"><strong>Editar Cantidades:</strong> Haz clic en "Editar" en un producto para actualizar el stock cuando recibas nueva mercadería.</div>
                        <div class="help-step" data-step="4"><strong>Alertas de Stock Bajo:</strong> El sistema notifica automáticamente cuando el stock es bajo (configurable en ajustes).</div>
                        <div class="help-step" data-step="5"><strong>Filtrar y Buscar:</strong> Usa los filtros por categoría, proveedor o estado para encontrar productos rápidamente.</div>
                    </div>
                    
                    <div class="help-tip">💡 Consejo: Mantén actualizado el stock para evitar sobrevender</div>
                </div>
            </div>
            
            <div id="help-registros" class="help-content-section">
                <div class="help-module-item">
                    <h3>📝 Módulo de Registros</h3>
                    <p><strong>Descripción:</strong> Registra todos los ingresos y egresos de dinero de tu negocio. Categoriza movimientos y genera cortes diarios.</p>
                    
                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Registrar Movimiento:</strong> Crea un nuevo ingreso o egreso de dinero. Especifica si es entrada o salida.</div>
                        <div class="help-step" data-step="2"><strong>Seleccionar Categoría:</strong> Elige el tipo: Venta, Pago, Devolución, Gasto, etc. Las categorías te ayudan a organizar.</div>
                        <div class="help-step" data-step="3"><strong>Concepto Detallado:</strong> Describe brevemente el motivo del movimiento. Esto facilita búsquedas futuras.</div>
                        <div class="help-step" data-step="4"><strong>Hacer Corte Diario:</strong> Al final del día, genera un corte de caja. El sistema reconcilia ingresos y egresos.</div>
                        <div class="help-step" data-step="5"><strong>Editar si es Necesario:</strong> Puedes modificar registros (requiere autorización de admin).</div>
                    </div>
                    
                    <div class="help-tip">💡 Consejo: Registra los movimientos el mismo día para mayor precisión</div>
                </div>
            </div>

            <div id="help-cuentas" class="help-content-section">
                <div class="help-module-item">
                    <h3>👤 Módulo de Cuentas</h3>
                    <p><strong>Descripción:</strong> Administra clientes con saldo pendiente, registra abonos y consulta historial de movimientos de cada cuenta.</p>

                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Nueva cuenta:</strong> crea la cuenta con nombre y datos del cliente.</div>
                        <div class="help-step" data-step="2"><strong>Cargos:</strong> desde Ventas puedes generar compras a cuenta.</div>
                        <div class="help-step" data-step="3"><strong>Abonos:</strong> registra pagos parciales o totales del cliente.</div>
                        <div class="help-step" data-step="4"><strong>Historial:</strong> revisa movimientos y estado (pendiente/pagado).</div>
                        <div class="help-step" data-step="5"><strong>Control:</strong> usa buscador y saldo total para seguimiento diario.</div>
                    </div>

                    <div class="help-tip">💡 Consejo: registra siempre el abono el mismo día para mantener saldo exacto por cliente.</div>
                </div>
            </div>

            <?php if ($es_admin_help): ?>
            <div id="help-cortes" class="help-content-section">
                <div class="help-module-item">
                    <h3>🧾 Módulo de Cortes</h3>
                    <p><strong>Descripción:</strong> Abre, monitorea y cierra cortes de caja; compara saldo esperado vs contado y guarda historial de cierres.</p>

                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Abrir corte:</strong> inicia jornada con saldo inicial en caja.</div>
                        <div class="help-step" data-step="2"><strong>Monitorear:</strong> valida movimiento y ventas acumuladas durante el dia.</div>
                        <div class="help-step" data-step="3"><strong>Cerrar corte:</strong> captura saldo final contado para calcular diferencia.</div>
                        <div class="help-step" data-step="4"><strong>Detalle:</strong> revisa ventas/egresos ligados al corte.</div>
                        <div class="help-step" data-step="5"><strong>Historial:</strong> limpia historial cuando sea necesario (solo admin).</div>
                    </div>

                    <div class="help-tip">💡 Consejo: cierra corte al final de turno para detectar diferencias inmediatamente.</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($es_admin_help): ?>
            <div id="help-reportes" class="help-content-section">
                <div class="help-module-item">
                    <h3>📊 Módulo de Reportes</h3>
                    <p><strong>Descripción:</strong> Genera archivos Excel para respaldo, auditoria y analisis operativo del negocio.</p>

                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Excel Inventario:</strong> exporta productos, stock, tipo y precios actuales.</div>
                        <div class="help-step" data-step="2"><strong>Excel Completo:</strong> exporta consolidado de operaciones del sistema.</div>
                        <div class="help-step" data-step="3"><strong>Ruta de salida:</strong> los archivos se guardan en Descargas del equipo.</div>
                        <div class="help-step" data-step="4"><strong>Uso recomendado:</strong> genera cierre diario/semanal para respaldo administrativo.</div>
                        <div class="help-step" data-step="5"><strong>Validacion:</strong> confirma fecha de generacion y contenido antes de compartir.</div>
                    </div>

                    <div class="help-tip">💡 Consejo: conserva una carpeta por mes para ordenar reportes y facilitar revisiones.</div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($es_admin_help): ?>
            <div id="help-septimas" class="help-content-section">
                <div class="help-module-item">
                    <h3>💸 Módulo de Séptimas</h3>
                    <p><strong>Descripción:</strong> Gestiona los gastos de operación como arriendo, servicios, salarios y otros gastos recurrentes.</p>
                    
                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Registrar Gasto:</strong> Crea un nuevo gasto o pago. Ingresa monto, fecha y detalles.</div>
                        <div class="help-step" data-step="2"><strong>Seleccionar Tipo de Servicio:</strong> Categoriza como: Arriendo, Servicios (agua, luz, internet), Salarios, Impuestos, etc.</div>
                        <div class="help-step" data-step="3"><strong>Marcar como Pagada:</strong> Cuando completes el pago, marca el gasto como "Pagado" para llevar control.</div>
                        <div class="help-step" data-step="4"><strong>Ver Historial:</strong> Consulta el historial de gastos y genera reportes de séptimas por período.</div>
                    </div>
                    
                    <div class="help-tip">💡 Consejo: Planifica gastos recurrentes con anticipación</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($es_admin_help): ?>
            <div id="help-config" class="help-content-section">
                <div class="help-module-item">
                    <h3>⚙️ Módulo de Configuración</h3>
                    <p><strong>Descripción:</strong> Ajusta parámetros avanzados, personalización y mantenimiento general del sistema.</p>

                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Usuarios y permisos:</strong> administra accesos y roles.</div>
                        <div class="help-step" data-step="2"><strong>Pruebas del sistema:</strong> ejecuta diagnósticos y verificaciones rápidas.</div>
                        <div class="help-step" data-step="3"><strong>Mantenimiento:</strong> optimiza, valida y respalda cuando sea necesario.</div>
                        <div class="help-step" data-step="4"><strong>Zona de riesgo:</strong> usa con cuidado, solo cuando lo necesites.</div>
                    </div>

                    <div class="help-tip">💡 Consejo: modifica configuración solo si entiendes el impacto en toda la operación.</div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="help-footer">
                <button class="help-btn help-btn-secondary" onclick="cerrarAyuda()">Cerrar</button>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        "use strict";

        function activarSeccionAyuda(modulo) {
            document.querySelectorAll('.help-content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.help-tab').forEach(t => t.classList.remove('active'));

            const seccion = document.getElementById(modulo);
            if (seccion) seccion.classList.add('active');

            const tab = document.querySelector('.help-tab[onclick*="' + modulo + '"]');
            if (tab) tab.classList.add('active');
        }
        
        function crearBotonAyuda() {
            const btn = document.createElement('button');
            btn.id = 'btn-help-flotante';
            btn.innerHTML = '<span class="help-fab-icon"><i class="fas fa-life-ring"></i></span><span class="help-fab-text">Ayuda</span>';
            btn.title = 'Centro de Ayuda';
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                abrirAyuda();
            };
            document.body.appendChild(btn);
        }
        
        window.abrirAyuda = function() {
            const modal = document.getElementById('help-modal');
            if (modal) {
                activarSeccionAyuda('help-inicio');
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        };
        
        window.cerrarAyuda = function() {
            const modal = document.getElementById('help-modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        };
        
        window.mostrarModulo = function(modulo, event) {
            if (event) {
                event.stopPropagation();
            }
            
            document.querySelectorAll('.help-content-section').forEach(s => {
                s.classList.remove('active');
            });
            
            document.querySelectorAll('.help-tab').forEach(t => {
                t.classList.remove('active');
            });
            
            const seccion = document.getElementById(modulo);
            if (seccion) {
                seccion.classList.add('active');
            }
            if (event && event.target) {
                event.target.classList.add('active');
            }
        };

        window.irATab = function(tabId) {
            const target = document.querySelector('.nav-item[data-tab="' + tabId + '"]');
            if (target) {
                target.click();
                cerrarAyuda();
            }
        };
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarAyuda();
            }
        });
        
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('help-modal');
            if (e.target === modal) {
                cerrarAyuda();
            }
        });
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', crearBotonAyuda);
        } else {
            crearBotonAyuda();
        }
    })();
    </script>
    
    <script src="assets/js/download-handler.js"></script>
    
    <!-- Contenedor de notificaciones -->
    <div id="toast-container" style="position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px; max-width:400px;"></div>
    
    <!-- Audio para notificaciones -->
    <audio id="notif-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRiYAAABXQVZFZm10IBAAAAABAAEAQB8AAAB9AAACABAAZGF0YQIAAAA=" type="audio/wav">
    </audio>
    
    <script src="assets/js/app.js" type="module"></script>
</body>
</html>