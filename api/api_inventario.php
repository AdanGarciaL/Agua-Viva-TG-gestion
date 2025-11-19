<?php
// api_inventario.php
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

// PERMISO: Admin y Superadmin pueden modificar
$esAdmin = ($role === 'admin' || $role === 'superadmin');

try {
    // LISTAR: Solo mostramos los productos ACTIVOS (activo = 1)
    if ($accion === 'listar') {
        // Verificamos si existe la columna activo, si no, listamos todo (fallback)
        try {
            $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        } catch (PDOException $e) {
            // Si falla (por si no corriste el script), carga todo normal
            $stmt = $conexion->query("SELECT * FROM productos ORDER BY nombre ASC");
        }
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'productos' => $productos]);
    
    } else if ($accion === 'crear' && $esAdmin) {
        $foto_url = (!empty($_POST['foto'])) ? $_POST['foto'] : null;
        // Por defecto activo = 1
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock, foto_url, activo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['codigo'] ?? null,
            $_POST['precio'],
            $_POST['stock'],
            $foto_url
        ]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'editar' && $esAdmin) {
        $foto_url = (!empty($_POST['foto'])) ? $_POST['foto'] : null;
        $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, codigo_barras = ?, precio_venta = ?, stock = ?, foto_url = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['codigo'] ?? null,
            $_POST['precio'],
            $_POST['stock'],
            $foto_url,
            $_POST['id']
        ]);
        echo json_encode(['success' => true]);

    } else if ($accion === 'eliminar' && $esAdmin) {
        // *** CAMBIO IMPORTANTE: ELIMINACIÓN LÓGICA ***
        // En lugar de DELETE, hacemos UPDATE activo = 0
        $id = $_POST['id'];
        
        // Primero intentamos DELETE normal (por si es nuevo y no tiene ventas)
        try {
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Producto eliminado físicamente.']);
        } catch (PDOException $e) {
            // Si falla por restricción (Error 23000/1451), hacemos Soft Delete
            if ($e->getCode() == '23000') {
                $stmt = $conexion->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Producto archivado (tenía ventas asociadas).']);
            } else {
                throw $e; // Otro error
            }
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida o no autorizada']);
    }

} catch (PDOException $e) {
    logError("Error en API Inventario: " . $e->getMessage(), $conexion);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>