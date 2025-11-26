<?php
// api/api_registros.php
// VERSIÓN v4.0

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);  // NO mostrar errores HTML
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$usuario = $_SESSION['usuario'];
$role = $_SESSION['role'];
$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($role === 'admin' || $role === 'superadmin');

try {
    // --- 1. LISTAR MOVIMIENTOS ---
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM registros ORDER BY id DESC LIMIT 100");
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

        $stmt = $conexion->prepare("INSERT INTO registros (fecha, tipo, concepto, monto, usuario) VALUES (datetime('now', 'localtime'), ?, ?, ?, ?)");
        $stmt->execute([$tipo, $concepto, $monto, $usuario]);
        
        echo json_encode(['success' => true]);
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

    // --- 4. CORTE DE CAJA (NUEVO) ---
    if ($accion === 'corte_dia') {
        // A. Sumar Ventas en Efectivo de HOY desde tabla ventas
        $sqlVentas = "SELECT SUM(total) as total FROM ventas 
                      WHERE tipo_pago IN ('efectivo', 'pagado') 
                      AND date(fecha) = date('now', 'localtime')";
        $ventasDirectas = $conexion->query($sqlVentas)->fetch()['total'] ?? 0;

        // B. Sumar Movimientos de registros de HOY
        $sqlMovs = "SELECT tipo, SUM(monto) as total FROM registros 
                    WHERE date(fecha) = date('now', 'localtime') 
                    GROUP BY tipo";
        $stmtMovs = $conexion->query($sqlMovs);
        
        $ventasEfectivo = 0;
        $ingresos = 0;
        $gastos = 0;
        $retiros = 0;
        $fiados = 0;

        while ($row = $stmtMovs->fetch()) {
            switch($row['tipo']) {
                case 'efectivo':
                    $ventasEfectivo = floatval($row['total']);
                    break;
                case 'ingreso':
                    $ingresos = floatval($row['total']);
                    break;
                case 'gasto':
                    $gastos = floatval($row['total']);
                    break;
                case 'egreso':
                    $retiros = floatval($row['total']);
                    break;
                case 'fiado':
                    $fiados = floatval($row['total']);
                    break;
            }
        }

        // Cálculo Final: Dinero que debe haber en el cajón
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error registros: ' . $e->getMessage()]);
}
?>