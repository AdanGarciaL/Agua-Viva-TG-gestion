<?php
// api/api_inventario.php
// VERSIÓN FINAL: Producción

session_start();
include 'db.php';
header('Content-Type: application/json');
error_reporting(0);

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');

try {
    // --- 1. LISTAR PRODUCTOS ACTIVOS ---
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        echo json_encode(['success' => true, 'productos' => $stmt->fetchAll()]);
        exit();
    }

    // --- 2. CREAR PRODUCTO ---
    if ($accion === 'crear' && $esAdmin) {
        $nombre = $_POST['nombre'];
        $codigo = $_POST['codigo'] ?? '';
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $foto = $_POST['foto'] ?? '';

        $stmt = $conexion->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock, foto_url, activo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$nombre, $codigo, $precio, $stock, $foto]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 3. EDITAR PRODUCTO ---
    if ($accion === 'editar' && $esAdmin) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $codigo = $_POST['codigo'] ?? '';
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $foto = $_POST['foto'] ?? '';

        $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, codigo_barras = ?, precio_venta = ?, stock = ?, foto_url = ? WHERE id = ?");
        $stmt->execute([$nombre, $codigo, $precio, $stock, $foto, $id]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 4. ELIMINAR (LÓGICA BLINDADA) ---
    if ($accion === 'eliminar' && $esAdmin) {
        $id = $_POST['id'];

        try {
            // Intento 1: Borrado físico (si es nuevo)
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Producto eliminado.']);
        } catch (Exception $e) {
            // Intento 2: Si falla (por ventas previas), ocultarlo
            $stmt = $conexion->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Producto archivado (tiene historial).']);
        }
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error inventario: ' . $e->getMessage()]);
}
?>