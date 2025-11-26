<?php
// api/api_admin.php
// VERSIÓN v5.0 Offline Edition

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';
header('Content-Type: application/json');

// Validación básica de sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';
$esSuperAdmin = ($_SESSION['role'] === 'superadmin');
$esAdmin = ($_SESSION['role'] === 'admin' || $esSuperAdmin);

try {
    // --- 1. OBTENER COLOR (Acceso libre para pintar la interfaz) ---
    if ($accion === 'get_color') {
        $stmt = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'color_tema'");
        $res = $stmt->fetch();
        $color = $res ? $res['valor'] : '#0d47a1'; 
        echo json_encode(['success' => true, 'color' => $color]);
        exit();
    }

    // --- 2. VER ERRORES (Solo Superadmin) ---
    if ($accion === 'ver_errores') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        $stmt = $conexion->query("SELECT * FROM log_errores ORDER BY id DESC LIMIT 50");
        echo json_encode(['success' => true, 'errores' => $stmt->fetchAll()]);
        exit();
    }

    // --- 3. GUARDAR COLOR (Solo Superadmin) ---
    if ($accion === 'save_color') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Solo Superadmin cambia la configuración.']);
            exit();
        }
        include_once 'csrf.php';
        require_csrf_or_die();

        $nuevoColor = $_POST['color'];
        $stmt = $conexion->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES ('color_tema', ?)");
        $stmt->execute([$nuevoColor]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // === v5.0: NUEVAS FUNCIONALIDADES ===
    
    // --- 4. TOP PRODUCTO DEL MES (Admin+) ---
    if ($accion === 'top_producto_mes') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        $mesActual = date('Y-m');
        $stmt = $conexion->prepare("
            SELECT p.nombre, COUNT(vi.id) as ventas
            FROM venta_items vi
            INNER JOIN ventas v ON vi.venta_id = v.id
            INNER JOIN productos p ON vi.producto_id = p.id
            WHERE strftime('%Y-%m', v.fecha) = ?
            GROUP BY vi.producto_id
            ORDER BY ventas DESC
            LIMIT 1
        ");
        $stmt->execute([$mesActual]);
        $producto = $stmt->fetch();
        
        echo json_encode([
            'success' => true, 
            'producto' => $producto ? $producto : ['nombre' => 'Sin datos', 'ventas' => 0]
        ]);
        exit();
    }

    // --- 5. VENTAS DEL MES (Admin+) ---
    if ($accion === 'ventas_mes') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        $mesActual = date('Y-m');
        $mesAnterior = date('Y-m', strtotime('-1 month'));
        
        // Mes actual
        $stmt = $conexion->prepare("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE strftime('%Y-%m', fecha) = ?");
        $stmt->execute([$mesActual]);
        $totalActual = $stmt->fetch()['total'];
        
        // Mes anterior
        $stmt->execute([$mesAnterior]);
        $totalAnterior = $stmt->fetch()['total'];
        
        // Comparativa
        $comparativa = 0;
        if ($totalAnterior > 0) {
            $comparativa = round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 1);
        }
        
        echo json_encode([
            'success' => true,
            'total' => $totalActual,
            'comparativa' => $comparativa
        ]);
        exit();
    }

    // --- 6. ESTADÍSTICAS GLOBALES (SuperAdmin) ---
    if ($accion === 'stats_globales') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        // Tamaño de BD
        $dbPath = getenv('APPDATA') . '/TG_Gestion/database.sqlite';
        $dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024 / 1024, 2) . ' MB' : '--';
        
        // Ventas totales
        $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas");
        $ventasTotal = $stmt->fetch()['total'];
        
        // Total usuarios
        $stmt = $conexion->query("SELECT COUNT(*) as count FROM usuarios");
        $usuariosTotal = $stmt->fetch()['count'];
        
        // Última venta
        $stmt = $conexion->query("SELECT fecha FROM ventas ORDER BY id DESC LIMIT 1");
        $ultimaVenta = $stmt->fetch();
        $ultimaVentaStr = $ultimaVenta ? date('d/m/Y H:i', strtotime($ultimaVenta['fecha'])) : 'Nunca';
        
        echo json_encode([
            'success' => true,
            'db_size' => $dbSize,
            'ventas_total' => $ventasTotal,
            'usuarios_total' => $usuariosTotal,
            'ultima_venta' => $ultimaVentaStr
        ]);
        exit();
    }

    // --- 7. OPTIMIZAR BD (SuperAdmin) ---
    if ($accion === 'optimizar_bd') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        try {
            $dbPath = getenv('APPDATA') . '/TG_Gestion/database.sqlite';
            $sizeBefore = file_exists($dbPath) ? filesize($dbPath) : 0;
            
            // VACUUM requiere que no haya transacciones activas
            $conexion->exec("VACUUM");
            $conexion->exec("ANALYZE");
            
            clearstatcache(); // Limpiar cache de filesize
            $sizeAfter = file_exists($dbPath) ? filesize($dbPath) : 0;
            $ahorro = round(($sizeBefore - $sizeAfter) / 1024, 2) . ' KB';
            
            echo json_encode(['success' => true, 'ahorro' => $ahorro]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al optimizar: ' . $e->getMessage()]);
        }
        exit();
    }

    // --- 8. RESETEAR SISTEMA (SuperAdmin) ---
    if ($accion === 'resetear_sistema') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        // Limpiar todas las tablas excepto usuarios
        $conexion->exec("DELETE FROM ventas");
        $conexion->exec("DELETE FROM venta_items");
        $conexion->exec("DELETE FROM registros");
        $conexion->exec("DELETE FROM productos");
        $conexion->exec("DELETE FROM fiados");
        $conexion->exec("DELETE FROM septimas");
        $conexion->exec("DELETE FROM log_errores");
        
        // Reset autoincrement
        $conexion->exec("DELETE FROM sqlite_sequence WHERE name IN ('ventas','venta_items','registros','productos','fiados','septimas')");
        
        echo json_encode(['success' => true, 'message' => 'Sistema reseteado correctamente']);
        exit();
    }

    // --- 9. LOG DE ERRORES (Sistema) ---
    if ($accion === 'log_error') {
        $data = json_decode(file_get_contents('php://input'), true);
        $tipo = $data['tipo'] ?? 'Unknown';
        $mensaje = $data['mensaje'] ?? '';
        $detalles = $data['detalles'] ?? '';
        $url = $data['url'] ?? '';
        
        $stmt = $conexion->prepare("INSERT INTO log_errores (tipo, mensaje, detalles, url, fecha) VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt->execute([$tipo, $mensaje, $detalles, $url]);
        
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error admin: ' . $e->getMessage()]);
}
?>