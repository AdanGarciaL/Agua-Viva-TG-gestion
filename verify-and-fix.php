<?php
/**
 * verify-and-fix.php - Verificar y reparar BD
 * Acceso: http://localhost:8080/verify-and-fix.php
 */
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [],
    'actions' => [],
    'final_status' => 'PENDING'
];

// 1. Verificar si DB_PATH existe
$response['checks']['db_path'] = DB_PATH;
$response['checks']['db_exists'] = file_exists(DB_PATH);

// 2. Crear BD si no existe
if (!file_exists(DB_PATH)) {
    $response['actions'][] = 'Creando BD...';
}

try {
    // Conectar/crear BD
    $conexion = new PDO("sqlite:" . DB_PATH, '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optimizaciones
    $conexion->exec('PRAGMA foreign_keys = ON');
    $conexion->exec('PRAGMA journal_mode = WAL');
    $conexion->exec('PRAGMA synchronous = NORMAL');
    
    $response['checks']['db_connected'] = true;
    
    // 3. Verificar tabla usuarios
    $check_table = $conexion->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
    $usuarios_exists = $check_table['cnt'] > 0;
    $response['checks']['usuarios_table_exists'] = $usuarios_exists;
    
    if (!$usuarios_exists) {
        $response['actions'][] = 'Creando tabla usuarios...';
        $conexion->exec("CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        )");
        $response['actions'][] = 'Tabla usuarios creada ✓';
    }
    
    // 4. Verificar usuario AdanGL
    $check_user = $conexion->query("SELECT * FROM usuarios WHERE username='AdanGL' LIMIT 1")->fetch();
    $response['checks']['user_exists'] = $check_user ? true : false;
    
    if (!$check_user) {
        $response['actions'][] = 'Creando usuario AdanGL...';
        $pass_hash = password_hash("Agl252002", PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $result = $stmt->execute(['AdanGL', $pass_hash, 'superadmin']);
        $response['actions'][] = 'Usuario AdanGL creado ✓';
    } else {
        $response['checks']['user_role'] = $check_user['role'];
        $response['actions'][] = 'Usuario AdanGL ya existe ✓';
    }
    
    // 5. Crear otras tablas si no existen
    $tables_sql = [
        'productos' => "CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT,
            codigo_barras TEXT,
            precio_venta REAL,
            stock INTEGER,
            stock_minimo INTEGER DEFAULT 10,
            foto_url TEXT,
            activo INTEGER DEFAULT 1
        )",
        'ventas' => "CREATE TABLE IF NOT EXISTS ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            producto_id INTEGER,
            cantidad INTEGER,
            total REAL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            vendedor TEXT,
            foto_referencia TEXT,
            tipo_pago TEXT,
            nombre_fiado TEXT,
            fiado_pagado INTEGER DEFAULT 0
        )",
        'registros' => "CREATE TABLE IF NOT EXISTS registros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            tipo TEXT,
            concepto TEXT,
            monto REAL,
            usuario TEXT,
            categoria TEXT,
            servicio TEXT
        )",
        'septimas' => "CREATE TABLE IF NOT EXISTS septimas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            nombre_padrino TEXT,
            monto REAL,
            usuario_registro TEXT,
            pagado INTEGER DEFAULT 0,
            tipo TEXT DEFAULT 'normal',
            servicio TEXT
        )",
        'log_errores' => "CREATE TABLE IF NOT EXISTS log_errores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            tipo TEXT,
            mensaje TEXT,
            detalles TEXT,
            url TEXT
        )",
        'configuracion' => "CREATE TABLE IF NOT EXISTS configuracion (
            clave TEXT PRIMARY KEY,
            valor TEXT
        )"
    ];
    
    foreach ($tables_sql as $table_name => $sql) {
        try {
            $conexion->exec($sql);
            $response['checks'][$table_name . '_table'] = 'OK';
        } catch (Exception $e) {
            $response['checks'][$table_name . '_table'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // 6. Verificación final
    $final_check = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios WHERE username='AdanGL'")->fetch();
    
    if ($final_check['cnt'] > 0) {
        $response['final_status'] = 'SUCCESS ✓';
        $response['actions'][] = '════════════════════════════════════════';
        $response['actions'][] = 'BASE DE DATOS REPARADA Y LISTA';
        $response['actions'][] = 'Usuario: AdanGL';
        $response['actions'][] = 'Contraseña: Agl252002';
        $response['actions'][] = 'Recarga la página ahora (F5)';
        $response['actions'][] = '════════════════════════════════════════';
    } else {
        $response['final_status'] = 'FAILED - Usuario no se pudo crear';
    }
    
} catch (Exception $e) {
    $response['final_status'] = 'ERROR: ' . $e->getMessage();
    $response['error_details'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

http_response_code($response['final_status'] === 'SUCCESS ✓' ? 200 : 500);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
