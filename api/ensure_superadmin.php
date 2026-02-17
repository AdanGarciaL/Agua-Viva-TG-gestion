<?php
/**
 * ensure_superadmin.php
 * Garantiza que el usuario superadmin AdanGL exista en la base de datos
 * Este script se puede llamar en cualquier momento para verificar/crear el usuario
 */

// Cargar configuración
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';

// Obtener ruta de base de datos
$db_file = DB_PATH;
$log_dir = dirname($db_file);
if (!is_dir($log_dir)) @mkdir($log_dir, 0777, true);

$log_file = $log_dir . DIRECTORY_SEPARATOR . 'superadmin_check.log';

function log_check($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
}

try {
    log_check("═══ VERIFICACIÓN DE SUPERADMIN INICIADA ═══");
    
    // Conectar a base de datos
    $conexion = new PDO("sqlite:$db_file", '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    log_check("✓ Conexión a BD exitosa: $db_file");
    
    // Verificar si la tabla usuarios existe
    $table_check = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
    
    if (!$table_check) {
        log_check("⚠ Tabla usuarios no existe, creando...");
        $conexion->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        )");
        log_check("✓ Tabla usuarios creada");
    } else {
        log_check("✓ Tabla usuarios existe");
    }
    
    // Verificar si el usuario AdanGL existe
    $user_check = $conexion->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL'")->fetch();
    
    if (!$user_check) {
        log_check("⚠ Usuario AdanGL NO existe, creando...");
        
        // Crear usuario superadmin
        $password_hash = password_hash("Agl252002", PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['AdanGL', $password_hash, 'superadmin']);
        
        // Verificar creación
        $verify = $conexion->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL'")->fetch();
        
        if ($verify) {
            log_check("✓✓✓ Usuario AdanGL creado exitosamente!");
            log_check("    ID: " . $verify['id']);
            log_check("    Username: " . $verify['username']);
            log_check("    Role: " . $verify['role']);
            log_check("    Contraseña: Agl252002 (hash guardado en BD)");
            
            return [
                'success' => true,
                'action' => 'created',
                'message' => 'Usuario superadmin AdanGL creado exitosamente',
                'user' => $verify
            ];
        } else {
            log_check("✗ ERROR: No se pudo verificar la creación del usuario");
            return [
                'success' => false,
                'action' => 'failed',
                'message' => 'No se pudo crear el usuario AdanGL'
            ];
        }
        
    } else {
        log_check("✓ Usuario AdanGL ya existe");
        log_check("    ID: " . $user_check['id']);
        log_check("    Username: " . $user_check['username']);
        log_check("    Role: " . $user_check['role']);
        
        // Verificar que sea superadmin
        if ($user_check['role'] !== 'superadmin') {
            log_check("⚠ Usuario existe pero NO es superadmin, actualizando...");
            $conexion->exec("UPDATE usuarios SET role='superadmin' WHERE username='AdanGL'");
            log_check("✓ Usuario actualizado a superadmin");
            
            return [
                'success' => true,
                'action' => 'updated',
                'message' => 'Usuario AdanGL actualizado a superadmin',
                'user' => $user_check
            ];
        }
        
        return [
            'success' => true,
            'action' => 'exists',
            'message' => 'Usuario superadmin AdanGL ya existe',
            'user' => $user_check
        ];
    }
    
    log_check("═══ VERIFICACIÓN COMPLETADA ═══");
    
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    log_check("✗✗✗ ERROR CRÍTICO: $error_msg");
    log_check("    Archivo: " . $e->getFile());
    log_check("    Línea: " . $e->getLine());
    
    return [
        'success' => false,
        'action' => 'error',
        'message' => 'Error: ' . $error_msg,
        'error' => $e
    ];
}

// Si se ejecuta directamente (no como include)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $result = ensure_superadmin_exists();
    echo json_encode($result, JSON_PRETTY_PRINT);
}
