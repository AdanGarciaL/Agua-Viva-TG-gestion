<?php
// api/api_ventas.php
// VERSIÓN FINAL: Producción

session_start();
include 'db.php';
header('Content-Type: application/json');

// Evitar warnings en JSON
error_reporting(0);

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$usuario = $_SESSION['usuario'];
$role = $_SESSION['role'];
$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($role === 'admin' || $role === 'superadmin');
$vendedor_nombre = $_SESSION['vendedor_nombre'] ?? $usuario;

try {
    // --- 1. LISTAR VENTAS (Últimas 100 para velocidad) ---
    if ($accion === 'listar_ventas') {
        $stmt = $conexion->query("
            SELECT v.*, p.nombre as producto_nombre 
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            ORDER BY v.fecha DESC
            LIMIT 100
        ");
        echo json_encode(['success' => true, 'ventas' => $stmt->fetchAll()]);
        exit();
    }

    // --- 2. LISTAR DEUDORES ---
    if ($accion === 'listar_fiados') {
        $stmt = $conexion->query("
            SELECT nombre_fiado, SUM(total) as total_deuda
            FROM ventas
            WHERE tipo_pago = 'fiado' AND fiado_pagado = 0
            GROUP BY nombre_fiado
            HAVING total_deuda > 0
            ORDER BY nombre_fiado ASC
        ");
        echo json_encode(['success' => true, 'deudores' => $stmt->fetchAll()]);
        exit();
    }

    // --- 3. PAGAR DEUDA ---
    if ($accion === 'pagar_fiado' && !empty($_POST['nombre_fiado'])) {
        $nombre = $_POST['nombre_fiado'];
        $monto = $_POST['monto_pagado'] ?? 0;

        $conexion->beginTransaction();
        
        // Marcar ventas como pagadas
        $stmt = $conexion->prepare("UPDATE ventas SET fiado_pagado = 1 WHERE nombre_fiado = ? AND tipo_pago = 'fiado'");
        $stmt->execute([$nombre]);

        // Registrar ingreso de dinero en caja
        $stmtReg = $conexion->prepare("INSERT INTO registros (tipo, concepto, monto, usuario, fecha) VALUES ('ingreso', ?, ?, ?, datetime('now', 'localtime'))");
        $stmtReg->execute(['Pago de fiado: ' . $nombre, $monto, $vendedor_nombre]);

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 4. ELIMINAR VENTA (Solo Admins - Devuelve Stock) ---
    if ($accion === 'eliminar_venta' && $esAdmin) {
        $id = $_POST['id'];
        
        $conexion->beginTransaction();
        
        // Obtener cantidad para devolver
        $stmtGet = $conexion->prepare("SELECT producto_id, cantidad FROM ventas WHERE id = ?");
        $stmtGet->execute([$id]);
        $venta = $stmtGet->fetch();

        if ($venta) {
            // Devolver stock
            $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmtStock->execute([$venta['cantidad'], $venta['producto_id']]);
        }
        
        // Borrar registro
        $stmtDel = $conexion->prepare("DELETE FROM ventas WHERE id = ?");
        $stmtDel->execute([$id]);

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 5. NUEVA VENTA (Procesar Carrito) ---
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $carrito = $input['carrito'] ?? [];
        if (empty($carrito)) {
            echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
            exit();
        }

        $tipo = $input['tipo_pago'];
        $fiadoA = ($tipo === 'fiado') ? $input['nombre_fiado'] : null;

        $conexion->beginTransaction();

        $stmtVenta = $conexion->prepare("INSERT INTO ventas (producto_id, cantidad, total, vendedor, foto_referencia, tipo_pago, nombre_fiado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))");
        $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

        foreach ($carrito as $item) {
            $total = $item['precio'] * $item['cantidad'];
            $stmtVenta->execute([$item['id'], $item['cantidad'], $total, $vendedor_nombre, $item['foto'], $tipo, $fiadoA]);
            $stmtStock->execute([$item['cantidad'], $item['id']]);
        }

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error venta: ' . $e->getMessage()]);
}
?>