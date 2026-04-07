<?php
// api/api_registros.php
// VERSIÓN v4.1 - Reconexión automática

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);  // NO mostrar errores HTML
include 'db.php';
include 'error_handler.php';  // AGREGADO para manejar errores
header('Content-Type: application/json');

// Asegurar conexión de BD
if (!asegurar_conexion_db()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos']);
    exit();
}

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$usuario = $_SESSION['usuario'];
$role = $_SESSION['role'];
$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($role === 'admin' || $role === 'superadmin');
$nowExpr = (defined('DB_DRIVER') && DB_DRIVER === 'mysql') ? 'NOW()' : "datetime('now', 'localtime')";
$todayExpr = (defined('DB_DRIVER') && DB_DRIVER === 'mysql') ? 'CURDATE()' : "date('now', 'localtime')";

try {
    // --- 1. LISTAR MOVIMIENTOS ---
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM registros ORDER BY id DESC LIMIT 200");
        echo json_encode(['success' => true, 'registros' => $stmt->fetchAll()]);
        exit();
    }

    // --- 2. CREAR REGISTRO ---
    if ($accion === 'crear' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $tipo = $_POST['tipo'];
        $concepto = $_POST['concepto'];
        $monto = $_POST['monto'];
        $categoria = $_POST['categoria'] ?? null;
        $servicio = $_POST['servicio'] ?? null;

        $stmt = $conexion->prepare("INSERT INTO registros (fecha, tipo, concepto, monto, usuario, categoria, servicio) VALUES ($nowExpr, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tipo, $concepto, $monto, $usuario, $categoria, $servicio]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 2b. EDITAR REGISTRO ---
    if ($accion === 'editar' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $id = $_POST['id'] ?? null;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Falta id']); exit(); }

        // Obtener valores anteriores
        $stmt_prev = $conexion->prepare("SELECT * FROM registros WHERE id = ?");
        $stmt_prev->execute([$id]);
        $anterior = $stmt_prev->fetch(PDO::FETCH_ASSOC);

        $tipo = $_POST['tipo'];
        $concepto = $_POST['concepto'];
        $monto = $_POST['monto'];
        $categoria = $_POST['categoria'] ?? null;
        $servicio = $_POST['servicio'] ?? null;

        $stmt = $conexion->prepare("UPDATE registros SET tipo = ?, concepto = ?, monto = ?, categoria = ?, servicio = ? WHERE id = ?");
        $stmt->execute([$tipo, $concepto, $monto, $categoria, $servicio, $id]);

        // Registrar cambios en audit_log
        if ($anterior['tipo'] !== $tipo) {
            registrar_auditoria('registros', $id, 'tipo', $anterior['tipo'], $tipo);
        }
        if ($anterior['concepto'] !== $concepto) {
            registrar_auditoria('registros', $id, 'concepto', $anterior['concepto'], $concepto);
        }
        if (floatval($anterior['monto']) !== floatval($monto)) {
            registrar_auditoria('registros', $id, 'monto', $anterior['monto'], $monto);
        }
        if ($anterior['categoria'] !== $categoria) {
            registrar_auditoria('registros', $id, 'categoria', $anterior['categoria'], $categoria);
        }
        if ($anterior['servicio'] !== $servicio) {
            registrar_auditoria('registros', $id, 'servicio', $anterior['servicio'], $servicio);
        }

        echo json_encode(['success' => true]);
        exit();
    }

    // --- 2c. HISTORIAL DE CAMBIOS ---
    if ($accion === 'historial' && $esAdmin) {
        $registro_id = intval($_GET['registro_id'] ?? 0);
        
        if ($registro_id === 0) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            exit();
        }
        
        $stmt = $conexion->prepare("
            SELECT * FROM audit_log
            WHERE tabla = 'registros' AND registro_id = ?
            ORDER BY fecha DESC
            LIMIT 50
        ");
        $stmt->execute([$registro_id]);
        $historial = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'historial' => $historial]);
        exit();
    }

    // --- 3. ELIMINAR REGISTRO ---
    if ($accion === 'eliminar' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $id = $_POST['id'];
        $stmt = $conexion->prepare("DELETE FROM registros WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 3b. ELIMINAR TODOS LOS REGISTROS ---
    if ($accion === 'eliminar_todos' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();

        $deleted = 0;
        if (defined('DB_DRIVER') && DB_DRIVER === 'mysql') {
            $deleted = intval($conexion->query("SELECT COUNT(*) AS c FROM registros")->fetch()['c'] ?? 0);
            $conexion->exec("TRUNCATE TABLE registros");
        } else {
            $deleted = intval($conexion->query("SELECT COUNT(*) AS c FROM registros")->fetch()['c'] ?? 0);
            $conexion->exec("DELETE FROM registros");
        }

        echo json_encode(['success' => true, 'deleted' => $deleted]);
        exit();
    }

    // --- 4. CORTE DE CAJA (NUEVO) ---
    if ($accion === 'corte_dia') {
        // A. Sumar Ventas en Efectivo de HOY desde tabla ventas (fuente única)
        $sqlVentas = "SELECT SUM(total) as total FROM ventas 
                  WHERE tipo_pago IN ('efectivo', 'pagado') 
                  AND DATE(fecha) = $todayExpr";
        $ventasEfectivo = $conexion->query($sqlVentas)->fetch()['total'] ?? 0;

        // B. Sumar Movimientos adicionales de registros de HOY
        $sqlMovs = "SELECT tipo, SUM(monto) as total FROM registros 
                WHERE DATE(fecha) = $todayExpr 
                GROUP BY tipo";
        $stmtMovs = $conexion->query($sqlMovs);
        
        $ingresos = 0;
        $gastos = 0;
        $retiros = 0;

        while ($row = $stmtMovs->fetch()) {
            switch($row['tipo']) {
                case 'ingreso':
                case 'septima':
                case 'septima_especial':
                    $ingresos += floatval($row['total']);
                    break;
                case 'gasto':
                case 'egreso':
                case 'merma':
                    $gastos += floatval($row['total']);
                    if ($row['tipo'] === 'egreso') $retiros += floatval($row['total']);
                    break;
                default:
                    // Tipos no contemplados no afectan caja principal
                    break;
            }
        }

        // C. Fiados pendientes desde tabla ventas (evita desajustes al cancelar)
        $sqlFiados = "SELECT SUM(total) as total FROM ventas 
                  WHERE tipo_pago = 'fiado' AND fiado_pagado = 0 
                  AND DATE(fecha) = $todayExpr";
        $fiados = $conexion->query($sqlFiados)->fetch()['total'] ?? 0;

        // D. Cálculo Final: Dinero que debe haber en el cajón
        // Ventas Efectivo + Ingresos - Gastos - Retiros
        // (Los fiados NO entran en caja hasta que se paguen)
        $enCaja = ($ventasEfectivo + $ingresos) - ($gastos + $retiros);

        echo json_encode([
            'success' => true,
            'corte' => [
                'ventas_efectivo' => $ventasEfectivo,
                'ingresos_extra' => $ingresos,
                'gastos' => $gastos,
                'retiros' => $retiros,
                'fiados_pendientes' => $fiados,
                'total_caja' => $enCaja
            ]
        ]);
        exit();
    }

} catch (Exception $e) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    manejar_excepcion_general($e, 'registros');
}
?>

?>