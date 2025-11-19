<?php
// api_ventas.php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$usuario = $_SESSION['usuario'];
$role = $_SESSION['role'];
$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($role === 'admin' || $role === 'superadmin');

// --- Lógica de Vendedor (Tiendita) ---
// Obtenemos el nombre real si es un vendedor
$vendedor_nombre = $_SESSION['vendedor_nombre'] ?? $usuario;


// --- ACCIÓN: Listar Historial de Ventas (Todos) ---
if ($accion === 'listar_ventas') {
    try {
        $stmt = $conexion->query("
            SELECT v.*, p.nombre as producto_nombre 
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            ORDER BY v.fecha DESC
            LIMIT 200
        ");
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'ventas' => $ventas]);
    } catch (PDOException $e) {
        logError("Error en API Ventas (Listar): " . $e->getMessage(), $conexion);
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    }
    exit();
}

// --- ACCIÓN: Listar Deudores (Fiados) (Todos) ---
if ($accion === 'listar_fiados') {
    try {
        // Agrupa por nombre_fiado y suma los totales NO pagados
        $stmt = $conexion->query("
            SELECT nombre_fiado, SUM(total) as total_deuda
            FROM ventas
            WHERE tipo_pago = 'fiado' AND fiado_pagado = 0
            GROUP BY nombre_fiado
            HAVING total_deuda > 0
            ORDER BY nombre_fiado ASC
        ");
        $deudores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'deudores' => $deudores]);
    } catch (PDOException $e) {
        logError("Error en API Ventas (Listar Fiados): " . $e->getMessage(), $conexion);
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    }
    exit();
}

// --- ACCIÓN: Pagar Fiado (Todos) ---
if ($accion === 'pagar_fiado' && !empty($_POST['nombre_fiado'])) {
    try {
        $nombre_fiado = $_POST['nombre_fiado'];
        // Marca todas las deudas de esta persona como pagadas
        $stmt = $conexion->prepare("
            UPDATE ventas 
            SET fiado_pagado = 1 
            WHERE nombre_fiado = ? AND tipo_pago = 'fiado'
        ");
        $stmt->execute([$nombre_fiado]);
        
        // Opcional: Registrar este pago en la tabla 'registros'
        $monto_pagado = $_POST['monto_pagado'] ?? 0; // JS debe enviar esto
        $regStmt = $conexion->prepare("INSERT INTO registros (tipo, concepto, monto, usuario) VALUES (?, ?, ?, ?)");
        $regStmt->execute(['ingreso', 'Pago de fiado: ' . $nombre_fiado, $monto_pagado, $vendedor_nombre]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        logError("Error en API Ventas (Pagar Fiado): " . $e->getMessage(), $conexion);
        echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
    }
    exit();
}

// --- ACCIÓN: Eliminar Venta (Admin/Superadmin) ---
// (*** LÓGICA DE DEVOLUCIÓN DE STOCK IMPLEMENTADA ***)
if ($accion === 'eliminar_venta' && $esAdmin && !empty($_POST['id'])) {
    
    $venta_id = $_POST['id'];
    
    try {
        // Iniciar transacción
        $conexion->beginTransaction();

        // 1. Obtener los datos de la venta que se va a eliminar
        $stmtVenta = $conexion->prepare("SELECT producto_id, cantidad FROM ventas WHERE id = ?");
        $stmtVenta->execute([$venta_id]);
        $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

        if ($venta) {
            $producto_id = $venta['producto_id'];
            $cantidad_devuelta = $venta['cantidad'];

            // 2. Devolver el stock al producto
            $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmtStock->execute([$cantidad_devuelta, $producto_id]);
        }
        
        // 3. Eliminar la venta
        $stmtDelete = $conexion->prepare("DELETE FROM ventas WHERE id = ?");
        $stmtDelete->execute([$venta_id]);

        // 4. Confirmar la transacción
        $conexion->commit();
        
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        // Si algo falla, revertir todo
        $conexion->rollBack();
        logError("Error en API Ventas (Eliminar con Devolución): " . $e->getMessage(), $conexion);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la venta: ' . $e->getMessage()]);
    }
    exit();
}


// --- ACCIÓN: Crear Venta (Default) ---
$data = json_decode(file_get_contents('php://input'), true);
$carrito = $data['carrito'] ?? null;
$tipo_pago = $data['tipo_pago'] ?? 'pagado';
$nombre_fiado = ($tipo_pago === 'fiado' && isset($data['nombre_fiado'])) ? $data['nombre_fiado'] : null;

if (!$carrito || empty($carrito)) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío.']);
    exit();
}

try {
    $conexion->beginTransaction();
    $ventaStmt = $conexion->prepare(
        "INSERT INTO ventas (producto_id, cantidad, total, vendedor, foto_referencia, tipo_pago, nombre_fiado) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stockStmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    foreach ($carrito as $item) {
        $producto_id = $item['id'];
        $cantidad = $item['cantidad'];
        $total = $item['precio'] * $cantidad;
        $foto = $item['foto'];
        
        // Usamos el nombre real del vendedor
        $ventaStmt->execute([$producto_id, $cantidad, $total, $vendedor_nombre, $foto, $tipo_pago, $nombre_fiado]);
        $stockStmt->execute([$cantidad, $producto_id]);
    }

    $conexion->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $conexion->rollBack();
    logError("Error en API Ventas (Crear): " . $e->getMessage(), $conexion);
    echo json_encode(['success' => false, 'message' => 'Error al procesar la venta: ' . $e->getMessage()]);
}
?>