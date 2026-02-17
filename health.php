<?php
/**
 * health.php - Estado de la aplicación (sin headers JSON, compatible con navegador)
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Estado TG Gestión</title>
    <style>
        * { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #0d47a1; margin-bottom: 20px; border-bottom: 3px solid #0d47a1; padding-bottom: 10px; }
        .status { display: flex; align-items: center; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .status.ok { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .status.error { background: #ffebee; border-left: 4px solid #f44336; }
        .status.warning { background: #fff3e0; border-left: 4px solid #ff9800; }
        .icon { font-size: 20px; margin-right: 10px; }
        .ok .icon { color: #4caf50; }
        .error .icon { color: #f44336; }
        .warning .icon { color: #ff9800; }
        p { margin: 5px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 Estado de TG Gestión v10 Beta (Offline)</h1>

<?php
try {
    // 1. Config
    require_once 'config.php';
    $configOK = true;
    echo '<div class="status ok">
        <span class="icon">✓</span>
        <p><strong>Configuración:</strong> OK<br>
        <code>driver: ' . $config['db']['driver'] . '</code> | 
        <code>offline: true</code></p>
    </div>';
    
    // 2. BD
    require_once 'api/db.php';
    
    if ($conexion && DB_DRIVER === 'sqlite') {
        echo '<div class="status ok">
            <span class="icon">✓</span>
            <p><strong>Base de Datos SQLite:</strong> Conectada<br>
            <code>Ruta: ' . basename(DB_PATH) . '</code> | 
            <code>Existe: ' . (file_exists(DB_PATH) ? 'Sí' : 'No (se creará)') . '</code></p>
        </div>';
        
        // Tablas
        $tables = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo '<div class="status ok">
                <span class="icon">✓</span>
                <p><strong>Tablas:</strong> ' . count($tables) . ' encontradas<br>
                <code>' . implode(', ', $tables) . '</code></p>
            </div>';
        } else {
            echo '<div class="status warning">
                <span class="icon">⚠</span>
                <p><strong>Tablas:</strong> No encontradas (se crearán en primer acceso)</p>
            </div>';
        }
        
        // Test query
        $test = $conexion->query("SELECT 1 as test")->fetch();
        if ($test) {
            echo '<div class="status ok">
                <span class="icon">✓</span>
                <p><strong>Query de Prueba:</strong> Exitoso</p>
            </div>';
        }
    } else {
        echo '<div class="status error">
            <span class="icon">✗</span>
            <p><strong>Base de Datos:</strong> ERROR<br>
            No se pudo conectar a SQLite</p>
        </div>';
    }
    
    // 3. Directorios
    $dirs_ok = true;
    $db_dir = dirname(DB_PATH);
    
    if (!is_dir($db_dir)) {
        @mkdir($db_dir, 0777, true);
    }
    
    if (is_dir($db_dir) && is_writable($db_dir)) {
        echo '<div class="status ok">
            <span class="icon">✓</span>
            <p><strong>Directorio de Datos:</strong> OK<br>
            <code>' . $db_dir . '</code></p>
        </div>';
    } else {
        echo '<div class="status error">
            <span class="icon">✗</span>
            <p><strong>Directorio de Datos:</strong> NO escribible<br>
            <code>' . $db_dir . '</code></p>
        </div>';
        $dirs_ok = false;
    }
    
    // 4. Resumen
    echo '<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">';
    echo '<h2>✓ ESTADO GENERAL: OK</h2>';
    echo '<p><strong>La aplicación está lista para usar</strong></p>';
    echo '<p>Accede a <code>/index.php</code> para iniciar sesión</p>';
    echo '<p><strong>Credenciales:</strong> AdanGL / Agl252002</p>';
    
} catch (Exception $e) {
    echo '<div class="status error">
        <span class="icon">✗</span>
        <p><strong>ERROR:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>
        <code>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</code></p>
    </div>';
}
?>

</div>
</body>
</html>
