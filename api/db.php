<?php
// api/db.php - v4.0

// 1. Headers de Seguridad HTTP
header("X-Frame-Options: DENY"); // Evita Clickjacking
header("X-XSS-Protection: 1; mode=block"); // Filtro XSS navegador
header("X-Content-Type-Options: nosniff"); // Evita MIME sniffing
header("Referrer-Policy: strict-origin-when-cross-origin");
header('Content-Type: application/json; charset=utf-8');

// 2. Configuración de Cookies Seguras (Antes de session_start)
ini_set('session.cookie_httponly', 1); // JS no puede leer la cookie
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // Descomentar si usas HTTPS

$db_file = __DIR__ . '/database.sqlite';
$inicializar = !file_exists($db_file);

try {
    $conexion = new PDO("sqlite:$db_file");
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
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
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error Crítico DB: ' . $e->getMessage()]);
    exit();
}

// 3. Generador de Token CSRF (Si hay sesión activa)
if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
?>