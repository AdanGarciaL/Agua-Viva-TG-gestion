<?php
/**
 * launcher.php - Punto de entrada para PHP Desktop
 * Inicializa la aplicación completamente - VERSIÓN BLINDADA
 */

// NO iniciar sesión aquí - dejalo para pages que lo necesiten
// session_start(); // Comentado: lo hace index.php y dashboard.php

// Cargar configuración
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

$log_file = dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'launcher.log';

function log_msg($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    @file_put_contents($log_file, "$timestamp [PID:$pid] $msg\n", FILE_APPEND);
    // También log en error_log de PHP para debugging
    error_log("[LAUNCHER] $msg");
}

// Crear estructura de directorios
$required_dirs = [
    dirname(DB_PATH),
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data',
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'backups',
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        $ok = @mkdir($dir, 0777, true);
        log_msg("Crear dir: $dir -> " . ($ok ? 'OK' : 'FAIL'));
    }
}

// ════════════════════════════════════════════════════════════════
// INICIALIZACIÓN BLINDADA - GARANTIZA QUE TODO FUNCIONE
// ════════════════════════════════════════════════════════════════

$init_success = false;

try {
    log_msg("═══ INICIANDO LAUNCHER v2 (BLINDADO) ═══");
    
    // 1. Conectar a BD
    log_msg("1. Conectando a BD: " . DB_PATH);
    $conexion = new PDO("sqlite:" . DB_PATH, '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optimizaciones
    $conexion->exec('PRAGMA foreign_keys = ON');
    $conexion->exec('PRAGMA journal_mode = WAL');
    $conexion->exec('PRAGMA synchronous = NORMAL');
    log_msg("   ✓ BD conectada");
    
    // 2. Crear tabla usuarios (FORZAR si no existe)
    log_msg("2. Verificando tabla usuarios...");
    $conexion->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL
    )");
    log_msg("   ✓ Tabla usuarios lista");
    
    // 3. Verificar/crear usuario AdanGL (GARANTIZADO)
    log_msg("3. Verificando usuario AdanGL...");
    $check_user = $conexion->query("SELECT id, role FROM usuarios WHERE username='AdanGL' LIMIT 1")->fetch();
    
    if (!$check_user) {
        log_msg("   → Usuario no existe, creando...");
        $pass_hash = password_hash("Agl252002", PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['AdanGL', $pass_hash, 'superadmin']);
        
        // Verificar que se creó
        $verify = $conexion->query("SELECT id FROM usuarios WHERE username='AdanGL' LIMIT 1")->fetch();
        if ($verify) {
            log_msg("   ✓✓ Usuario AdanGL creado con éxito (ID: " . $verify['id'] . ")");
        } else {
            throw new Exception('No se pudo crear usuario AdanGL');
        }
    } else {
        log_msg("   ✓ Usuario AdanGL existe (ID: " . $check_user['id'] . ")");
        
        // Verificar que sea superadmin
        if ($check_user['role'] !== 'superadmin') {
            log_msg("   → Usuario no es superadmin, actualizando...");
            $conexion->exec("UPDATE usuarios SET role='superadmin' WHERE username='AdanGL'");
            log_msg("   ✓ Usuario AdanGL actualizado a superadmin");
        }
    }
    
    // 4. Crear resto de tablas
    log_msg("4. Creando tablas adicionales...");
    $conexion->exec("CREATE TABLE IF NOT EXISTS productos (id INTEGER PRIMARY KEY, nombre TEXT, codigo_barras TEXT, precio_venta REAL, stock INTEGER, stock_minimo INTEGER DEFAULT 10, foto_url TEXT, activo INTEGER DEFAULT 1)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS ventas (id INTEGER PRIMARY KEY, producto_id INTEGER, cantidad INTEGER, total REAL, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, vendedor TEXT, foto_referencia TEXT, tipo_pago TEXT, nombre_fiado TEXT, fiado_pagado INTEGER DEFAULT 0)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS registros (id INTEGER PRIMARY KEY, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, concepto TEXT, monto REAL, usuario TEXT, categoria TEXT, servicio TEXT)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS septimas (id INTEGER PRIMARY KEY, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, nombre_padrino TEXT, monto REAL, usuario_registro TEXT, pagado INTEGER DEFAULT 0, tipo TEXT DEFAULT 'normal', servicio TEXT)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS log_errores (id INTEGER PRIMARY KEY, fecha DATETIME DEFAULT CURRENT_TIMESTAMP, tipo TEXT, mensaje TEXT, detalles TEXT, url TEXT)");
    $conexion->exec("CREATE TABLE IF NOT EXISTS configuracion (clave TEXT PRIMARY KEY, valor TEXT)");
    log_msg("   ✓ Tablas adicionales lista");
    
    // 5. Verificación final
    log_msg("5. Verificación final...");
    $final_check = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios WHERE username='AdanGL'")->fetch();
    
    if ($final_check['cnt'] > 0) {
        $init_success = true;
        log_msg("   ✓ Usuario AdanGL confirmado en BD");
    } else {
        throw new Exception('Verificación final: usuario AdanGL no encontrado');
    }
    
    log_msg("═══ LAUNCHER COMPLETADO CON ÉXITO ═══");
    
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    log_msg("✗ ERROR CRÍTICO: $error_msg");
    log_msg("   Archivo: " . $e->getFile());
    log_msg("   Línea: " . $e->getLine());
}

// ════════════════════════════════════════════════════════════════
// REDIRECCIONAMIENTO
// ════════════════════════════════════════════════════════════════

$marker_file = dirname(DB_PATH) . DIRECTORY_SEPARATOR . '.installation_complete';
$db_exists_before = file_exists(DB_PATH);

if ($init_success) {
    // Si BD fue inicializada correctamente, marcar como instalado
    @file_put_contents($marker_file, '1');
    
    // Redirigir a login directamente
    header("Location: index.php");
} else {
    // Error en inicialización - mostrar opciones de reparación
    if (!file_exists($marker_file) && !$db_exists_before) {
        // Primera vez - ir a restore-system para reparar
        header("Location: restore-system.php");
    } else {
        // BD existe pero hay error - ir a restore-system
        header("Location: restore-system.php");
    }
}
exit;
?>
