<?php
// api/api_registros.php
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

try {
    // --- 1. LISTAR MOVIMIENTOS (Últimos 100) ---
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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error registros: ' . $e->getMessage()]);
}
?>