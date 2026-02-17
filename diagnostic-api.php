<?php
/**
 * diagnostic-api.php - API para recopilar info de diagnóstico
 */
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'is_php_desktop' => defined('IS_PHP_DESKTOP') ? IS_PHP_DESKTOP : false,
    'db_driver' => DB_DRIVER ?? 'unknown',
    'db_path' => DB_PATH ?? 'unknown',
    'db_exists' => file_exists(DB_PATH ?? 'unknown'),
    'db_connected' => false,
    'usuarios_exists' => false,
    'user_count' => 0,
    'has_admin' => false,
    'logs' => []
];

try {
    require_once 'api/db.php';
    
    if ($conexion) {
        $response['db_connected'] = true;
        
        // Check usuarios table
        $check = $conexion->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
        $response['usuarios_exists'] = $check['cnt'] > 0;
        
        if ($response['usuarios_exists']) {
            $count = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios")->fetch();
            $response['user_count'] = $count['cnt'] ?? 0;
            
            $admin = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios WHERE username='AdanGL'")->fetch();
            $response['has_admin'] = $admin['cnt'] > 0;
        }
    }
    
} catch (Exception $e) {
    $response['db_connected'] = false;
    $response['error'] = $e->getMessage();
}

// Cargar logs
$log_files = [
    dirname(DB_PATH) . '/launcher.log',
    dirname(DB_PATH) . '/db_errors.log',
    dirname(DB_PATH) . '/api_login.log'
];

foreach ($log_files as $file) {
    if (file_exists($file)) {
        $lines = file($file);
        $lines = array_slice($lines, -20); // Últimas 20 líneas
        $response['logs'] = array_merge($response['logs'], array_map('trim', $lines));
    }
}

echo json_encode($response);
?>