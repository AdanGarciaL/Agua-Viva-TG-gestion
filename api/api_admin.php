<?php
// api/api_admin.php
// VERSIÓN v4.0

session_start();
include 'db.php';
header('Content-Type: application/json');
error_reporting(0);

// Validación básica de sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';
$esSuperAdmin = ($_SESSION['role'] === 'superadmin');

try {
    // --- 1. OBTENER COLOR (Acceso libre para pintar la interfaz) ---
    if ($accion === 'get_color') {
        // Buscamos el color, si no existe, devolvemos el azul default
        $stmt = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'color_tema'");
        $res = $stmt->fetch();
        $color = $res ? $res['valor'] : '#0d47a1'; 
        echo json_encode(['success' => true, 'color' => $color]);
        exit();
    }

    // --- 2. VER ERRORES (Solo Superadmin) ---
    if ($accion === 'ver_errores') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        $stmt = $conexion->query("SELECT * FROM log_errores ORDER BY id DESC LIMIT 50");
        echo json_encode(['success' => true, 'errores' => $stmt->fetchAll()]);
        exit();
    }

    // --- 3. GUARDAR COLOR (Solo Superadmin) ---
    if ($accion === 'save_color') {
        if (!$esSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Solo Superadmin cambia la configuración.']);
            exit();
        }
        
        $nuevoColor = $_POST['color'];
        // Guardar o Actualizar
        $stmt = $conexion->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES ('color_tema', ?)");
        $stmt->execute([$nuevoColor]);
        
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error admin: ' . $e->getMessage()]);
}
?>