<?php
/**
 * healthcheck.php - Verificador de salud del sistema
 * Se ejecuta automáticamente al cargar la app
 * Compatible con PHP Desktop
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$health = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'ok',
    'sapi' => php_sapi_name(),
    'phpdesktop' => (php_sapi_name() === 'cgi' || php_sapi_name() === 'cgi-fcgi'),
    'checks' => []
];

// 1. Verificar BD
try {
    if (!defined('DB_PATH')) {
        require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
    }
    require_once 'db.php';
    
    if (isset($conexion)) {
        try {
            $result = $conexion->query("SELECT 1");
            $health['checks']['database'] = [
                'status' => 'ok',
                'driver' => defined('DB_DRIVER') ? DB_DRIVER : 'unknown',
                'path' => defined('DB_PATH') ? basename(DB_PATH) : 'unknown'
            ];
        } catch (Exception $e) {
            $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $health['status'] = 'degraded';
        }
    } else {
        $health['checks']['database'] = ['status' => 'error', 'message' => 'No connection'];
        $health['status'] = 'error';
    }
} catch (Exception $e) {
    $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
    $health['status'] = 'error';
}

// 2. Verificar archivos críticos
$files = ['error_handler.php', '../assets/js/app.js', 'api_ventas.php'];
foreach ($files as $file) {
    $full_path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    $exists = file_exists($full_path) || file_exists($file);
    $health['checks']["file_" . basename($file)] = ['status' => $exists ? 'ok' : 'missing'];
}

// 3. Verificar directorios de logs
$log_dirs = [
    dirname(DB_PATH),
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data'
];
foreach ($log_dirs as $dir) {
    if (!empty($dir)) {
        $exists = is_dir($dir);
        $writable = $exists && is_writable($dir);
        $health['checks']["dir_" . basename($dir)] = [
            'status' => $writable ? 'ok' : ($exists ? 'not_writable' : 'missing'),
            'exists' => $exists,
            'writable' => $writable
        ];
    }
}

// 4. Información del sistema (solo en PHP Desktop)
if ($health['phpdesktop']) {
    $health['system'] = [
        'php_version' => phpversion(),
        'os' => PHP_OS,
        'db_path' => defined('DB_PATH') ? DB_PATH : 'undefined'
    ];
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health);
exit();
?>
