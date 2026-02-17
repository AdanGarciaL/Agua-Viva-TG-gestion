<?php
// simulate_salud_sistema.php - Simula la llamada a salud_sistema

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Limpiar cualquier output previo
ob_clean();

// Set header
header('Content-Type: application/json; charset=utf-8');

// Simular sesión
session_start();
$_SESSION['usuario'] = 'TestAdmin';
$_SESSION['role'] = 'admin';

// Incluir db
require_once 'config.php';
require_once 'api/db.php';

// Simulación de salud_sistema
$esAdmin = true; // Simular que es admin
$isMysql = false; // SQLite

try {
    // 1. Tamaño de BD
    $dbPath = DB_PATH;
    $dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024 / 1024, 2) : 0;
    
    // 2. Usuarios totales
    $stmt = $conexion->query("SELECT COUNT(*) as count FROM usuarios");
    $row = $stmt->fetch();
    $usuariosTotal = intval($row && isset($row['count']) ? $row['count'] : 0);
    
    // 3. Productos activos
    $stmt = $conexion->query("SELECT COUNT(*) as count FROM productos WHERE activo = 1");
    $row = $stmt->fetch();
    $productosActivos = intval($row && isset($row['count']) ? $row['count'] : 0);
    
    // 4. Total ventas
    $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas");
    $row = $stmt->fetch();
    $ventasTotal = floatval($row && isset($row['total']) ? $row['total'] : 0);
    
    // 5. Fiados pendientes
    $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE tipo_pago = 'fiado' AND fiado_pagado = 0");
    $row = $stmt->fetch();
    $fiados = floatval($row && isset($row['total']) ? $row['total'] : 0);
    
    // 6. Espacio libre
    $dbDir = dirname($dbPath);
    $diskFree = disk_free_space($dbDir);
    $diskFreeGb = $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) : 0;
    
    // Respuesta
    $response = [
        'success' => true,
        'db_size_mb' => $dbSize,
        'usuarios_total' => $usuariosTotal,
        'productos_activos' => $productosActivos,
        'ventas_total' => $ventasTotal,
        'fiados_pendientes' => $fiados,
        'disk_free_gb' => $diskFreeGb
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
