<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Log pero no mostrar
header('Content-Type: application/json');

// DEBUG: Log de intentos de login
@file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_login.log',
    date('Y-m-d H:i:s') . " [LOGIN] Intento. POST data: " . json_encode($_POST) . "\n",
    FILE_APPEND
);

$modo = $_POST['modo'] ?? 'admin';

try {
    if ($modo === 'vendedor') {
        $nombre = trim($_POST['vendedor_nombre'] ?? '');
        $estigma = $_POST['vendedor_estigma'] ?? '';
        $padrino = $_POST['vendedor_padrino'] ?? '';

        if (empty($nombre) || empty($estigma)) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = $nombre;
        $_SESSION['role'] = 'vendedor';
        $_SESSION['vendedor_nombre'] = $nombre;
        // Generar CSRF token y devolverlo al cliente para futuras peticiones
        include_once 'csrf.php';
        $token = get_csrf_token();
        echo json_encode(['success' => true, 'csrf_token' => $token]);

    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->execute([$user]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $u['username'];
            $_SESSION['role'] = $u['role'];
            include_once 'csrf.php';
            $token = get_csrf_token();
            echo json_encode(['success' => true, 'csrf_token' => $token]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos incorrectos']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error login: ' . $e->getMessage()]);
}
?>