<?php
/**
 * ping.php - Health check del sistema
 * Verifica que la aplicación está lista para usar
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

$response = [
    'ok' => false,
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Cargar configuración
    require_once 'config.php';
    
    // Verificar DB_PATH
    if (!defined('DB_PATH')) {
        $response['ok'] = false;
        $response['error'] = 'DB_PATH not defined';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // Esperar a que exista la BD
    $wait = 0;
    while (!file_exists(DB_PATH) && $wait < 10) {
        usleep(500000);
        $wait++;
    }
    
    if (!file_exists(DB_PATH)) {
        $response['ok'] = false;
        $response['error'] = 'Database file not found';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // Conectar a BD
    $db = new PDO("sqlite:" . DB_PATH, '', '', [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Test conexión
    $test = $db->query("SELECT 1")->fetch();
    if (!$test) {
        throw new Exception('DB connection test failed');
    }
    
    // Verificar tabla usuarios
    $tables = $db->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
    if (!$tables || $tables['cnt'] == 0) {
        throw new Exception('usuarios table not found');
    }
    
    // Verificar usuario admin
    $user = $db->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL' LIMIT 1")->fetch();
    if (!$user) {
        throw new Exception('Default user not found');
    }
    
    // Todo OK
    $response['ok'] = true;
    $response['status'] = 'ready';
    $response['user'] = $user['username'];
    $response['role'] = $user['role'];
    
    http_response_code(200);
    
} catch (Exception $e) {
    $response['ok'] = false;
    $response['error'] = $e->getMessage();
    http_response_code(503);
}

echo json_encode($response);
?>
