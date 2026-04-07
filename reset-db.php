<?php
/**
 * reset-db.php - Reinicia la base de datos (use solo si está corrupta)
 * Acceso: http://localhost:8080/reset-db.php
 */

require_once 'config.php';

$db_path = DB_PATH;
$backup_dir = dirname($db_path) . DIRECTORY_SEPARATOR . 'backups';

// Crear backup de la BD actual si existe
if (file_exists($db_path)) {
    if (!is_dir($backup_dir)) @mkdir($backup_dir, 0777, true);
    $backup_file = $backup_dir . DIRECTORY_SEPARATOR . 'database_backup_' . date('Ymd_His') . '.sqlite';
    copy($db_path, $backup_file);
    unlink($db_path);
}

// Crear BD nueva
try {
    $conexion = new PDO("sqlite:" . $db_path, '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optimizaciones
    $conexion->exec('PRAGMA foreign_keys = ON');
    $conexion->exec('PRAGMA journal_mode = WAL');
    $conexion->exec('PRAGMA synchronous = NORMAL');
    
    // Crear tablas
    $conexion->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT)");
    
    $pass = password_hash("Agl252002", PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['AdanGL', $pass, 'superadmin']);
    
    $conexion->exec("CREATE TABLE productos (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, codigo_barras TEXT, precio_venta REAL, stock INTEGER, stock_minimo INTEGER DEFAULT 10, activo INTEGER DEFAULT 1)");
    $stmt = $conexion->prepare("INSERT INTO productos (nombre, precio_venta, stock) VALUES (?, ?, ?)");
    $stmt->execute(['Producto Ejemplo', 100, 50]);
    
    $conexion->exec("CREATE TABLE ventas (id INTEGER PRIMARY KEY AUTOINCREMENT, producto_id INTEGER, cantidad INTEGER, total REAL, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, vendedor TEXT, tipo_pago TEXT, nombre_fiado TEXT, fiado_pagado INTEGER DEFAULT 0)");
    
    $conexion->exec("CREATE TABLE registros (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, concepto TEXT, monto REAL, usuario TEXT, categoria TEXT, servicio TEXT)");
    
    $conexion->exec("CREATE TABLE septimas (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, nombre_padrino TEXT, monto REAL, usuario_registro TEXT, pagado INTEGER DEFAULT 0, tipo TEXT DEFAULT 'normal', servicio TEXT)");
    
    $conexion->exec("CREATE TABLE log_errores (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, mensaje TEXT, detalles TEXT, url TEXT)");
    
    $conexion->exec("CREATE TABLE configuracion (clave TEXT PRIMARY KEY, valor TEXT)");
    $stmt = $conexion->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)");
    $stmt->execute(['color_tema', '#0d47a1']);
    
    echo "<pre style='background: #e8f5e9; padding: 20px; font-family: monospace; color: #2e7d32;'>";
    echo "✓ Base de datos reiniciada correctamente\n\n";
    echo "Ubicación: " . $db_path . "\n";
    echo "Usuario: AdanGL\n";
    echo "Contraseña: Agl252002\n\n";
    echo "Puedes cerrar esta ventana y recargar la aplicación.\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre style='background: #ffebee; padding: 20px; font-family: monospace; color: #c62828;'>";
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "</pre>";
}
