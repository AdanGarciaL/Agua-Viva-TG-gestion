<?php
/**
 * force-init-db.php
 * Script de emergencia para forzar la creación de la BD y usuario superadmin
 */

// Cargar configuración
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicialización Forzada - TG Gestión</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #fff; }
        .btn { 
            display: inline-block;
            margin: 10px 5px;
            padding: 10px 20px;
            background: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #0055aa; }
    </style>
</head>
<body>
    <h1>🔧 Inicialización Forzada de Base de Datos</h1>
    <pre>
<?php

$log = [];
$errors = [];
$success = false;

try {
    $log[] = "[INFO] Iniciando proceso de inicialización forzada...";
    $log[] = "[INFO] Ruta de BD configurada: " . DB_PATH;
    
    // 1. Verificar si existe la carpeta
    $db_dir = dirname(DB_PATH);
    if (!is_dir($db_dir)) {
        $log[] = "[WARN] Carpeta no existe, creando: $db_dir";
        if (@mkdir($db_dir, 0777, true)) {
            $log[] = "[OK] Carpeta creada exitosamente";
        } else {
            $errors[] = "No se pudo crear la carpeta: $db_dir";
            throw new Exception("No se pudo crear carpeta de base de datos");
        }
    } else {
        $log[] = "[OK] Carpeta existe: $db_dir";
    }
    
    // 2. Verificar permisos de escritura
    if (!is_writable($db_dir)) {
        $log[] = "[WARN] Carpeta no tiene permisos de escritura";
        @chmod($db_dir, 0777);
        if (is_writable($db_dir)) {
            $log[] = "[OK] Permisos corregidos";
        } else {
            $errors[] = "No se pueden establecer permisos de escritura en: $db_dir";
        }
    } else {
        $log[] = "[OK] Carpeta tiene permisos de escritura";
    }
    
    // 3. Si BD existe, hacer backup
    if (file_exists(DB_PATH)) {
        $log[] = "[WARN] Base de datos ya existe";
        $backup_file = DB_PATH . '.backup_' . date('YmdHis');
        if (@copy(DB_PATH, $backup_file)) {
            $log[] = "[OK] Backup creado: " . basename($backup_file);
        }
        
        // Preguntar si eliminar
        $log[] = "[INFO] Eliminando BD antigua para recrear...";
        if (@unlink(DB_PATH)) {
            $log[] = "[OK] BD antigua eliminada";
        } else {
            $log[] = "[WARN] No se pudo eliminar BD antigua, continuando...";
        }
    } else {
        $log[] = "[INFO] No existe BD previa";
    }
    
    // 4. Crear conexión nueva
    $log[] = "[INFO] Creando nueva base de datos...";
    $conexion = new PDO("sqlite:" . DB_PATH, '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $log[] = "[OK] Conexión PDO establecida";
    
    // 5. Optimizar SQLite
    $conexion->exec('PRAGMA journal_mode = WAL');
    $conexion->exec('PRAGMA synchronous = NORMAL');
    $conexion->exec('PRAGMA temp_store = MEMORY');
    $conexion->exec('PRAGMA foreign_keys = ON');
    $log[] = "[OK] SQLite optimizado";
    
    // 6. Crear tabla usuarios
    $log[] = "[INFO] Creando tabla usuarios...";
    $conexion->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL
    )");
    $log[] = "[OK] Tabla usuarios creada";
    
    // 7. Insertar usuario superadmin
    $log[] = "[INFO] Insertando usuario superadmin AdanGL...";
    $password_hash = password_hash("Agl252002", PASSWORD_DEFAULT);
    $log[] = "[DEBUG] Hash generado: " . substr($password_hash, 0, 20) . "...";
    
    $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['AdanGL', $password_hash, 'superadmin']);
    $user_id = $conexion->lastInsertId();
    $log[] = "[OK] Usuario AdanGL creado con ID: $user_id";
    
    // 8. Verificar usuario
    $log[] = "[INFO] Verificando usuario creado...";
    $verify = $conexion->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL'")->fetch();
    
    if ($verify) {
        $log[] = "[OK] Usuario verificado:";
        $log[] = "      - ID: " . $verify['id'];
        $log[] = "      - Username: " . $verify['username'];
        $log[] = "      - Role: " . $verify['role'];
    } else {
        throw new Exception("Usuario no encontrado después de insertar");
    }
    
    // 9. Verificar contraseña
    $log[] = "[INFO] Verificando contraseña...";
    $pass_check = $conexion->query("SELECT password FROM usuarios WHERE username='AdanGL'")->fetch();
    $pass_valid = password_verify("Agl252002", $pass_check['password']);
    
    if ($pass_valid) {
        $log[] = "[OK] Contraseña 'Agl252002' VÁLIDA ✓";
    } else {
        $log[] = "[ERROR] Contraseña NO válida ✗";
        $errors[] = "La contraseña no se puede verificar";
    }
    
    // 10. Crear otras tablas básicas
    $log[] = "[INFO] Creando tablas adicionales...";
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS productos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        codigo_barras TEXT,
        precio_venta REAL NOT NULL,
        stock INTEGER NOT NULL,
        stock_minimo INTEGER DEFAULT 10,
        activo INTEGER DEFAULT 1
    )");
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS ventas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        producto_id INTEGER,
        cantidad INTEGER,
        total REAL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        vendedor TEXT,
        tipo_pago TEXT,
        nombre_fiado TEXT,
        grupo_fiado TEXT,
        fiado_pagado INTEGER DEFAULT 0
    )");
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS registros (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        tipo TEXT,
        concepto TEXT,
        monto REAL,
        usuario TEXT,
        categoria TEXT,
        servicio TEXT
    )");
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS septimas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        nombre_padrino TEXT,
        monto REAL,
        usuario_registro TEXT,
        pagado INTEGER DEFAULT 0,
        tipo TEXT DEFAULT 'normal',
        servicio TEXT
    )");
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS log_errores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        tipo TEXT,
        mensaje TEXT,
        detalles TEXT,
        url TEXT
    )");
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS configuracion (
        clave TEXT PRIMARY KEY,
        valor TEXT
    )");
    
    $conexion->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        usuario TEXT,
        accion TEXT,
        tabla TEXT,
        registro_id INTEGER,
        datos_anteriores TEXT,
        datos_nuevos TEXT
    )");
    
    $log[] = "[OK] Todas las tablas creadas exitosamente";
    
    // 11. Insertar producto de ejemplo
    $stmt = $conexion->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Producto Ejemplo', '0000000000000', 100.00, 50]);
    $log[] = "[OK] Producto de ejemplo insertado";
    
    // 12. Contar tablas
    $tables = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
    $log[] = "[INFO] Total de tablas creadas: " . count($tables);
    foreach ($tables as $table) {
        $log[] = "      - " . $table['name'];
    }
    
    $success = true;
    $log[] = "";
    $log[] = "═════════════════════════════════════════════";
    $log[] = "[SUCCESS] ✓✓✓ BASE DE DATOS INICIALIZADA ✓✓✓";
    $log[] = "═════════════════════════════════════════════";
    $log[] = "";
    $log[] = "Credenciales de acceso:";
    $log[] = "  Usuario: AdanGL";
    $log[] = "  Contraseña: Agl252002";
    
} catch (Exception $e) {
    $errors[] = "EXCEPCIÓN: " . $e->getMessage();
    $log[] = "[ERROR] " . $e->getMessage();
    $log[] = "[ERROR] Archivo: " . $e->getFile();
    $log[] = "[ERROR] Línea: " . $e->getLine();
    $log[] = "[ERROR] Traza: " . $e->getTraceAsString();
}

// Mostrar logs
foreach ($log as $line) {
    if (strpos($line, '[OK]') !== false || strpos($line, 'SUCCESS') !== false) {
        echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '[ERROR]') !== false) {
        echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '[WARN]') !== false) {
        echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '[INFO]') !== false || strpos($line, '[DEBUG]') !== false) {
        echo '<span class="info">' . htmlspecialchars($line) . '</span>' . "\n";
    } else {
        echo htmlspecialchars($line) . "\n";
    }
}

// Guardar log en archivo
$log_file = dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'force_init_' . date('Ymd_His') . '.log';
@file_put_contents($log_file, implode("\n", $log));

?>
    </pre>
    
    <div style="margin-top: 20px; padding: 20px; background: #2e2e2e; border-radius: 5px;">
        <?php if ($success): ?>
            <h2 style="color: #00ff00;">✅ Inicialización Completada</h2>
            <p style="color: #fff;">La base de datos ha sido creada exitosamente.</p>
            <a href="index.php" class="btn">🏠 Ir al Login</a>
            <a href="verify-superadmin.php" class="btn">🔍 Verificar Sistema</a>
        <?php else: ?>
            <h2 style="color: #ff0000;">❌ Error en Inicialización</h2>
            <p style="color: #fff;">Hubo errores durante la inicialización:</p>
            <ul style="color: #ff6666;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="force-init-db.php" class="btn">🔄 Reintentar</a>
        <?php endif; ?>
        
        <p style="color: #888; margin-top: 20px; font-size: 12px;">
            Log guardado en: <?php echo htmlspecialchars(basename($log_file ?? 'N/A')); ?>
        </p>
    </div>
</body>
</html>
