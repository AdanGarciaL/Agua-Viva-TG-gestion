<?php
/**
 * init.php - Inicializador del sistema
 * Se ejecuta antes de cargar cualquier página
 * Asegura que el sistema esté listo
 */

session_start();

// Máxima compatibilidad con errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Intentar cargar la BD con máxima tolerancia a errores
try {
    include dirname(__DIR__) . '/api/db.php';
    include dirname(__DIR__) . '/api/error_handler.php';
    
    // Verificar y asegurar la conexión
    if (!isset($conexion) || !asegurar_conexion_db()) {
        error_log("[INIT] Advertencia: Conexión a BD no disponible");
    }
} catch (Exception $e) {
    error_log("[INIT] Error crítico: " . $e->getMessage());
}

// El sistema continúa incluso si la BD falla (fallback a offline)
?>
