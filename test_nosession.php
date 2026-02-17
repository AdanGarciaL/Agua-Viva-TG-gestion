<?php
// test_nosession.php - Test sin sesión

header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar config
    require_once 'config.php';
    
    // Cargar BD
    require_once 'api/db.php';
    
    if (!$conexion) {
        echo json_encode(['success' => false, 'error' => 'Sin conexion a BD']);
        exit();
    }
    
    // Test de queries
    $db_path = DB_PATH;
    $db_size = file_exists($db_path) ? round(filesize($db_path) / 1024 / 1024, 2) : 0;
    
    $stmt = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios");
    $row = $stmt->fetch();
    $usuarios = intval($row['cnt'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'db_size_mb' => $db_size,
        'usuarios_total' => $usuarios,
        'test' => 'OK'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
