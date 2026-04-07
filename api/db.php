<?php
// api/db.php - v5.1 OFFLINE - SQLite ONLY

// CRÍTICO: NO generar ningún output antes de headers JSON
error_reporting(E_ALL);
ini_set('display_errors', '0'); // NUNCA mostrar errores en output
ini_set('log_errors', '1');

// NO HEADERS AQUÍ - pueden causar problemas con descargas
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
}

// Load config
if (!defined('DB_PATH')) {
    require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
}

if (!defined('API_DEBUG_ENABLED')) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api_debug.php';
}

$db_file = DB_PATH;
// FORZAR SQLite en modo offline/PHP Desktop
$db_driver = 'sqlite';
define('DB_DRIVER', 'sqlite');

$log_dir = dirname($db_file);
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}

$conexion = null;
$inicializar = false;

// SOLO SQLite - Sin intentos de MySQL
try {
    $dsn = "sqlite:" . $db_file;
    $conexion = new PDO($dsn, '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $inicializar = !file_exists($db_file);
    
    // Optimizar SQLite
    $conexion->exec('PRAGMA foreign_keys = ON');
    $conexion->exec('PRAGMA journal_mode = WAL');
    $conexion->exec('PRAGMA synchronous = NORMAL');
    $conexion->exec('PRAGMA temp_store = MEMORY');

    // Compatibilidad: asegurar esquema clave aun en BD antiguas
    $conexion->exec("CREATE TABLE IF NOT EXISTS cuentas (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre_cuenta TEXT UNIQUE NOT NULL, celular TEXT, estado_cuenta TEXT DEFAULT 'activo', saldo_total REAL DEFAULT 0, fecha_primer_compra DATETIME, fecha_ultimo_compra DATETIME, fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP, notas TEXT)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS cortes_caja (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_cierre DATETIME, usuario_apertura TEXT, usuario_cierre TEXT, saldo_inicial REAL DEFAULT 0, saldo_final REAL DEFAULT 0, ingresos_efectivo REAL DEFAULT 0, ingresos_tarjeta REAL DEFAULT 0, ingresos_transferencia REAL DEFAULT 0, egresos REAL DEFAULT 0, diferencia REAL DEFAULT 0, estado TEXT DEFAULT 'abierto', notas TEXT)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS confirmacion_pagos (id INTEGER PRIMARY KEY AUTOINCREMENT, venta_id INTEGER, metodo_pago TEXT, comprobante_referencia TEXT, estado TEXT DEFAULT 'pendiente', fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_confirmacion DATETIME, usuario_confirmo TEXT, notas TEXT)");

    try {
        $cols = $conexion->query("PRAGMA table_info(ventas)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_map(function ($c) { return strtolower($c['name'] ?? ''); }, $cols);
        if (!in_array('grupo_fiado', $names, true)) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN grupo_fiado TEXT");
        }
        if (!in_array('celular_fiado', $names, true)) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN celular_fiado TEXT");
        }
        if (!in_array('fiado_pagado', $names, true)) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN fiado_pagado INTEGER DEFAULT 0");
        }
    } catch (Exception $schemaEx) {
        @file_put_contents($log_dir . '/db_errors.log', date('Y-m-d H:i:s') . " - Schema upgrade warning: " . $schemaEx->getMessage() . "\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    error_log("[DB] SQLite Error: " . $e->getMessage());
    @file_put_contents($log_dir . '/db_errors.log', 
        date('Y-m-d H:i:s') . " SQLite Error: " . $e->getMessage() . "\n", FILE_APPEND);
    die(json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ]));
}

// Self-healing initialization - SQLITE ONLY
if ($conexion && $inicializar) {
    try {
        // Crear todas las tablas SQLite
        $conexion->exec("CREATE TABLE IF NOT EXISTS usuarios (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT)");
        
        // Insertar usuario por defecto - GARANTIZADO
        $pass = password_hash("Agl252002", PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT OR IGNORE INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['AdanGL', $pass, 'superadmin']);
        
        // Verificar que se creó el usuario
        $verify_user = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios WHERE username='AdanGL'")->fetch();
        if ($verify_user['cnt'] == 0) {
            // Si no existe, intentar insertar sin IGNORE para forzar creación
            $stmt2 = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
            $stmt2->execute(['AdanGL', $pass, 'superadmin']);
        }
        @file_put_contents($log_dir . '/db_errors.log', 
            date('Y-m-d H:i:s') . " - Usuario AdanGL creado/verificado exitosamente\n", FILE_APPEND);

        $conexion->exec("CREATE TABLE IF NOT EXISTS productos (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, codigo_barras TEXT, precio_venta REAL, stock INTEGER, stock_minimo INTEGER DEFAULT 10, tipo_producto TEXT DEFAULT 'producto', activo INTEGER DEFAULT 1)");
        
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, precio_venta, stock) VALUES (?, ?, ?)");
        $stmt->execute(['Producto Ejemplo', 100, 50]);

        $conexion->exec("CREATE TABLE IF NOT EXISTS ventas (id INTEGER PRIMARY KEY AUTOINCREMENT, producto_id INTEGER, cantidad INTEGER, total REAL, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, vendedor TEXT, tipo_pago TEXT, nombre_fiado TEXT, grupo_fiado TEXT, fiado_pagado INTEGER DEFAULT 0)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS registros (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, concepto TEXT, monto REAL, usuario TEXT, categoria TEXT, servicio TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS septimas (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, nombre_padrino TEXT, monto REAL, usuario_registro TEXT, pagado INTEGER DEFAULT 0, tipo TEXT DEFAULT 'normal', servicio TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS log_errores (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, mensaje TEXT, detalles TEXT, url TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, usuario TEXT, accion TEXT, tabla TEXT, registro_id INTEGER, datos_anteriores TEXT, datos_nuevos TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS cuentas (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre_cuenta TEXT UNIQUE NOT NULL, celular TEXT, estado_cuenta TEXT DEFAULT 'activo', saldo_total REAL DEFAULT 0, fecha_primer_compra DATETIME, fecha_ultimo_compra DATETIME, fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP, notas TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS confirmacion_pagos (id INTEGER PRIMARY KEY AUTOINCREMENT, venta_id INTEGER, metodo_pago TEXT, comprobante_referencia TEXT, estado TEXT DEFAULT 'pendiente', fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_confirmacion DATETIME, usuario_confirmo TEXT, notas TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS cortes_caja (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_cierre DATETIME, usuario_apertura TEXT, usuario_cierre TEXT, saldo_inicial REAL DEFAULT 0, saldo_final REAL DEFAULT 0, ingresos_efectivo REAL DEFAULT 0, ingresos_tarjeta REAL DEFAULT 0, ingresos_transferencia REAL DEFAULT 0, egresos REAL DEFAULT 0, diferencia REAL DEFAULT 0, estado TEXT DEFAULT 'abierto', notas TEXT)");
        
        $conexion->exec("CREATE TABLE IF NOT EXISTS configuracion (clave TEXT PRIMARY KEY, valor TEXT)");
        
        $stmt = $conexion->prepare("INSERT OR IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
        $stmt->execute(['color_tema', '#0d47a1']);
        
        // Crear backup de BD inicial
        try {
            $backupDir = $log_dir . DIRECTORY_SEPARATOR . 'backups';
            if (!is_dir($backupDir)) {
                @mkdir($backupDir, 0777, true);
            }
            $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'database_init_' . date('Ymd_His') . '.sqlite';
            copy($db_file, $backupFile);
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - BD inicializada correctamente\n", FILE_APPEND);
        } catch (Exception $be) {
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Backup error: " . $be->getMessage() . "\n", FILE_APPEND);
        }
        
    } catch (Exception $init_error) {
        error_log("[DB] Init error: " . $init_error->getMessage());
        @file_put_contents($log_dir . '/db_errors.log', 
            date('Y-m-d H:i:s') . " - Init error: " . $init_error->getMessage() . "\n", FILE_APPEND);
        // NO die aquí - permitir que continúe incluso con error
    }
}

// MIGRACIÓN AUTOMÁTICA: Agregar columnas faltantes si no existen (para BDs antiguas)
if ($conexion) {
    try {
        // Verificar si existe la columna grupo_fiado en ventas
        $test = $conexion->query("PRAGMA table_info(ventas)");
        $columns = $test->fetchAll(PDO::FETCH_ASSOC);
        $hasGrupoFiado = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'grupo_fiado') {
                $hasGrupoFiado = true;
                break;
            }
        }
        
        // Si no existe, agregarla
        if (!$hasGrupoFiado) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN grupo_fiado TEXT");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Columna grupo_fiado agregada a tabla ventas\n", 
                FILE_APPEND);
        }
        
        // Verificar columnas faltantes en tabla ventas
        $hasCelularFiado = false;
        $hasEstadoCuenta = false;
        $hasMetodoPago = false;
        $hasTipoProducto = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'celular_fiado') $hasCelularFiado = true;
            if ($col['name'] === 'estado_cuenta') $hasEstadoCuenta = true;
            if ($col['name'] === 'metodo_pago') $hasMetodoPago = true;
        }
        
        // También verificar columna tipo_producto en tabla productos
        $testProd = $conexion->query("PRAGMA table_info(productos)");
        $columnsProd = $testProd->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnsProd as $col) {
            if ($col['name'] === 'tipo_producto') $hasTipoProducto = true;
        }
        
        if (!$hasCelularFiado) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN celular_fiado TEXT");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Columna celular_fiado agregada a tabla ventas\n", 
                FILE_APPEND);
        }
        
        if (!$hasEstadoCuenta) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN estado_cuenta TEXT DEFAULT 'pendiente'");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Columna estado_cuenta agregada a tabla ventas\n", 
                FILE_APPEND);
        }
        
        if (!$hasMetodoPago) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN metodo_pago TEXT");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Columna metodo_pago agregada a tabla ventas\n", 
                FILE_APPEND);
        }
        
        if (!$hasTipoProducto) {
            $conexion->exec("ALTER TABLE productos ADD COLUMN tipo_producto TEXT DEFAULT 'producto'");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Columna tipo_producto agregada a tabla productos\n", 
                FILE_APPEND);
        }
        
        // Verificar si existe la tabla cuentas
        $tablesCuentas = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cuentas'")->fetchAll();
        if (count($tablesCuentas) === 0) {
            $conexion->exec("CREATE TABLE cuentas (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre_cuenta TEXT UNIQUE NOT NULL, celular TEXT, estado_cuenta TEXT DEFAULT 'activo', saldo_total REAL DEFAULT 0, fecha_primer_compra DATETIME, fecha_ultimo_compra DATETIME, fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP, notas TEXT)");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Tabla cuentas creada\n", 
                FILE_APPEND);
        }
        
        // Verificar si existe la tabla confirmacion_pagos
        $tablesConfirmacion = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='confirmacion_pagos'")->fetchAll();
        if (count($tablesConfirmacion) === 0) {
            $conexion->exec("CREATE TABLE confirmacion_pagos (id INTEGER PRIMARY KEY AUTOINCREMENT, venta_id INTEGER, metodo_pago TEXT, comprobante_referencia TEXT, estado TEXT DEFAULT 'pendiente', fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_confirmacion DATETIME, usuario_confirmo TEXT, notas TEXT)");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Tabla confirmacion_pagos creada\n", 
                FILE_APPEND);
        }
        
        // Verificar si existe la tabla cortes_caja
        $tablesCortes = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cortes_caja'")->fetchAll();
        if (count($tablesCortes) === 0) {
            $conexion->exec("CREATE TABLE cortes_caja (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP, fecha_cierre DATETIME, usuario_apertura TEXT, usuario_cierre TEXT, saldo_inicial REAL DEFAULT 0, saldo_final REAL DEFAULT 0, ingresos_efectivo REAL DEFAULT 0, ingresos_tarjeta REAL DEFAULT 0, ingresos_transferencia REAL DEFAULT 0, egresos REAL DEFAULT 0, diferencia REAL DEFAULT 0, estado TEXT DEFAULT 'abierto', notas TEXT)");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Tabla cortes_caja creada\n", 
                FILE_APPEND);
        }
        
        // Verificar si existe la tabla audit_log
        $tables = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_log'")->fetchAll();
        if (count($tables) === 0) {
            $conexion->exec("CREATE TABLE IF NOT EXISTS audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, usuario TEXT, accion TEXT, tabla TEXT, registro_id INTEGER, datos_anteriores TEXT, datos_nuevos TEXT)");
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - Migración: Tabla audit_log creada\n", 
                FILE_APPEND);
        }
        
        // VERIFICACIÓN CRÍTICA: Asegurar que usuario superadmin AdanGL exista SIEMPRE
        $check_superadmin = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios WHERE username='AdanGL'")->fetch();
        if ($check_superadmin['cnt'] == 0) {
            $pass_hash = password_hash("Agl252002", PASSWORD_DEFAULT);
            $stmt_admin = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
            $stmt_admin->execute(['AdanGL', $pass_hash, 'superadmin']);
            @file_put_contents($log_dir . '/db_errors.log', 
                date('Y-m-d H:i:s') . " - CRÍTICO: Usuario superadmin AdanGL recreado\n", 
                FILE_APPEND);
        }
    } catch (Exception $migError) {
        // Si falla, loguear pero continuar
        @file_put_contents($log_dir . '/db_errors.log', 
            date('Y-m-d H:i:s') . " - Error en migración: " . $migError->getMessage() . "\n", 
            FILE_APPEND);
    }
}

// CSRF Token generation
if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Error logging function - SQLite only
function log_error_db($mensaje) {
    global $conexion;
    $ts = date('Y-m-d H:i:s');
    $txt = "[$ts] " . $mensaje . "\n";
    @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'db_errors.log', $txt, FILE_APPEND);
    
    try {
        if (isset($conexion) && $conexion) {
            $stmt = $conexion->prepare("INSERT INTO log_errores (fecha, tipo, mensaje, detalles, url) VALUES (datetime('now'), ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->execute(['db', substr($mensaje, 0, 255), substr($mensaje, 0, 1000), '']);
            }
        }
    } catch (Exception $e) {
        @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'db_errors.log', 
            date('c') . " - log table write failed\n", FILE_APPEND);
    }
}

// Audit logging disabled for offline mode
function registrar_auditoria($tabla, $registro_id, $campo, $valor_anterior, $valor_nuevo) {
    global $conexion;
    
    try {
        $usuario = $_SESSION['usuario'] ?? 'desconocido';
        
        $stmt = $conexion->prepare("
            INSERT INTO audit_log (usuario, accion, tabla, registro_id, datos_anteriores, datos_nuevos, fecha) 
            VALUES (?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
        ");
        
        $stmt->execute([
            $usuario,
            "Cambio en $campo",
            $tabla,
            $registro_id,
            json_encode([$campo => $valor_anterior]),
            json_encode([$campo => $valor_nuevo])
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("[AUDIT] Error registrando cambio: " . $e->getMessage());
        return false;
    }
}

// Ensure database connection
function asegurar_conexion_db() {
    global $conexion, $db_driver, $config, $db_file, $log_dir;
    
    if (!$conexion) {
        return false;
    }
    
    try {
        $conexion->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        error_log("[RECONNECT] Connection lost");
        
        for ($retry = 0; $retry < 3; $retry++) {
            try {
                if ($db_driver === 'mysql') {
                    $db_host = $config['db']['host'] ?? 'localhost';
                    $db_name = $config['db']['name'] ?? '';
                    $db_user = $config['db']['user'] ?? '';
                    $db_pass = $config['db']['pass'] ?? '';
                    $db_charset = $config['db']['charset'] ?? 'utf8mb4';
                    
                    $dsn = "mysql:host={$db_host}:3306;dbname={$db_name};charset={$db_charset}";
                    $conexion = new PDO($dsn, $db_user, $db_pass, [
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=28800",
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    
                    $conexion->exec("SET NAMES {$db_charset}");
                    $conexion->query("SELECT 1");
                    
                    @file_put_contents($log_dir . '/db_reconnect.log', 
                        date('Y-m-d H:i:s') . " MySQL reconnected\n", FILE_APPEND);
                    return true;
                }
            } catch (Exception $reconnect_error) {
                if ($retry < 2) {
                    usleep(500000);
                    continue;
                }
            }
        }
        
        if ($db_driver === 'mysql') {
            try {
                $dsn = "sqlite:" . $db_file;
                $conexion = new PDO($dsn, '', '', [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                $conexion->query("SELECT 1");
                $db_driver = 'sqlite';
                
                @file_put_contents($log_dir . '/db_fallback.log', 
                    date('Y-m-d H:i:s') . " Fallback to SQLite\n", FILE_APPEND);
                return true;
            } catch (Exception $fallback_error) {
                error_log("[FALLBACK] SQLite also failed");
                return false;
            }
        }
        
        return false;
    }
}
