<?php
// api/api_caja.php - Gestión de Cortes de Caja
// VERSIÓN v1.0 - Sistema de cortes flexibles (no cada 24h)

session_start();
include 'db.php';
include 'error_handler.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!asegurar_conexion_db()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos']);
    exit();
}

validar_sesion();

$usuario = $_SESSION['usuario'];
$role = $_SESSION['role'];
$esAdmin = ($role === 'admin' || $role === 'superadmin');
$accion = $_REQUEST['accion'] ?? '';
$nowExpr = (defined('DB_DRIVER') && DB_DRIVER === 'mysql') ? 'NOW()' : "datetime('now', 'localtime')";

try {
    // --- 1. ABRIR CORTE DE CAJA (Solo Admin) ---
    if ($accion === 'abrir_corte' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $saldo_inicial = floatval($_POST['saldo_inicial'] ?? 0);
        
        // Verificar si hay un corte abierto
        $stmt = $conexion->prepare("SELECT id FROM cortes_caja WHERE estado = 'abierto' LIMIT 1");
        $stmt->execute();
        $corteAbierto = $stmt->fetch();
        
        if ($corteAbierto) {
            error_response('corte_abierto', ['id' => $corteAbierto['id']]);
        }
        
        try {
            $stmt = $conexion->prepare("
                INSERT INTO cortes_caja (fecha_apertura, usuario_apertura, saldo_inicial, estado)
                VALUES ($nowExpr, ?, ?, 'abierto')
            ");
            $stmt->execute([$usuario, $saldo_inicial]);
            
            $id_corte = $conexion->lastInsertId();
            
            // Registrar en auditoría
            registrar_auditoria('cortes_caja', $id_corte, 'corte_abierto', null, 'Saldo inicial: $' . $saldo_inicial);
            
            echo json_encode(['success' => true, 'message' => 'Corte abierto', 'corte_id' => $id_corte]);
        } catch (Exception $e) {
            error_response('error_abrir_corte', ['detalle' => $e->getMessage()]);
        }
        exit();
    }
    
    // --- 2. OBTENER CORTE ACTUAL (cualquier usuario) ---
    if ($accion === 'corte_actual') {
        $stmt = $conexion->prepare("
            SELECT * FROM cortes_caja WHERE estado = 'abierto' LIMIT 1
        ");
        $stmt->execute();
        $corte = $stmt->fetch();
        
        if (!$corte) {
            echo json_encode(['success' => false, 'message' => 'No hay corte abierto', 'corte' => null]);
        } else {
            // Calcular ingresos desde la apertura del corte
            $stmt2 = $conexion->prepare("
                SELECT 
                    SUM(CASE WHEN tipo_pago = 'efectivo' THEN total ELSE 0 END) as efectivo,
                    SUM(CASE WHEN tipo_pago = 'tarjeta' THEN total ELSE 0 END) as tarjeta,
                    SUM(CASE WHEN tipo_pago = 'transferencia' THEN total ELSE 0 END) as transferencia,
                    COUNT(*) as total_ventas
                FROM ventas
                WHERE fecha >= ?
            ");
            $stmt2->execute([$corte['fecha_apertura']]);
            $ventas = $stmt2->fetch();
            
            // Calcular egresos
            $stmt3 = $conexion->prepare("
                SELECT COALESCE(SUM(monto), 0) as total_egresos
                FROM registros
                WHERE tipo = 'egreso' AND fecha >= ?
            ");
            $stmt3->execute([$corte['fecha_apertura']]);
            $egresos = $stmt3->fetch();
            
            $corte['ventas'] = $ventas;
            $corte['egresos_total'] = floatval($egresos['total_egresos']);
            
            echo json_encode(['success' => true, 'corte' => $corte]);
        }
        exit();
    }
    
    // --- 3. CERRAR CORTE DE CAJA (Solo Admin) ---
    if ($accion === 'cerrar_corte' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $id_corte = intval($_POST['id_corte'] ?? 0);
        $saldo_final = floatval($_POST['saldo_final'] ?? 0);
        $notas = $_POST['notas'] ?? '';
        
        if (!$id_corte) {
            error_response('datos_incompletos', ['field' => 'id_corte']);
        }
        
        $conexion->beginTransaction();
        
        try {
            // Obtener corte
            $stmt = $conexion->prepare("SELECT * FROM cortes_caja WHERE id = ? AND estado = 'abierto'");
            $stmt->execute([$id_corte]);
            $corte = $stmt->fetch();
            
            if (!$corte) {
                error_response('corte_no_encontrado');
            }
            
            // Calcular movimientos desde la apertura
            $stmt2 = $conexion->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipo_pago = 'efectivo' THEN total ELSE 0 END), 0) as efectivo,
                    COALESCE(SUM(CASE WHEN tipo_pago = 'tarjeta' THEN total ELSE 0 END), 0) as tarjeta,
                    COALESCE(SUM(CASE WHEN tipo_pago = 'transferencia' THEN total ELSE 0 END), 0) as transferencia
                FROM ventas
                WHERE fecha >= ?
            ");
            $stmt2->execute([$corte['fecha_apertura']]);
            $ventas = $stmt2->fetch();
            
            $stmt3 = $conexion->prepare("
                SELECT COALESCE(SUM(monto), 0) as total_egresos
                FROM registros
                WHERE tipo = 'egreso' AND fecha >= ?
            ");
            $stmt3->execute([$corte['fecha_apertura']]);
            $egresos = $stmt3->fetch();
            
            $ingresos_total = floatval($ventas['efectivo']) + floatval($ventas['tarjeta']) + floatval($ventas['transferencia']);
            $egresos_total = floatval($egresos['total_egresos']);
            $diferencia = $saldo_final - ($corte['saldo_inicial'] + $ingresos_total - $egresos_total);
            
            // Cerrar corte
            $stmt4 = $conexion->prepare("
                UPDATE cortes_caja
                SET fecha_cierre = $nowExpr,
                    usuario_cierre = ?,
                    ingresos_efectivo = ?,
                    ingresos_tarjeta = ?,
                    ingresos_transferencia = ?,
                    egresos = ?,
                    saldo_final = ?,
                    diferencia = ?,
                    notas = ?,
                    estado = 'cerrado'
                WHERE id = ?
            ");
            $stmt4->execute([
                $usuario,
                floatval($ventas['efectivo']),
                floatval($ventas['tarjeta']),
                floatval($ventas['transferencia']),
                $egresos_total,
                $saldo_final,
                $diferencia,
                $notas,
                $id_corte
            ]);
            
            // Registrar en auditoría
            registrar_auditoria('cortes_caja', $id_corte, 'corte_cerrado', 'abierto', "Saldo final: $saldo_final, Diferencia: $diferencia");
            
            $conexion->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Corte cerrado exitosamente',
                'resumen' => [
                    'saldo_inicial' => floatval($corte['saldo_inicial']),
                    'ingresos_efectivo' => floatval($ventas['efectivo']),
                    'ingresos_tarjeta' => floatval($ventas['tarjeta']),
                    'ingresos_transferencia' => floatval($ventas['transferencia']),
                    'egresos' => $egresos_total,
                    'saldo_esperado' => floatval($corte['saldo_inicial']) + $ingresos_total - $egresos_total,
                    'saldo_final' => $saldo_final,
                    'diferencia' => $diferencia
                ]
            ]);
        } catch (Exception $e) {
            $conexion->rollBack();
            error_response('error_cerrar_corte', ['detalle' => $e->getMessage()]);
        }
        exit();
    }
    
    // --- 4. LISTAR CORTES HISTÓRICOS (Admin) ---
    if ($accion === 'listar_cortes' && $esAdmin) {
        $estado = $_GET['estado'] ?? 'todos'; // 'abierto', 'cerrado', 'todos'
        $limit = intval($_GET['limit'] ?? 50);
        
        $sql = "SELECT * FROM cortes_caja";
        $params = [];
        
        if ($estado !== 'todos') {
            $sql .= " WHERE estado = ?";
            $params[] = $estado;
        }
        
        $sql .= " ORDER BY fecha_apertura DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'cortes' => $stmt->fetchAll()]);
        exit();
    }

    // --- 4.1 ELIMINAR HISTORIAL DE CORTES CERRADOS (Solo Admin) ---
    if ($accion === 'eliminar_historial' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();

        $conexion->beginTransaction();

        try {
            $stmtAbierto = $conexion->prepare("SELECT id FROM cortes_caja WHERE estado = 'abierto' LIMIT 1");
            $stmtAbierto->execute();
            $corteAbierto = $stmtAbierto->fetch();

            if ($corteAbierto) {
                error_response('corte_abierto', ['id' => $corteAbierto['id']]);
            }

            $stmtCount = $conexion->prepare("SELECT COUNT(*) as total FROM cortes_caja WHERE estado = 'cerrado'");
            $stmtCount->execute();
            $total = intval(($stmtCount->fetch()['total'] ?? 0));

            if ($total > 0) {
                $stmtDelete = $conexion->prepare("DELETE FROM cortes_caja WHERE estado = 'cerrado'");
                $stmtDelete->execute();
            }

            registrar_auditoria('cortes_caja', 0, 'historial_cortes_eliminado', null, 'Total eliminados: ' . $total);
            $conexion->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Historial de cortes eliminado',
                'eliminados' => $total
            ]);
        } catch (Exception $e) {
            $conexion->rollBack();
            error_response('error_eliminar_historial_cortes', ['detalle' => $e->getMessage()]);
        }
        exit();
    }
    
    // --- 5. OBTENER DETALLE DE CORTE ---
    if ($accion === 'detalle_corte') {
        $id_corte = intval($_GET['id_corte'] ?? 0);
        
        if (!$id_corte) {
            error_response('datos_incompletos', ['field' => 'id_corte']);
        }
        
        $stmt = $conexion->prepare("SELECT * FROM cortes_caja WHERE id = ?");
        $stmt->execute([$id_corte]);
        $corte = $stmt->fetch();
        
        if (!$corte) {
            error_response('corte_no_encontrado');
        }
        
        // Obtener ventas del corte
        $stmt2 = $conexion->prepare("
            SELECT v.id, v.fecha, v.tipo_pago, v.total, p.nombre as producto_nombre
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            WHERE v.fecha >= ? AND v.fecha <= ?
            ORDER BY v.fecha DESC
        ");
        $fechaFin = $corte['fecha_cierre'] ?? $nowExpr;
        $stmt2->execute([$corte['fecha_apertura'], $fechaFin]);
        $ventas = $stmt2->fetchAll();
        
        // Obtener egresos del corte
        $stmt3 = $conexion->prepare("
            SELECT * FROM registros
            WHERE tipo = 'egreso' AND fecha >= ? AND fecha <= ?
            ORDER BY fecha DESC
        ");
        $stmt3->execute([$corte['fecha_apertura'], $fechaFin]);
        $egresos = $stmt3->fetchAll();
        
        echo json_encode([
            'success' => true,
            'corte' => $corte,
            'ventas' => $ventas,
            'egresos' => $egresos
        ]);
        exit();
    }

} catch (Exception $e) {
    manejar_excepcion_general($e, 'api_caja');
}
?>
