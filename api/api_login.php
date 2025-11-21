<?php
session_start();
include 'db.php';
error_reporting(0);
header('Content-Type: application/json');

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
        
        echo json_encode(['success' => true]);

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
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos incorrectos']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error login: ' . $e->getMessage()]);
}
?>