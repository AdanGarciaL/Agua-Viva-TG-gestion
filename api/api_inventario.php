<?php
// api/api_inventario.php - VERSIÓN COMPLETADA
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');

try {
    // Listar (Público para vendedores)
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        echo json_encode(['success' => true, 'productos' => $stmt->fetchAll()]);
    
    // Crear Producto
    } else if ($accion === 'crear' && $esAdmin) {
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock, foto_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nombre'], 
            $_POST['codigo'] ?? '', 
            $_POST['precio'], 
            $_POST['stock'], 
            $_POST['foto'] ?? ''
        ]);
        echo json_encode(['success' => true, 'message' => 'Producto Creado']);

    // Editar Producto (REPARADO: Manejo de ID y campos vacíos)
    } else if ($accion === 'editar' && $esAdmin) {
        $stmt = $conexion->prepare("UPDATE productos SET nombre=?, codigo_barras=?, precio_venta=?, stock=?, foto_url=? WHERE id=?");
        $stmt->execute([
            $_POST['nombre'], 
            $_POST['codigo'] ?? '', 
            $_POST['precio'], 
            $_POST['stock'], 
            $_POST['foto'] ?? '', 
            $_POST['id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Producto Actualizado']);

    // Eliminar (Soft Delete para no romper historial)
    } else if ($accion === 'eliminar' && $esAdmin) {
        $id = $_POST['id'];
        // Intentamos borrar físico primero
        try {
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            // Si falla (tiene ventas), hacemos borrado lógico
            $stmt = $conexion->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>