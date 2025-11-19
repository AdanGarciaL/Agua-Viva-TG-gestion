<?php
// api_admin.php
session_start();
include 'db.php';
header('Content-Type: application/json');

// PERMISO ACTUALIZADO: Solo Superadmin
if (!isset($_SESSION['usuario']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado. Se requiere nivel SuperAdmin.']);
    exit();
}

$accion = $_GET['accion'] ?? '';

try {
    if ($accion === 'ver_errores') {
        $stmt = $conexion->query("SELECT * FROM log_errores ORDER BY fecha DESC LIMIT 100");
        $errores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'errores' => $errores]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>