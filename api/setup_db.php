<?php
// api/setup_db.php
// INSTALADOR AUTOMÁTICO (usa DB_PATH central desde config.php)

// Cargar configuración global para obtener DB_PATH
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';

$db_file = defined('DB_PATH') ? DB_PATH : (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database.sqlite');
$existe = file_exists($db_file);

// Asegurar carpeta
$db_dir = dirname($db_file);
if (!is_dir($db_dir)) @mkdir($db_dir, 0777, true);

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$existe) {
        // Salida amigable pero segura: si se ejecuta por CLI o por navegador
        if (php_sapi_name() === 'cli') {
            echo "Iniciando instalación (CLI)...\n";
        } else {
            echo "<h1>Iniciando Instalación...</h1>";
        }

        // 1. TABLA USUARIOS
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        )");
        // Superadmin: AdanGL / Agl252002
        $passHash = password_hash("Agl252002", PASSWORD_DEFAULT);
        $pdo->exec("INSERT OR IGNORE INTO usuarios (username, password, role) VALUES ('AdanGL', '$passHash', 'superadmin')");

        // 2. TABLA PRODUCTOS
        $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            codigo_barras TEXT,
            precio_venta REAL NOT NULL,
            stock INTEGER NOT NULL,
            foto_url TEXT,
            activo INTEGER DEFAULT 1
        )");

        // Agregar ejemplos mínimos sin depender de recursos externos
        $stmtProd = $pdo->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock, foto_url) VALUES (?, ?, ?, ?, ?)");
        $stmtProd->execute(['Producto Ejemplo', '0000000000000', 100.00, 50, '']);

        // 3. TABLA VENTAS
        $pdo->exec("CREATE TABLE IF NOT EXISTS ventas (
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
        )");

        // 4. TABLA REGISTROS
        $pdo->exec("CREATE TABLE IF NOT EXISTS registros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            tipo TEXT,
            concepto TEXT,
            monto REAL,
            usuario TEXT
        )");

        // 5. TABLA SÉPTIMAS
        $pdo->exec("CREATE TABLE IF NOT EXISTS septimas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            nombre_padrino TEXT,
            monto REAL,
            usuario_registro TEXT,
            pagado INTEGER DEFAULT 0
        )");

        // 6. TABLA LOG ERRORES
        $pdo->exec("CREATE TABLE IF NOT EXISTS log_errores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            error TEXT
        )");

        // 7. TABLA CONFIGURACIÓN
        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
            clave TEXT PRIMARY KEY,
            valor TEXT
        )");
        $pdo->exec("INSERT OR IGNORE INTO configuracion (clave, valor) VALUES ('color_tema', '#0d47a1')");

        // Crear respaldo inicial (silencioso)
        try {
            $backupDir = $db_dir . DIRECTORY_SEPARATOR . 'backups';
            if (!is_dir($backupDir)) @mkdir($backupDir, 0777, true);
            $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'database_init_' . date('Ymd_His') . '.sqlite';
            copy($db_file, $backupFile);
        } catch (Exception $e) {
            @file_put_contents($db_dir . DIRECTORY_SEPARATOR . 'db_errors.log', date('c') . " - backup error: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        if (php_sapi_name() !== 'cli') {
            echo "<br><h2 style='color:green'>¡SISTEMA LISTO! Cierra esta ventana.</h2>";
            echo "<button onclick='window.close()'>Cerrar</button>";
        } else {
            echo "Instalación completada.\n";
        }

    } else {
        if (php_sapi_name() !== 'cli') {
            echo "<h2>La base de datos ya existe.</h2>";
            echo "<p>Para reiniciar y ver los ejemplos, borra el archivo <b>database.sqlite</b> y recarga esta página.</p>";
        } else {
            echo "La base de datos ya existe.\n";
        }
    }

} catch (PDOException $e) {
    // Registrar y devolver error
    @file_put_contents($db_dir . DIRECTORY_SEPARATOR . 'db_errors.log', date('c') . " - PDO Error: " . $e->getMessage() . "\n", FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Error Fatal: " . $e->getMessage() . "\n");
        exit(1);
    } else {
        die("Error Fatal: " . $e->getMessage());
    }
}
?>