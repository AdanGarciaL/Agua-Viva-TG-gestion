<?php
// api_registros.php
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

// PERMISO ACTUALIZADO: Admin y Superadmin pueden modificar
$esAdmin = ($role === 'admin' || $role === 'superadmin');

try {
    // Todos los roles pueden listar
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM registros ORDER BY fecha DESC LIMIT 100");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'registros' => $registros]);
    
    } else if ($accion === 'crear' && $esAdmin) {
        $stmt = $conexion->prepare("INSERT INTO registros (tipo, concepto, monto, usuario) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['tipo'],
            $_POST['concepto'],
            $_POST['monto'],
            $usuario
        ]);
        echo json_encode(['success' => true]);

    // NUEVO: Editar y Eliminar Registros
    } else if ($accion === 'eliminar' && $esAdmin) {
        $stmt = $conexion->prepare("DELETE FROM registros WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['success' => true]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida o no autorizada']);
    }

} catch (PDOException $e) {
    logError("Error en API Registros: ". $e->getMessage(), $conexion);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>