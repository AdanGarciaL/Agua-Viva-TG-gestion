<?php
/**
 * ping.php - Verificar que la aplicación está lista
 * VERSIÓN BLINDADA - Garantiza que todo esté listo
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$response = [
    'ok' => false,
    'timestamp' => date('Y-m-d H:i:s'),
    'details' => []
];

try {
    require_once 'config.php';
    
    // Verificar que DB_PATH está definido
    if (!defined('DB_PATH')) {
        $response['details'][] = 'DB_PATH not defined';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // Esperar a que el archivo de BD exista (máximo 5 segundos)
    $wait_count = 0;
    while (!file_exists(DB_PATH) && $wait_count < 10) {
        usleep(500000); // 0.5 segundos
        $wait_count++;
    }
    
    if (!file_exists(DB_PATH)) {
        $response['details'][] = 'Database file does not exist - iniciando creación';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // Conectar a BD
    $conexion = new PDO("sqlite:" . DB_PATH, '', '', [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Test de conexión
    $test = $conexion->query("SELECT 1")->fetch();
    if (!$test) {
        $response['details'][] = 'Database connection test failed';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // Verificar tabla usuarios
    $users_table = $conexion->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
    if ($users_table['cnt'] == 0) {
        $response['details'][] = 'usuarios table does not exist';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // Verificar usuario AdanGL
    $admin_user = $conexion->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL' LIMIT 1")->fetch();
    if (!$admin_user) {
        $response['details'][] = 'Default user (AdanGL) does not exist';
        http_response_code(503);
        echo json_encode($response);
        exit;
    }
    
    // ✓ TODO LISTO
    $response['ok'] = true;
    $response['db'] = 'sqlite';
    $response['db_path'] = DB_PATH;
    $response['users_table_exists'] = true;
    $response['default_user'] = $admin_user['username'];
    $response['user_role'] = $admin_user['role'];
    $response['details'][] = 'Application ready ✓';
    
    http_response_code(200);
    
} catch (Exception $e) {
    $response['ok'] = false;
    $response['error'] = $e->getMessage();
    $response['details'][] = 'Exception: ' . $e->getMessage();
    http_response_code(503);
}

echo json_encode($response);