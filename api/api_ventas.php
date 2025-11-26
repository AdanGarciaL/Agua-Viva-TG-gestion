<?php
// api/api_ventas.php
// VERSIÓN v4.0

session_start();
include 'db.php';
header('Content-Type: application/json');

// Logging pero sin mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', '0');

// DEBUG: Log de operaciones de ventas
@file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_ventas.log',
    date('Y-m-d H:i:s') . " [VENTAS] Accion: " . ($_REQUEST['accion'] ?? 'unknown') . " | Data: " . json_encode($_REQUEST) . "\n",
    FILE_APPEND
);

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
        include_once 'csrf.php';
        require_csrf_or_die();
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
        include_once 'csrf.php';
        require_csrf_or_die();
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
        
        // Borrar registro (Podríamos usar soft delete, pero mantenemos delete físico por tu solicitud anterior)
        $stmtDel = $conexion->prepare("DELETE FROM ventas WHERE id = ?");
        $stmtDel->execute([$id]);

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 5. NUEVA VENTA (Procesar Carrito - BLINDADO) ---
    include_once 'csrf.php';
    // Leer una sola vez el raw input (cached en csrf)
    $raw_input = get_cached_raw_input();
    $input = $raw_input ? json_decode($raw_input, true) : null;
    if ($input) {
        require_csrf_or_die();
        $carrito = $input['carrito'] ?? [];
        if (empty($carrito)) {
            echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
            exit();
        }

        $tipo = $input['tipo_pago'] ?? 'pagado';
        // Normalizar: "pagado" → "efectivo", "fiado" → "fiado"
        if ($tipo === 'pagado') $tipo = 'efectivo';
        
        $fiadoA = ($tipo === 'fiado') ? ($input['nombre_fiado'] ?? null) : null;

        $conexion->beginTransaction();

        // Consultas preparadas
        $stmtCheckStock = $conexion->prepare("SELECT nombre, stock FROM productos WHERE id = ?");
        $stmtVenta = $conexion->prepare("INSERT INTO ventas (producto_id, cantidad, total, vendedor, foto_referencia, tipo_pago, nombre_fiado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))");
        $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

        foreach ($carrito as $item) {
            // 1. BLINDAJE DE STOCK: Verificar antes de insertar
            $stmtCheckStock->execute([$item['id']]);
            $prodReal = $stmtCheckStock->fetch(PDO::FETCH_ASSOC);

            if (!$prodReal) {
                throw new Exception("El producto ID " . $item['id'] . " no existe.");
            }
            
            if ($prodReal['stock'] < $item['cantidad']) {
                throw new Exception("Stock insuficiente para: " . $prodReal['nombre'] . ". Quedan: " . $prodReal['stock']);
            }

            // 2. Si hay stock, procedemos
            // Soportar tanto 'precio' como 'precio_venta' del JS
            $precio = $item['precio'] ?? $item['precio_venta'] ?? 0;
            $total = floatval($precio) * intval($item['cantidad']);
            $stmtVenta->execute([$item['id'], $item['cantidad'], $total, $vendedor_nombre, $item['foto'] ?? null, $tipo, $fiadoA]);
            $stmtStock->execute([$item['cantidad'], $item['id']]);
        }

        // 3. REGISTRAR MOVIMIENTO EN CAJA (automático después de venta exitosa)
        $totalCarrito = array_reduce($carrito, function($sum, $item) {
            $precio = $item['precio'] ?? $item['precio_venta'] ?? 0;
            return $sum + (floatval($precio) * intval($item['cantidad']));
        }, 0);
        
        $stmtReg = $conexion->prepare("INSERT INTO registros (tipo, concepto, monto, usuario, fecha) VALUES (?, ?, ?, ?, datetime('now', 'localtime'))");
        if ($tipo === 'fiado') {
            $stmtReg->execute(['fiado', 'Fiado a: ' . ($fiadoA ?? 'Sin nombre'), $totalCarrito, $vendedor_nombre]);
        } else {
            $stmtReg->execute(['efectivo', 'Venta Efectivo', $totalCarrito, $vendedor_nombre]);
        }

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // v5.0: ÚLTIMAS VENTAS (para historial)
    if ($accion === 'ultimas_ventas') {
        $limit = min(20, max(1, intval($_GET['limit'] ?? 5)));
        $stmt = $conexion->prepare("
            SELECT id, total, tipo_pago, fecha
            FROM ventas
            ORDER BY fecha DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        echo json_encode(['success' => true, 'ventas' => $stmt->fetchAll()]);
        exit();
    }

} catch (Exception $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error venta: ' . $e->getMessage()]);
}
?>