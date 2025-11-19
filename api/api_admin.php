<?php
// api/api_admin.php
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

try {
    // --- 1. VER ERRORES (Solo Superadmin) ---
    if ($accion === 'ver_errores') {
        if ($_SESSION['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit();
        }
        
        $stmt = $conexion->query("SELECT * FROM log_errores ORDER BY id DESC LIMIT 100");
        echo json_encode(['success' => true, 'errores' => $stmt->fetchAll()]);
        exit();
    }

    // --- 2. OBTENER COLOR (Para pintar la interfaz al inicio) ---
    if ($accion === 'get_color') {
        $stmt = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'color_tema'");
        $res = $stmt->fetch();
        $color = $res ? $res['valor'] : '#0d47a1'; // Azul default
        echo json_encode(['success' => true, 'color' => $color]);
        exit();
    }

    // --- 3. GUARDAR COLOR (Solo Superadmin) ---
    if ($accion === 'save_color') {
        if ($_SESSION['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Solo Superadmin cambia la configuración.']);
            exit();
        }
        
        $nuevoColor = $_POST['color'];
        // Usamos REPLACE o UPDATE para asegurar que se guarde
        $stmt = $conexion->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES ('color_tema', ?)");
        $stmt->execute([$nuevoColor]);
        
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error admin: ' . $e->getMessage()]);
}
?>