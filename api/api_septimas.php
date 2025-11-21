<?php
// api/api_septimas.php
session_start();
include 'db.php';
header('Content-Type: application/json');
error_reporting(0);

if (!isset($_SESSION['usuario'])) exit(json_encode(['success'=>false, 'message'=>'No autorizado']));

$accion = $_REQUEST['accion'] ?? '';

try {
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM septimas ORDER BY pagado ASC, fecha DESC LIMIT 50");
        echo json_encode(['success' => true, 'septimas' => $stmt->fetchAll()]);

    } else if ($accion === 'crear') {
        $stmt = $conexion->prepare("INSERT INTO septimas (nombre_padrino, monto, usuario_registro) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['nombre'], $_POST['monto'], $_SESSION['usuario']]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'pagar') {
        // Marcar como pagado
        $id = $_POST['id'];
        $stmt = $conexion->prepare("UPDATE septimas SET pagado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'eliminar') {
        // Eliminar registro
        $id = $_POST['id'];
        $stmt = $conexion->prepare("DELETE FROM septimas WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>