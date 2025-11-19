<?php
// api_septimas.php
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

// PERMISOS: Admin y Superadmin pueden modificar
$esAdmin = ($role === 'admin' || $role === 'superadmin');

// Vendedor NO puede acceder a esta API
if (!$esAdmin) {
    echo json_encode(['success' => false, 'message' => 'No autorizado para esta acción.']);
    exit();
}

try {
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM septimas ORDER BY pagado ASC, fecha DESC LIMIT 100");
        $septimas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'septimas' => $septimas]);
    
    } else if ($accion === 'crear') {
        $stmt = $conexion->prepare("INSERT INTO septimas (nombre_padrino, monto, usuario_registro) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['monto'],
            $usuario
        ]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'editar') {
        $stmt = $conexion->prepare("UPDATE septimas SET nombre_padrino = ?, monto = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['monto'],
            $_POST['id']
        ]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'pagar') {
        $stmt = $conexion->prepare("UPDATE septimas SET pagado = 1 WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'eliminar') {
        $stmt = $conexion->prepare("DELETE FROM septimas WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (PDOException $e) {
    logError("Error en API Séptimas: ". $e->getMessage(), $conexion);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>