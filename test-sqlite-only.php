<?php
/**
 * test-sqlite-only.php - Verificar que SQLite funciona correctamente
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar config
    require_once 'config.php';
    
    // Cargar BD
    require_once 'api/db.php';
    
    $result = [
        'status' => 'OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'config' => [
            'driver' => $config['db']['driver'] ?? 'unknown',
            'offline' => true
        ],
        'database' => [
            'driver' => DB_DRIVER,
            'path' => DB_PATH,
            'exists' => file_exists(DB_PATH),
            'writable' => is_writable(dirname(DB_PATH))
        ],
        'connection' => [
            'connected' => isset($conexion) && $conexion ? true : false,
            'tipo' => isset($conexion) && $conexion ? 'sqlite' : 'none'
        ]
    ];
    
    // Test query
    if ($conexion) {
        $test = $conexion->query("SELECT 1 as test")->fetch();
        $result['test_query'] = $test ? 'OK' : 'FAILED';
        
        // Contar tablas
        $tables = $conexion->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $result['tables_count'] = count($tables);
        $result['tables'] = $tables;
    }
    
    http_response_code(200);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
