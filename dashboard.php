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
                    
                    <h3 style="margin-top: 2rem;">Fiados Pendientes</h3>
                    <div class="table-wrap">
                        <table id="tabla-deudores">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px;">DEUDOR</th>
                                    <th style="width: 120px; text-align: right;">MONTO</th>
                                    <th style="width: 150px; text-align: center;">ACCIÓN</th>
                                </tr>
                            </thead>
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
                    
                    <div class="input-group" id="grupo-fiado-group" style="display:none;">
                        <label>Grupo:</label>
                        <select id="grupo-fiado">
                            <option value="">-- Selecciona grupo --</option>
                            <optgroup label="Región 4">
                                <option value="AMALUCAN">AMALUCAN</option>
                                <option value="APIZACO">APIZACO</option>
                                <option value="BUENAVISTA">BUENAVISTA</option>
                                <option value="GUADALUPE HIDALGO">GUADALUPE HIDALGO</option>
                                <option value="LOMAS DEL SUR">LOMAS DEL SUR</option>
                                <option value="SAN BALTAZAR">SAN BALTAZAR</option>
                                <option value="SAN FELIPE">SAN FELIPE</option>
                                <option value="TLAXCALA">TLAXCALA</option>
                                <option value="CHOLULA">CHOLULA</option>
                                <option value="ZACATELCO">ZACATELCO</option>
                                <option value="SANTA ANA">SANTA ANA</option>
                                <option value="AMOZOC">AMOZOC</option>
                                <option value="HUAMANTLA">HUAMANTLA</option>
                                <option value="CONTLA">CONTLA</option>
                            </optgroup>
                            <optgroup label="Otras Regiones">
                                <option value="Region 1 CDMX">Region 1 CDMX</option>
                                <option value="Region 2 EDO MEX">Region 2 EDO MEX</option>
                                <option value="Region 3">Region 3</option>
                                <option value="Region 5">Region 5</option>
                                <option value="Region 6">Region 6</option>
                                <option value="Region 7">Region 7</option>
                                <option value="Region 8">Region 8</option>
                            </optgroup>
                        </select>
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
            
            <div class="admin-only" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:30px;">
                <div class="stat-card stat-card-purple">
                    <div style="position:relative; z-index:2; display:flex; align-items:center; gap:18px;">
                        <div style="font-size:2.8rem; min-width:70px; text-align:center; opacity:0.95;">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div style="flex:1;">
                            <small style="font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:0.7rem; opacity:0.88;text-shadow:1px 1px 2px rgba(0,0,0,0.2);">👑 Top Producto</small>
                            <h2 id="top-producto" style="font-size:1.6rem; margin:6px 0; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-shadow:2px 2px 4px rgba(0,0,0,0.3);">Cargando...</h2>
                            <small id="top-producto-ventas" style="opacity:0.85; font-size:0.9rem; display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-chart-bar"></i>0 ventas
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card stat-card-pink" style="cursor:pointer; position:relative; overflow:hidden;" onclick="inventario.mostrarStockBajo(); return false;">
                    <div style="position:absolute; top:-30%; right:-30%; width:200px; height:200px; background:rgba(255,255,255,0.08); border-radius:50%; z-index:1;"></div>
                    <div style="position:relative; z-index:2; display:flex; align-items:center; gap:18px;">
                        <div style="font-size:3.2rem; min-width:80px; text-align:center; animation: pulse 2.5s infinite; opacity:0.98; text-shadow:2px 2px 6px rgba(0,0,0,0.3);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div style="flex:1; overflow:hidden; min-width:0;">
                            <small style="font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:0.7rem; opacity:0.92; text-shadow:1px 1px 2px rgba(0,0,0,0.2);">⚠️ Stock Crítico</small>
                            <h2 id="stock-bajo-count" style="font-size:2.8rem; margin:4px 0 8px 0; font-weight:900; text-shadow:3px 3px 8px rgba(0,0,0,0.3); line-height:1; word-break:break-word;">0</h2>
                            <small style="opacity:0.88; font-size:0.9rem; display:block; font-weight:600; word-break:break-word;">
                                <i class="fas fa-box" style="margin-right:6px;"></i>Productos bajo stock
                            </small>
                            <small style="opacity:0.75; font-size:0.8rem; display:block; margin-top:6px;">
                                <i class="fas fa-mouse" style="margin-right:6px; font-weight:700;"></i><em>Haz clic para ver</em>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card stat-card-blue">
                    <div style="position:relative; z-index:2; display:flex; align-items:center; gap:18px;">
                        <div style="font-size:2.8rem; min-width:70px; text-align:center; opacity:0.95;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div style="flex:1;">
                            <small style="font-weight:700; text-transform:uppercase; letter-spacing:1px; font-size:0.7rem; opacity:0.88; text-shadow:1px 1px 2px rgba(0,0,0,0.2);">💰 Ventas Mensuales</small>
                            <h2 id="ventas-mes-total" style="font-size:2rem; margin:6px 0; font-weight:800; text-shadow:2px 2px 4px rgba(0,0,0,0.3);">$0.00</h2>
                            <small id="ventas-mes-comparativa" style="opacity:0.85; font-size:0.9rem; display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-percentage"></i>vs mes anterior
                            </small>
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
                            <div class="input-group"><label>Código:</label><input type="text" id="producto-codigo" name="codigo"></div>
                            <div class="input-group"><label>Precio:</label><input type="number" id="producto-precio" name="precio" step="0.50" required></div>
                            <div class="input-group"><label>Stock:</label><input type="number" id="producto-stock" name="stock" required></div>
                            <div class="input-group" style="grid-column:span 2"><label>Foto URL:</label><input type="text" id="producto-foto" name="foto"></div>
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
                <div style="background:var(--color-texto); padding:15px; border-radius:12px; text-align:center; box-shadow:var(--sombra); color:white; min-height:90px; display:flex; flex-direction:column; justify-content:center;">
                    <small style="opacity:1; font-weight:bold; color:#ffffff; display:block; margin-bottom:8px;">TOTAL EN CAJÓN</small>
                    <h3 id="corte-total" style="margin:0; font-size:1.8rem; color:#4caf50;">$0.00</h3>
                </div>
            </div>

            <button class="btn btn-primario admin-only" id="btn-agregar-registro" style="margin-bottom:20px; width:auto;">
                <i class="fas fa-plus"></i> Agregar Movimiento
            </button>

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
            
            <div style="background:var(--color-blanco); padding:40px; border-radius:12px; text-align:center; box-shadow:var(--sombra);">
                <h3 style="color:var(--color-texto);">Descargar Reportes</h3>
                
                <div style="background:#e3f2fd; border-left:4px solid #2196f3; padding:15px; margin:20px 0; border-radius:8px; text-align:left;">
                    <p style="margin:0; color:#1565c0; font-size:0.9em;">
                        <strong>📁 Los archivos se guardan automáticamente en:</strong><br>
                        <code style="background:white; padding:5px 10px; border-radius:4px; display:inline-block; margin-top:5px;">C:\Users\Tu_Usuario\Downloads\</code>
                    </p>
                </div>
                
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-top:30px;">
                    <button class="btn btn-success" id="btn-reporte-inventario" style="width:100%;">
                        <i class="fas fa-file-excel"></i> Excel - Inventario
                    </button>
                    <button class="btn btn-primario" id="btn-reporte-consolidado" style="width:100%;">
                        <i class="fas fa-file-excel"></i> Excel - Completo
                    </button>
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
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                <div style="background:var(--color-blanco); padding:25px; border-radius:16px; box-shadow:var(--sombra);">
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
                    
                    <h4 style="color:#ff9800;"><i class="fas fa-laptop"></i> Optimización de Aplicación</h4>
                    <button class="btn btn-warning" id="btn-diagnostico-rendimiento" style="width:100%; margin-bottom:10px;">
                        <i class="fas fa-stethoscope"></i> Ver Diagnóstico
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-8px; margin-bottom:12px;">Analiza FPS, memoria, CPU del equipo</p>
                    
                    <button class="btn btn-info" id="btn-optimizar-app" style="width:100%; margin-bottom:15px;">
                        <i class="fas fa-tachometer-alt"></i> Optimizar Rendimiento
                    </button>
                    <p style="font-size:0.85rem; color:var(--color-texto); opacity:0.7; margin-top:-10px;">Desactiva animaciones y reduce carga para equipos lentos</p>
                    
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
            <div style="display:flex; justify-content:flex-end; margin:10px 0;">
                <button class="btn btn-danger" id="btn-limpiar-log" style="width:auto;">
                    <i class="fas fa-broom"></i> Limpiar Log
                </button>
            </div>
            <div class="table-wrap">
                <table id="tabla-errores">
                    <thead><tr><th>Fecha</th><th>Error</th></tr></thead><tbody id="cuerpo-tabla-errores"></tbody></table>
            </div>
        </div>

        <footer class="main-footer">
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
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--color-primario, #3498db);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 28px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        #btn-help-flotante:hover {
            transform: scale(1.1) translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
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
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 700px;
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
            margin-bottom: 25px;
            border-bottom: 2px solid var(--color-primario, #3498db);
            padding-bottom: 15px;
        }
        
        .help-modal-header h2 {
            color: var(--color-primario, #3498db);
            margin: 0;
            font-size: 24px;
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
            background: white;
            color: var(--color-primario, #3498db);
            border-radius: 6px;
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
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-primario, #3498db);
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
            background: rgba(52, 152, 219, 0.1);
            border-left: 3px solid var(--color-primario, #3498db);
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 12px;
            font-style: italic;
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
    </style>
    
    <div id="help-modal">
        <div class="help-modal-content">
            <div class="help-modal-header">
                <h2>📚 Centro de Ayuda TG Gestión</h2>
                <button class="help-modal-close-btn" onclick="cerrarAyuda()" title="Cerrar">×</button>
            </div>
            
            <div class="help-tabs">
                <button class="help-tab active" onclick="mostrarModulo('help-ventas', event)">🛒 Ventas</button>
                <button class="help-tab" onclick="mostrarModulo('help-inventario', event)">📦 Inventario</button>
                <button class="help-tab" onclick="mostrarModulo('help-registros', event)">📝 Registros</button>
                <button class="help-tab" onclick="mostrarModulo('help-septimas', event)">💸 Séptimas</button>
                <button class="help-tab" onclick="mostrarModulo('help-config', event)">⚙️ Config</button>
            </div>
            
            <div id="help-ventas" class="help-content-section active">
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
            
            <div id="help-config" class="help-content-section">
                <div class="help-module-item">
                    <h3>⚙️ Configuración</h3>
                    <p><strong>Descripción:</strong> Personaliza la aplicación a tu gusto. Cambia temas, colores, gestiona usuarios y consulta estadísticas.</p>
                    
                    <div class="help-steps">
                        <div class="help-step" data-step="1"><strong>Cambiar Tema:</strong> Alterna entre tema claro y oscuro. Presiona Ctrl+D para cambiar rápidamente.</div>
                        <div class="help-step" data-step="2"><strong>Seleccionar Color Primario:</strong> Personaliza el color principal de la aplicación según tus preferencias.</div>
                        <div class="help-step" data-step="3"><strong>Gestionar Usuarios:</strong> Crea, edita o elimina cuentas de usuario (requiere permisos de administrador).</div>
                        <div class="help-step" data-step="4"><strong>Ver Estadísticas:</strong> Consulta reportes diarios, semanales, mensuales y anuales del negocio.</div>
                    </div>
                    
                    <div class="help-tip">💡 Consejo: El tema oscuro es perfecto para trabajar de noche</div>
                </div>
            </div>
            
            <div class="help-footer">
                <button class="help-btn help-btn-secondary" onclick="cerrarAyuda()">Cerrar</button>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        "use strict";
        
        function crearBotonAyuda() {
            const btn = document.createElement('button');
            btn.id = 'btn-help-flotante';
            btn.innerHTML = '?';
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
    
    <script src="assets/js/app.js" type="module"></script>
</body>
</html>