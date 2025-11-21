<?php
// api/api_usuarios.php
// VERSIÓN FINAL: Producción

session_start();
include 'db.php';
header('Content-Type: application/json');
error_reporting(0);

// SEGURIDAD TOTAL: Solo Superadmin entra aquí
if (!isset($_SESSION['usuario']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';

try {
    // --- 1. LISTAR USUARIOS ---
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT id, username, role FROM usuarios ORDER BY role ASC");
        echo json_encode(['success' => true, 'usuarios' => $stmt->fetchAll()]);
        exit();
    } 
    
    // --- 2. CREAR USUARIO (Solo Admins) ---
    if ($accion === 'crear') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        
        if (empty($user) || empty($pass)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit();
        }

        // RESTRICCIÓN DE SEGURIDAD:
        // Forzamos el rol a 'admin'. Nadie puede crear superadmins por aquí.
        $role = 'admin'; 

        // Verificar duplicados
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE username = ?");
        $check->execute([$user]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El usuario ya existe.']);
            exit();
        }

        // Crear
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$user, $hash, $role]);
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    // --- 3. ELIMINAR USUARIO ---
    if ($accion === 'eliminar') {
        $id = $_POST['id'];
        
        // Evitar auto-suicidio (No puedes borrarte a ti mismo)
        $stmtCheck = $conexion->prepare("SELECT username FROM usuarios WHERE id = ?");
        $stmtCheck->execute([$id]);
        $u = $stmtCheck->fetch();
        
        if ($u && $u['username'] === $_SESSION['usuario']) {
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta.']);
            exit();
        }

        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error usuarios: ' . $e->getMessage()]);
}
?>