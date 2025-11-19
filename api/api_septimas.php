<?php
// api/api_septimas.php
// VERSIÓN FINAL: Producción

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

// Seguridad extra: Vendedores no entran aquí
if (!$esAdmin) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

try {
    // --- 1. LISTAR SÉPTIMAS ---
    if ($accion === 'listar') {
        // Orden: Primero las pendientes (0), luego por fecha
        $stmt = $conexion->query("SELECT * FROM septimas ORDER BY pagado ASC, fecha DESC LIMIT 100");
        echo json_encode(['success' => true, 'septimas' => $stmt->fetchAll()]);
        exit();
    }

    // --- 2. CREAR REGISTRO ---
    if ($accion === 'crear') {
        $nombre = $_POST['nombre'];
        $monto = $_POST['monto'];

        $stmt = $conexion->prepare("INSERT INTO septimas (fecha, nombre_padrino, monto, usuario_registro, pagado) VALUES (datetime('now', 'localtime'), ?, ?, ?, 0)");
        $stmt->execute([$nombre, $monto, $usuario]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 3. EDITAR ---
    if ($accion === 'editar') {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $monto = $_POST['monto'];

        $stmt = $conexion->prepare("UPDATE septimas SET nombre_padrino = ?, monto = ? WHERE id = ?");
        $stmt->execute([$nombre, $monto, $id]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 4. MARCAR COMO PAGADO ---
    if ($accion === 'pagar') {
        $id = $_POST['id'];
        $stmt = $conexion->prepare("UPDATE septimas SET pagado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 5. ELIMINAR ---
    if ($accion === 'eliminar') {
        $id = $_POST['id'];
        $stmt = $conexion->prepare("DELETE FROM septimas WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error séptimas: ' . $e->getMessage()]);
}
?>