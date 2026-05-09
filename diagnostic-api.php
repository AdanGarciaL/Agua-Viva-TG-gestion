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
    'db_exists' => (defined('DB_PATH') && is_string(DB_PATH) && DB_PATH !== '') ? file_exists(DB_PATH) : false,
    'db_connected' => false,
    'usuarios_exists' => false,
    'user_count' => 0,
    'has_admin' => false,
    'admin_has_password' => false,
    'logs' => []
];

try {
    require_once 'api/db.php';
    
    if ($conexion instanceof PDO) {
        $response['db_connected'] = true;
        $response['db_exists'] = true; // Si conecta, la BD es accesible
        
        // Check usuarios table
        $checkStmt = $conexion->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='usuarios'");
        $check = $checkStmt ? $checkStmt->fetch(PDO::FETCH_ASSOC) : ['cnt' => 0];
        $response['usuarios_exists'] = (int)($check['cnt'] ?? 0) > 0;
        
        if ($response['usuarios_exists']) {
            $countStmt = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios");
            $count = $countStmt ? $countStmt->fetch(PDO::FETCH_ASSOC) : ['cnt' => 0];
            $response['user_count'] = (int)($count['cnt'] ?? 0);
            
            $adminStmt = $conexion->prepare("SELECT username, password FROM usuarios WHERE LOWER(username)=LOWER(?) LIMIT 1");
            $adminStmt->execute(['AdanGL']);
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
            $response['has_admin'] = !empty($admin);
            $response['admin_has_password'] = !empty($admin['password']);
        }
    }
    
} catch (Exception $e) {
    $response['db_connected'] = false;
    $response['db_exists'] = false;
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
