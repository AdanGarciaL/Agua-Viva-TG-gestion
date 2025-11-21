<?php
// api/api_registros.php
// VERSIÓN v4.0

session_start();
include 'db.php';
header('Content-Type: application/json');
error_reporting(0);

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
        $id = $_POST['id'];
        $stmt = $conexion->prepare("DELETE FROM registros WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 4. CORTE DE CAJA (NUEVO) ---
    if ($accion === 'corte_dia') {
        // Fecha de hoy en servidor
        $hoy = date('Y-m-d');

        // A. Sumar Ventas en Efectivo de HOY
        $sqlVentas = "SELECT SUM(total) as total FROM ventas 
                      WHERE tipo_pago = 'pagado' 
                      AND date(fecha) = date('now', 'localtime')";
        $ventas = $conexion->query($sqlVentas)->fetch()['total'] ?? 0;

        // B. Sumar Movimientos Manuales de HOY (Ingresos, Gastos, Retiros)
        $sqlMovs = "SELECT tipo, SUM(monto) as total FROM registros 
                    WHERE date(fecha) = date('now', 'localtime') 
                    GROUP BY tipo";
        $stmtMovs = $conexion->query($sqlMovs);
        
        $ingresos = 0;
        $gastos = 0;
        $retiros = 0;

        while ($row = $stmtMovs->fetch()) {
            if ($row['tipo'] === 'ingreso') $ingresos = $row['total'];
            if ($row['tipo'] === 'gasto') $gastos = $row['total'];
            if ($row['tipo'] === 'egreso') $retiros = $row['total'];
        }

        // Cálculo Final: Dinero que debe haber en el cajón
        // Nota: Los pagos de fiados entran como 'ingreso' en la tabla registros, así que ya se suman ahí.
        $enCaja = ($ventas + $ingresos) - ($gastos + $retiros);

        echo json_encode([
            'success' => true,
            'corte' => [
                'ventas_efectivo' => $ventas,
                'ingresos_extra' => $ingresos,
                'gastos' => $gastos,
                'retiros' => $retiros,
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