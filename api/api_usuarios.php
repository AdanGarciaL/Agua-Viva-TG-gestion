<?php
// api/api_usuarios.php
session_start();
include 'db.php';
header('Content-Type: application/json');

// SEGURIDAD: Solo Superadmin puede entrar
if (!isset($_SESSION['usuario']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'ACCESO DENEGADO.']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';

try {
    // --- LISTAR USUARIOS ---
    if ($accion === 'listar') {
        $stmt = $conexion->query("SELECT id, username, role FROM usuarios ORDER BY role ASC");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
    } 
    
    // --- CREAR USUARIO (SOLO ADMINS) ---
    else if ($accion === 'crear') {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        
        // RESTRICCIÓN FUERTE: 
        // Ignoramos lo que venga del formulario y forzamos 'admin'.
        // Adan G. (Superadmin) solo puede crear 'admin'.
        $role = 'admin'; 

        // Validar usuario
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE username = ?");
        $check->execute([$user]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El usuario ya existe.']);
            exit();
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$user, $hash, $role]);
        
        echo json_encode(['success' => true, 'message' => 'Administrador creado exitosamente.']);
    }
    
    // --- ELIMINAR USUARIO ---
    else if ($accion === 'eliminar') {
        $id = $_POST['id'];
        
        // Evitar auto-eliminación
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
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
}
?>