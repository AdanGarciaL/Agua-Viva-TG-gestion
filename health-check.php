<?php
/**
 * health-check.php - Verificación completa del sistema
 * Acceso: http://localhost:8080/health-check.php
 */
header('Content-Type: application/json; charset=utf-8');

$checks = [];

// 1. PHP Version
$checks['php_version'] = [
    'name' => 'PHP Version',
    'value' => phpversion(),
    'ok' => version_compare(phpversion(), '7.2.0', '>=')
];

// 2. Config file exists
$config_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';
$checks['config_file'] = [
    'name' => 'Config File',
    'ok' => file_exists($config_path),
    'path' => $config_path
];

// 3. Load config
if (file_exists($config_path)) {
    require_once $config_path;
    
    // 4. DB Path
    $checks['db_path'] = [
        'name' => 'Database Path',
        'value' => DB_PATH ?? 'UNDEFINED',
        'ok' => defined('DB_PATH')
    ];
    
    // 5. DB File exists
    $db_exists = file_exists(DB_PATH);
    $checks['db_file'] = [
        'name' => 'Database File',
        'exists' => $db_exists,
        'writable' => $db_exists && is_writable(DB_PATH),
        'ok' => $db_exists
    ];
    
    // 6. Try connection
    try {
        $conexion = new PDO("sqlite:" . DB_PATH, '', '', [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conexion->exec('PRAGMA foreign_keys = ON');
        
        $checks['db_connection'] = [
            'name' => 'Database Connection',
            'ok' => true
        ];
        
        // 7. Tables
        $tables = ['usuarios', 'productos', 'ventas', 'registros', 'septimas'];
        foreach ($tables as $table) {
            $result = $conexion->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
            $checks['table_' . $table] = [
                'name' => "Table: $table",
                'ok' => $result['cnt'] > 0
            ];
        }
        
        // 8. Default user
        $user_check = $conexion->query("SELECT * FROM usuarios WHERE username='AdanGL' LIMIT 1")->fetch();
        $checks['default_user'] = [
            'name' => 'Default User (AdanGL)',
            'exists' => $user_check ? true : false,
            'ok' => $user_check ? true : false
        ];
        
        if ($user_check) {
            $checks['default_user']['has_password'] = !empty($user_check['password']);
            $checks['default_user']['role'] = $user_check['role'];
        }
        
        // 9. User count
        $count = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios")->fetch();
        $checks['user_count'] = [
            'name' => 'Total Users',
            'count' => $count['cnt'],
            'ok' => $count['cnt'] > 0
        ];
        
    } catch (Exception $e) {
        $checks['db_connection'] = [
            'name' => 'Database Connection',
            'ok' => false,
            'error' => $e->getMessage()
        ];
    }
}

// 10. Required directories
$required_dirs = [
    'api' => dirname(__FILE__) . '/api',
    'assets' => dirname(__FILE__) . '/assets',
    'data' => dirname(__FILE__) . '/data'
];

foreach ($required_dirs as $name => $path) {
    $checks['dir_' . $name] = [
        'name' => "Directory: $name",
        'exists' => is_dir($path),
        'ok' => is_dir($path)
    ];
}

// 11. Key files
$key_files = [
    'index.php' => dirname(__FILE__) . '/index.php',
    'launcher.php' => dirname(__FILE__) . '/launcher.php',
    'ping.php' => dirname(__FILE__) . '/ping.php',
    'api/db.php' => dirname(__FILE__) . '/api/db.php',
    'api/api_login.php' => dirname(__FILE__) . '/api/api_login.php'
];

foreach ($key_files as $name => $path) {
    $checks['file_' . str_replace('/', '_', $name)] = [
        'name' => "File: $name",
        'exists' => file_exists($path),
        'ok' => file_exists($path)
    ];
}

// Calculate overall status
$all_ok = true;
foreach ($checks as $check) {
    if (!($check['ok'] ?? false)) {
        $all_ok = false;
        break;
    }
}

$response = [
    'status' => $all_ok ? 'OK' : 'FAILED',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks
];

http_response_code($all_ok ? 200 : 503);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
