<?php
/**
 * connection_retry.php - Endpoint de reintento de conexión
 * v1.0 - Manejo automático de reconexión
 * 
 * Uso desde JS:
 * fetch('api/connection_retry.php')
 *   .then(r => r.json())
 *   .then(d => { if(d.success) location.reload(); })
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();

// Intentar cargar DB
try {
    include 'db.php';
    include 'error_handler.php';
    
    // Forzar reconexión
    if (!asegurar_conexion_db()) {
        throw new Exception('No se puede conectar a BD');
    }
    
    // Test de conexión
    $conexion->query("SELECT 1");
    
    // Éxito
    echo json_encode([
        'success' => true,
        'message' => 'Conexión recuperada',
        'timestamp' => date('Y-m-d H:i:s'),
        'driver' => defined('DB_DRIVER') ? DB_DRIVER : 'unknown'
    ]);
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
