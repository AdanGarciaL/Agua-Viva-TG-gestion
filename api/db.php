<?php
// api/db.php - v4.0

// 1. Headers de Seguridad HTTP
header("X-Frame-Options: DENY"); // Evita Clickjacking
header("X-XSS-Protection: 1; mode=block"); // Filtro XSS navegador
header("X-Content-Type-Options: nosniff"); // Evita MIME sniffing
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Type: application/json; charset=utf-8');

// 2. Configuración de Cookies Seguras (Antes de session_start)
ini_set('session.cookie_httponly', 1); // JS no puede leer la cookie
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // Descomentar si usas HTTPS

// 3. Cargar configuración centralizada que determina la ruta BD persistente
if (!defined('DB_PATH')) {
    require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
}

// DEBUG: Cargar middleware de debug
if (!defined('API_DEBUG_ENABLED')) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api_debug.php';
}

$db_file = DB_PATH;

// DEBUG: Log de rutas críticas
@file_put_contents(dirname($db_file) . DIRECTORY_SEPARATOR . 'db_debug.log', 
    date('Y-m-d H:i:s') . " [db.php] Intentando conectar a: " . $db_file . "\n" .
    "  - File exists: " . (file_exists($db_file) ? 'SÍ' : 'NO') . "\n" .
    "  - Dir writable: " . (is_writable(dirname($db_file)) ? 'SÍ' : 'NO') . "\n" .
    "  - PHP_OS: " . PHP_OS . "\n" .
    "  - getcwd(): " . getcwd() . "\n",
    FILE_APPEND
);

$inicializar = !file_exists($db_file);

try {
    $dsn = "sqlite:" . $db_file;
    $conexion = new PDO($dsn);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // DEBUG: Conexión exitosa
    @file_put_contents(dirname($db_file) . DIRECTORY_SEPARATOR . 'db_debug.log',
        date('Y-m-d H:i:s') . " [db.php] Conexión PDO exitosa.\n",
        FILE_APPEND
    );

    // Optimización y seguridad SQLite
    $conexion->exec('PRAGMA foreign_keys = ON');
    $conexion->exec('PRAGMA journal_mode = WAL');
    $conexion->exec('PRAGMA synchronous = NORMAL');
    
    // CRÍTICO: Activar autocommit explícitamente (PDO debe hacerlo, pero asegurar)
    $conexion->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);

    // Inicialización Automática (Self-Healing)
    if ($inicializar) {
        $conexion->exec("CREATE TABLE IF NOT EXISTS usuarios (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT)");
        $pass = password_hash("Agl252002", PASSWORD_DEFAULT);
        $conexion->exec("INSERT OR IGNORE INTO usuarios (username, password, role) VALUES ('AdanGL', '$pass', 'superadmin')");

        $conexion->exec("CREATE TABLE IF NOT EXISTS productos (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, codigo_barras TEXT, precio_venta REAL, stock INTEGER, foto_url TEXT, activo INTEGER DEFAULT 1)");
        $conexion->exec("INSERT INTO productos (nombre, precio_venta, stock) VALUES ('Producto Ejemplo', 100, 50)");

        $conexion->exec("CREATE TABLE IF NOT EXISTS ventas (id INTEGER PRIMARY KEY AUTOINCREMENT, producto_id INTEGER, cantidad INTEGER, total REAL, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, vendedor TEXT, foto_referencia TEXT, tipo_pago TEXT, nombre_fiado TEXT, fiado_pagado INTEGER DEFAULT 0)");
        $conexion->exec("CREATE TABLE IF NOT EXISTS registros (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, concepto TEXT, monto REAL, usuario TEXT)");
        $conexion->exec("CREATE TABLE IF NOT EXISTS septimas (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, nombre_padrino TEXT, monto REAL, usuario_registro TEXT, pagado INTEGER DEFAULT 0)");
        $conexion->exec("CREATE TABLE IF NOT EXISTS log_errores (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, error TEXT)");
        $conexion->exec("CREATE TABLE IF NOT EXISTS configuracion (clave TEXT PRIMARY KEY, valor TEXT)");
        $conexion->exec("INSERT OR IGNORE INTO configuracion (clave, valor) VALUES ('color_tema', '#0d47a1')");

        // Crear un respaldo inicial para evitar pérdidas
        try {
            $backupDir = dirname($db_file) . DIRECTORY_SEPARATOR . 'backups';
            if (!is_dir($backupDir)) @mkdir($backupDir, 0777, true);
            $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'database_init_' . date('Ymd_His') . '.sqlite';
            copy($db_file, $backupFile);
        } catch (Exception $e) {
            // registrar pero no bloquear
            @file_put_contents(dirname($db_file) . DIRECTORY_SEPARATOR . 'db_errors.log', date('c') . " - backup error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    // Guardar log local
    @file_put_contents(dirname($db_file) . DIRECTORY_SEPARATOR . 'db_errors.log', date('c') . " - PDO Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error Crítico DB: ' . $e->getMessage()]);
    exit();
}

// 3. Generador de Token CSRF (Si hay sesión activa)
if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Helper para registrar errores tanto en archivo como en la tabla 'log_errores' cuando exista
function log_error_db($mensaje) {
    global $conexion;
    $ts = date('Y-m-d H:i:s');
    $txt = "[$ts] " . $mensaje . "\n";
    // Archivo de log local
    @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'db_errors.log', $txt, FILE_APPEND);
    // Intentar insertar en la tabla log_errores si la conexión y la tabla existen
    try {
        if (isset($conexion)) {
            $conexion->exec("INSERT INTO log_errores (fecha, error) VALUES (datetime('now', 'localtime'), '" . str_replace("'", "''", substr($mensaje,0,1000)) . "')");
        }
    } catch (Exception $e) {
        // fallback silencioso
        @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'db_errors.log', date('c') . " - log table write failed: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>