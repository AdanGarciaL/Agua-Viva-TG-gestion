<?php
// api_debug.php - Middleware de debug para rastrear todas las operaciones
// Incluir esto AL INICIO de cada api_*.php

if (!defined('API_DEBUG_ENABLED')) {
    define('API_DEBUG_ENABLED', true);
    
    // IMPORTANTE: DB_PATH debe estar definido antes de esto
    // Normalizar path
    if (!defined('DB_PATH')) {
        define('DB_PATH', getenv('DB_PATH') ?: sys_get_temp_dir() . '/TG_Gestion_debug.log');
    }
    
    // Crear archivo de debug global
    $debug_log = dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_requests.log';
    
    // Registrar entrada
    $entry = [
        'timestamp' => date('Y-m-d H:i:s.u'),
        'php_sapi' => php_sapi_name(),
        'script' => basename($_SERVER['SCRIPT_FILENAME'] ?? 'unknown'),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST,
        'session' => $_SESSION ?? [],
    ];
    
    @file_put_contents($debug_log, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
}
?>
