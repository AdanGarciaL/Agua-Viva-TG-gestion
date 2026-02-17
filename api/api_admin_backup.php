<?php
// api/api_admin.php
// VERSIÓN v5.2 - Reconexión automática + Type hints mejorados

ob_clean();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db.php';

// Asegurar conexión de BD
if (!asegurar_conexion_db()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos']);
    exit();
}

/** @var \PDO $conexion */

// Validación básica de sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';
$esSuperAdmin = ($_SESSION['role'] === 'superadmin');
$esAdmin = ($_SESSION['role'] === 'admin' || $esSuperAdmin);
$isMysql = (defined('DB_DRIVER') && DB_DRIVER === 'mysql');
$nowExpr = $isMysql ? 'NOW()' : "datetime('now', 'localtime')";
$todayExpr = $isMysql ? 'CURDATE()' : "date('now', 'localtime')";
$monthExpr = $isMysql ? "DATE_FORMAT(%s, '%Y-%m')" : "strftime('%Y-%m', %s)";

try {
    // --- 0. AUTENTICAR ADMIN (Para operaciones sensibles desde frontend) ---
    if ($accion === 'autenticar_admin') {
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($usuario) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incompletas']);
            exit();
        }
        
        // Verificar que sea admin o superadmin
        $stmt = $conexion->prepare("SELECT id, password, role FROM usuarios WHERE username = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'superadmin')) {
            echo json_encode(['success' => false, 'message' => 'Usuario no válido o sin permisos']);
            exit();
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
            exit();
        }
        
        echo json_encode(['success' => true, 'message' => 'Autenticado', 'role' => $user['role']]);
        exit();
    }

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

    // --- 2.1 LIMPIAR LOGS DE ERROR (Solo Superadmin) ---
    if ($accion === 'limpiar_logs') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        include_once 'csrf.php';
        require_csrf_or_die();

        try {
            $dias = intval($_POST['dias'] ?? 30);
            
            if ($isMysql) {
                $stmt = $conexion->prepare("DELETE FROM log_errores WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)");
            } else {
                $stmt = $conexion->prepare("DELETE FROM log_errores WHERE fecha < datetime('now', '-' || ? || ' days')");
            }
            $stmt->execute([$dias]);
            $deleted = $conexion->query("SELECT changes() as c")->fetch()['c'] ?? $stmt->rowCount();
            
            echo json_encode(['success' => true, 'message' => "Se eliminaron $deleted registros de error con más de $dias días"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
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
        if ($isMysql) {
            $stmt = $conexion->prepare("INSERT INTO configuracion (clave, valor) VALUES ('color_tema', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        } else {
            $stmt = $conexion->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES ('color_tema', ?)");
        }
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
            SELECT p.nombre, COUNT(*) as ventas
            FROM ventas v
            INNER JOIN productos p ON v.producto_id = p.id
            WHERE strftime('%Y-%m', v.fecha) = ?
            GROUP BY v.producto_id
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
        $stmt = $conexion->prepare("
            SELECT COALESCE(SUM(total), 0) as total 
            FROM ventas 
            WHERE strftime('%Y-%m', fecha) = ?
        ");
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

    // --- 6. SALUD DEL SISTEMA (Admin+) - Nuevo Panel ---
    if ($accion === 'salud_sistema') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        try {
            // 1. Tamaño de BD (en MB)
            if ($isMysql) {
                $stmt = $conexion->query("SELECT ROUND(SUM(data_length + index_length)/1024/1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
                $dbSize = floatval($stmt->fetch()['size_mb'] ?? 0);
            } else {
                $dbPath = DB_PATH;
                $dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024 / 1024, 2) : 0;
            }
            
            // 2. Usuarios totales
            $stmt = $conexion->query("SELECT COUNT(*) as count FROM usuarios");
            $row = $stmt->fetch();
            $usuariosTotal = intval($row && isset($row['count']) ? $row['count'] : 0);
            
            // 3. Productos activos
            $stmt = $conexion->query("SELECT COUNT(*) as count FROM productos WHERE activo = 1");
            $row = $stmt->fetch();
            $productosActivos = intval($row && isset($row['count']) ? $row['count'] : 0);
            
            // 4. Total ventas (suma de montos)
            $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas");
            $row = $stmt->fetch();
            $ventasTotal = floatval($row && isset($row['total']) ? $row['total'] : 0);
            
            // 5. Fiados pendientes
            $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE tipo_pago = 'fiado' AND fiado_pagado = 0");
            $row = $stmt->fetch();
            $fiados = floatval($row && isset($row['total']) ? $row['total'] : 0);
            
            // 6. Espacio libre en disco (donde está la BD)
            if ($isMysql) {
                $diskFreeGb = null;
            } else {
                $dbDir = dirname($dbPath);
                $diskFree = disk_free_space($dbDir);
                $diskFreeGb = $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) : 0;
            }
            
            echo json_encode([
                'success' => true,
                'db_size_mb' => $dbSize,
                'usuarios_total' => $usuariosTotal,
                'productos_activos' => $productosActivos,
                'ventas_total' => $ventasTotal,
                'fiados_pendientes' => $fiados,
                'disk_free_gb' => $diskFreeGb
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error calculando salud: ' . $e->getMessage(),
                'db_size_mb' => 0,
                'usuarios_total' => 0,
                'productos_activos' => 0,
                'ventas_total' => 0,
                'fiados_pendientes' => 0,
                'disk_free_gb' => 0
            ]);
        }
        exit();
    }

    // --- 7. ESTADÍSTICAS GLOBALES (SuperAdmin) ---
    if ($accion === 'stats_globales') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        // Tamaño de BD
        $dbPath = DB_PATH;
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
            if ($isMysql) {
                $tables = ['usuarios','productos','ventas','registros','septimas','log_errores','configuracion','audit_log'];
                foreach ($tables as $t) {
                    try { $conexion->exec("OPTIMIZE TABLE $t"); } catch (Exception $e) { /* ignore */ }
                }
                echo json_encode(['success' => true, 'ahorro' => 'Optimizado']);
            } else {
                $dbPath = getenv('APPDATA') . '/TG_Gestion/database.sqlite';
                $sizeBefore = file_exists($dbPath) ? filesize($dbPath) : 0;
                
                // VACUUM requiere que no haya transacciones activas
                $conexion->exec("VACUUM");
                $conexion->exec("ANALYZE");
                
                clearstatcache();
                $sizeAfter = file_exists($dbPath) ? filesize($dbPath) : 0;
                $ahorro = round(($sizeBefore - $sizeAfter) / 1024, 2) . ' KB';
                
                echo json_encode(['success' => true, 'ahorro' => $ahorro]);
            }
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
        
        $tableExists = function($table) use ($conexion, $isMysql) {
            if ($isMysql) {
                $stmt = $conexion->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
                $stmt->execute([$table]);
                return (bool)$stmt->fetchColumn();
            }
            $stmt = $conexion->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        };

        $tablesToClear = ['ventas','venta_items','registros','productos','fiados','septimas','log_errores'];
        foreach ($tablesToClear as $t) {
            if ($tableExists($t)) {
                $conexion->exec("DELETE FROM $t");
            }
        }
        
        if ($isMysql) {
            $tablesToReset = ['ventas','registros','productos','septimas'];
            foreach ($tablesToReset as $t) {
                if ($tableExists($t)) {
                    $conexion->exec("ALTER TABLE $t AUTO_INCREMENT = 1");
                }
            }
        } else {
            $conexion->exec("DELETE FROM sqlite_sequence WHERE name IN ('ventas','venta_items','registros','productos','fiados','septimas')");
        }
        
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
        
        $stmt = $conexion->prepare("INSERT INTO log_errores (tipo, mensaje, detalles, url, fecha) VALUES (?, ?, ?, ?, $nowExpr)");
        $stmt->execute([$tipo, $mensaje, $detalles, $url]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 10. DATOS PARA GRÁFICOS (Admin+) ---
    if ($accion === 'datos_graficos') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        // A. Ventas por día (últimos 7 días)
        $ventasPorDia = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $stmt = $conexion->prepare("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE date(fecha) = ?");
            $stmt->execute([$fecha]);
            $total = $stmt->fetch()['total'];
            $ventasPorDia[] = [
                'fecha' => date('d/m', strtotime($fecha)),
                'total' => floatval($total)
            ];
        }
        
        // B. Top 5 productos más vendidos (últimos 30 días)
        $topProductos = [];
        $last30 = $isMysql ? "DATE_SUB(CURDATE(), INTERVAL 30 DAY)" : "date('now', '-30 days')";
        $stmt = $conexion->query("
            SELECT p.nombre, SUM(v.cantidad) as cantidad_vendida
            FROM ventas v
            INNER JOIN productos p ON v.producto_id = p.id
            WHERE DATE(v.fecha) >= $last30
            GROUP BY v.producto_id
            ORDER BY cantidad_vendida DESC
            LIMIT 5
        ");
        while ($row = $stmt->fetch()) {
            $topProductos[] = [
                'nombre' => $row['nombre'],
                'cantidad_vendida' => intval($row['cantidad_vendida'])
            ];
        }
        
        // C. Ventas por tipo de pago (últimos 30 días)
        $ventasPorTipo = [];
        $stmt = $conexion->query("
            SELECT tipo_pago, COALESCE(SUM(total), 0) as total
            FROM ventas
            WHERE DATE(fecha) >= $last30
            GROUP BY tipo_pago
        ");
        while ($row = $stmt->fetch()) {
            $ventasPorTipo[] = [
                'tipo_pago' => strtolower($row['tipo_pago']),
                'total' => floatval($row['total'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'ventas_por_dia' => $ventasPorDia,
            'top_productos' => $topProductos,
            'ventas_por_tipo' => $ventasPorTipo
        ]);
        exit();
    }

    // --- GESTIÓN DE LOGS DE ERROR (Solo Superadmin) ---
    
    if ($accion === 'guardar_optimizacion') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $nivel = $data['nivel'] ?? 'ninguno';

            $stmt = $conexion->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES (?, ?)");
            $stmt->execute(['optimizacion_nivel', $nivel]);

            echo json_encode(['success' => true, 'message' => 'Configuración guardada']);
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit();
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error admin: ' . $e->getMessage()]);
}
?>
