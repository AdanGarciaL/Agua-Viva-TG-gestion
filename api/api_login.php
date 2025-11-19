<?php
// api/api_login.php
// VERSIÓN FINAL: Producción

session_start();
include 'db.php';

header('Content-Type: application/json');

// Evitar warnings de PHP en la respuesta JSON
error_reporting(0); 

$modo = $_POST['modo'] ?? 'admin';

try {
    if ($modo === 'vendedor') {
        // --- LOGIN VENDEDOR (Simple) ---
        $nombre = trim($_POST['vendedor_nombre'] ?? '');
        $estigma = $_POST['vendedor_estigma'] ?? '';
        $padrino = $_POST['vendedor_padrino'] ?? '';
        
        if (empty($nombre) || empty($estigma) || empty($padrino)) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos del vendedor.']);
            exit();
        }
        
        // Regenerar ID para seguridad de sesión
        session_regenerate_id(true); 
        
        $_SESSION['usuario'] = $nombre;
        $_SESSION['role'] = 'vendedor';
        $_SESSION['vendedor_nombre'] = $nombre;
        $_SESSION['vendedor_padrino'] = $padrino;
        $_SESSION['vendedor_estigma'] = $estigma;
        
        echo json_encode(['success' => true]);

    } else {
        // --- LOGIN ADMIN (Base de Datos) ---
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        if (empty($user) || empty($pass)) {
            echo json_encode(['success' => false, 'message' => 'Campos vacíos.']);
            exit();
        }

        $stmt = $conexion->prepare("SELECT id, username, password, role FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->execute([$user]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data && password_verify($pass, $data['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $data['username'];
            $_SESSION['role'] = $data['role'];
            echo json_encode(['success' => true]);
        } else {
            // Retardo de seguridad anti-bruteforce
            usleep(500000); // 0.5 segundos
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
        }
    }

} catch (Exception $e) {
    // En producción no mostramos el error exacto al usuario, solo "Error interno"
    echo json_encode(['success' => false, 'message' => 'Error del sistema de acceso.']);
}
?>